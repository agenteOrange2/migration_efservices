<?php

namespace App\Services\Hos;

use Carbon\Carbon;
use App\Models\Trip;
use App\Models\Hos\HosEntry;
use App\Models\Hos\HosDailyLog;
use App\Models\Hos\HosViolation;
use App\Models\Hos\HosConfiguration;
use App\Models\Hos\HosWeeklyCycle;
use App\Models\Admin\Vehicle\VehicleDriverAssignment;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class HosFMCSAService
{
    protected HosWeeklyCycleService $weeklyCycleService;

    public function __construct(HosWeeklyCycleService $weeklyCycleService)
    {
        $this->weeklyCycleService = $weeklyCycleService;
    }

    /**
     * Validate if a driver can start a trip.
     * Returns validation result with pass/fail and details.
     */
    public function validateTripStart(int $driverId, int $carrierId): array
    {
        $errors = [];
        $warnings = [];

        // 1. Check 10-hour reset requirement (FMCSA requirement for local drivers)
        $resetCheck = $this->weeklyCycleService->canStartNewDutyPeriod($driverId);
        if (!$resetCheck['can_start']) {
            $errors[] = [
                'type' => '10_hour_reset',
                'message' => "Driver needs {$resetCheck['hours_needed']} more hours of rest before starting a new duty period.",
                'hours_needed' => $resetCheck['hours_needed'],
                'fmcsa_reference' => '37 TAC §4.11(a)',
            ];
        }

        // 2. Check weekly cycle hours
        $weeklyStatus = $this->weeklyCycleService->getWeeklyCycleStatus($driverId);
        if ($weeklyStatus['is_over_limit']) {
            $errors[] = [
                'type' => 'weekly_cycle_exceeded',
                'message' => "Driver has exceeded the {$weeklyStatus['hours_limit']}-hour weekly limit.",
                'hours_used' => $weeklyStatus['hours_used'],
                'fmcsa_reference' => '37 TAC §4.12(b)(2)',
            ];
        } elseif ($weeklyStatus['is_approaching_limit']) {
            $warnings[] = [
                'type' => 'weekly_cycle_warning',
                'message' => "Driver is approaching weekly limit. Only {$weeklyStatus['hours_remaining']} hours remaining.",
                'hours_remaining' => $weeklyStatus['hours_remaining'],
            ];
        }

        // 3. Check for blocking penalties
        $penaltyCheck = $this->hasBlockingPenalty($driverId);
        if ($penaltyCheck['has_penalty']) {
            $errors[] = [
                'type' => 'active_penalty',
                'message' => "Driver has an active penalty: {$penaltyCheck['penalty_type']}",
                'penalty_type' => $penaltyCheck['penalty_type'],
                'penalty_expires_at' => $penaltyCheck['expires_at'],
                'hours_remaining' => $penaltyCheck['hours_remaining'],
            ];
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'weekly_status' => $weeklyStatus,
            'reset_status' => $resetCheck,
        ];
    }

    /**
     * Check driving time limit (12 hours).
     */
    public function checkDrivingLimit(int $driverId, int $carrierId): array
    {
        $config = HosConfiguration::getForCarrier($carrierId);
        $maxDrivingMinutes = $config->max_driving_hours * 60; // 12 hours = 720 minutes

        // Get today's driving minutes
        $todayDriving = $this->getTodayDrivingMinutes($driverId);
        $remainingMinutes = max(0, $maxDrivingMinutes - $todayDriving);

        $result = [
            'driving_minutes' => $todayDriving,
            'max_minutes' => $maxDrivingMinutes,
            'remaining_minutes' => $remainingMinutes,
            'remaining_hours' => round($remainingMinutes / 60, 2),
            'percentage_used' => round(($todayDriving / $maxDrivingMinutes) * 100, 1),
            'is_exceeded' => $todayDriving >= $maxDrivingMinutes,
            'is_warning' => $remainingMinutes <= 60 && $remainingMinutes > 0, // 1 hour warning
        ];

        Log::debug('HOS: Driving limit check performed', [
            'driver_id' => $driverId,
            'carrier_id' => $carrierId,
            'date' => today()->toDateString(),
            'driving_minutes' => $todayDriving,
            'max_minutes' => $maxDrivingMinutes,
            'remaining_minutes' => $remainingMinutes,
            'is_exceeded' => $result['is_exceeded'],
        ]);

        // Create violation if exceeded
        if ($result['is_exceeded']) {
            $this->createViolation($driverId, $carrierId, [
                'type' => HosViolation::TYPE_DRIVING_LIMIT_EXCEEDED,
                'severity' => HosViolation::SEVERITY_CRITICAL,
                'hours_exceeded' => round(($todayDriving - $maxDrivingMinutes) / 60, 2),
                'fmcsa_reference' => '37 TAC §4.11(a)',
                'penalty_type' => 'mandatory_rest',
                'penalty_hours' => 10,
            ]);
        }

        return $result;
    }

    /**
     * Check duty period (14-hour window).
     */
    public function checkDutyPeriod(int $driverId, int $carrierId): array
    {
        $dailyLog = HosDailyLog::forDriver($driverId)
            ->whereDate('date', today())
            ->first();

        if (!$dailyLog || !$dailyLog->duty_period_start) {
            return [
                'duty_period_active' => false,
                'elapsed_minutes' => 0,
                'remaining_minutes' => 14 * 60,
                'is_exceeded' => false,
            ];
        }

        $maxDutyMinutes = 14 * 60; // 14 hours
        $elapsedMinutes = $dailyLog->duty_period_elapsed_minutes;
        $remainingMinutes = max(0, $maxDutyMinutes - $elapsedMinutes);

        $result = [
            'duty_period_active' => true,
            'duty_period_start' => $dailyLog->duty_period_start->toIso8601String(),
            'elapsed_minutes' => $elapsedMinutes,
            'remaining_minutes' => $remainingMinutes,
            'remaining_hours' => round($remainingMinutes / 60, 2),
            'percentage_used' => round(($elapsedMinutes / $maxDutyMinutes) * 100, 1),
            'is_exceeded' => $elapsedMinutes >= $maxDutyMinutes,
            'is_critical_warning' => $elapsedMinutes >= (13 * 60) && $elapsedMinutes < $maxDutyMinutes,
        ];

        // Create violation if exceeded
        if ($result['is_exceeded']) {
            $this->createViolation($driverId, $carrierId, [
                'type' => HosViolation::TYPE_DUTY_PERIOD_EXCEEDED,
                'severity' => HosViolation::SEVERITY_CRITICAL,
                'hours_exceeded' => round(($elapsedMinutes - $maxDutyMinutes) / 60, 2),
                'fmcsa_reference' => '37 TAC §4.11(a)',
                'penalty_type' => 'mandatory_rest',
                'penalty_hours' => 8,
            ]);
        }

        return $result;
    }

    /**
     * Check 30-minute break requirement (after 8 hours driving).
     */
    public function checkBreakRequirement(int $driverId, int $carrierId): array
    {
        $config = HosConfiguration::getForCarrier($carrierId);
        
        if (!$config->requires30MinBreak()) {
            return [
                'break_required' => false,
                'continuous_driving_minutes' => 0,
            ];
        }

        $breakAfterMinutes = $config->break_after_hours * 60; // 8 hours = 480 minutes
        $continuousDriving = $this->getContinuousDrivingMinutes($driverId);

        $dailyLog = HosDailyLog::forDriver($driverId)
            ->whereDate('date', today())
            ->first();

        $breakTaken = $dailyLog ? $dailyLog->hasCompletedRequiredBreak() : false;

        $result = [
            'break_required' => true,
            'continuous_driving_minutes' => $continuousDriving,
            'max_continuous_minutes' => $breakAfterMinutes,
            'break_taken' => $breakTaken,
            'is_violated' => $continuousDriving >= $breakAfterMinutes && !$breakTaken,
            'minutes_until_break_required' => max(0, $breakAfterMinutes - $continuousDriving),
        ];

        // Create violation if exceeded
        if ($result['is_violated']) {
            $this->createViolation($driverId, $carrierId, [
                'type' => HosViolation::TYPE_MISSING_REQUIRED_BREAK,
                'severity' => HosViolation::SEVERITY_MODERATE,
                'hours_exceeded' => round(($continuousDriving - $breakAfterMinutes) / 60, 2),
                'fmcsa_reference' => '37 TAC §4.11(c)',
                'penalty_type' => 'mandatory_rest',
                'penalty_hours' => 0.5, // 30 minutes
            ]);
        }

        return $result;
    }

    /**
     * Check weekly cycle limits.
     */
    public function checkWeeklyCycle(int $driverId, int $carrierId): array
    {
        $weeklyStatus = $this->weeklyCycleService->getWeeklyCycleStatus($driverId);

        if ($weeklyStatus['is_over_limit']) {
            $this->createViolation($driverId, $carrierId, [
                'type' => HosViolation::TYPE_WEEKLY_CYCLE_EXCEEDED,
                'severity' => HosViolation::SEVERITY_CRITICAL,
                'hours_exceeded' => $weeklyStatus['hours_used'] - $weeklyStatus['hours_limit'],
                'fmcsa_reference' => '37 TAC §4.12(b)(2)',
                'penalty_type' => 'suspension',
                'penalty_hours' => 24,
            ]);
        }

        return $weeklyStatus;
    }

    /**
     * Apply a penalty to a driver.
     */
    public function applyPenalty(int $driverId, string $penaltyType, string $violationType, int $penaltyHours = 0): void
    {
        // Find the most recent violation of this type
        $violation = HosViolation::forDriver($driverId)
            ->where('violation_type', $violationType)
            ->latest()
            ->first();

        if ($violation) {
            $violation->update([
                'has_penalty' => true,
                'penalty_type' => $penaltyType,
                'penalty_hours' => $penaltyHours,
                'penalty_start' => now(),
                'penalty_end' => now()->addHours($penaltyHours),
            ]);
        }
    }

    /**
     * Check if driver has a blocking penalty.
     */
    public function hasBlockingPenalty(int $driverId): array
    {
        $blockingViolation = HosViolation::forDriver($driverId)
            ->where('has_penalty', true)
            ->where(function ($query) {
                $query->whereNull('penalty_end')
                    ->orWhere('penalty_end', '>', now());
            })
            ->whereIn('penalty_type', ['suspension', 'mandatory_rest'])
            ->latest()
            ->first();

        if (!$blockingViolation) {
            return [
                'has_penalty' => false,
            ];
        }

        return [
            'has_penalty' => true,
            'penalty_type' => $blockingViolation->penalty_type,
            'violation_type' => $blockingViolation->violation_type,
            'expires_at' => $blockingViolation->penalty_end?->toIso8601String(),
            'hours_remaining' => $blockingViolation->remaining_penalty_hours,
        ];
    }

    /**
     * Get comprehensive FMCSA status for a driver.
     */
    public function getDriverFMCSAStatus(int $driverId, int $carrierId): array
    {
        return [
            'driving_limit' => $this->checkDrivingLimit($driverId, $carrierId),
            'duty_period' => $this->checkDutyPeriod($driverId, $carrierId),
            'break_requirement' => $this->checkBreakRequirement($driverId, $carrierId),
            'weekly_cycle' => $this->checkWeeklyCycle($driverId, $carrierId),
            'penalty_status' => $this->hasBlockingPenalty($driverId),
            'can_drive' => $this->canDriverOperate($driverId, $carrierId),
        ];
    }

    /**
     * Check if driver can currently operate.
     */
    public function canDriverOperate(int $driverId, int $carrierId): array
    {
        $drivingLimit = $this->checkDrivingLimit($driverId, $carrierId);
        $dutyPeriod = $this->checkDutyPeriod($driverId, $carrierId);
        $weeklyCycle = $this->checkWeeklyCycle($driverId, $carrierId);
        $penalty = $this->hasBlockingPenalty($driverId);

        $canOperate = !$drivingLimit['is_exceeded'] 
            && !$dutyPeriod['is_exceeded'] 
            && !$weeklyCycle['is_over_limit']
            && !$penalty['has_penalty'];

        $reasons = [];
        if ($drivingLimit['is_exceeded']) {
            $reasons[] = 'Driving limit exceeded (12h)';
        }
        if ($dutyPeriod['is_exceeded']) {
            $reasons[] = 'Duty period exceeded (14h)';
        }
        if ($weeklyCycle['is_over_limit']) {
            $reasons[] = 'Weekly cycle exceeded';
        }
        if ($penalty['has_penalty']) {
            $reasons[] = "Active penalty: {$penalty['penalty_type']}";
        }

        return [
            'can_operate' => $canOperate,
            'reasons' => $reasons,
        ];
    }

    /**
     * Create a violation record.
     */
    protected function createViolation(int $driverId, int $carrierId, array $data): ?HosViolation
    {
        // Check if similar violation already exists today
        $existing = HosViolation::forDriver($driverId)
            ->where('violation_type', $data['type'])
            ->whereDate('violation_date', today())
            ->first();

        if ($existing) {
            return $existing;
        }

        // Get vehicle_id for the driver
        $vehicleId = $this->getDriverVehicleId($driverId);

        // If no vehicle found, log error and return null
        if (!$vehicleId) {
            Log::error('Cannot create HOS violation: No vehicle found', [
                'driver_id' => $driverId,
                'carrier_id' => $carrierId,
                'violation_type' => $data['type'],
                'violation_severity' => $data['severity'],
            ]);

            return null;
        }

        // Validate all required fields
        try {
            $this->validateViolationData($data, $vehicleId);
        } catch (ValidationException $e) {
            Log::error('HOS violation validation failed', [
                'driver_id' => $driverId,
                'carrier_id' => $carrierId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }

        $violation = HosViolation::create([
            'user_driver_detail_id' => $driverId,
            'carrier_id' => $carrierId,
            'vehicle_id' => $vehicleId,
            'violation_type' => $data['type'],
            'violation_severity' => $data['severity'],
            'violation_date' => today(),
            'hours_exceeded' => $data['hours_exceeded'] ?? 0,
            'fmcsa_rule_reference' => $data['fmcsa_reference'] ?? null,
            'has_penalty' => isset($data['penalty_type']),
            'penalty_type' => $data['penalty_type'] ?? 'none',
            'penalty_hours' => $data['penalty_hours'] ?? null,
            'penalty_start' => isset($data['penalty_type']) ? now() : null,
            'penalty_end' => isset($data['penalty_hours']) 
                ? now()->addHours($data['penalty_hours']) 
                : null,
        ]);

        return $violation;
    }

    /**
     * Get today's driving minutes for a driver.
     * Only includes completed entries (end_time is not null).
     */
    protected function getTodayDrivingMinutes(int $driverId): int
    {
        // Get completed driving entries only
        $entries = HosEntry::forDriver($driverId)
            ->forDate(today())
            ->where('status', HosEntry::STATUS_ON_DUTY_DRIVING)
            ->whereNotNull('end_time')
            ->get();

        $totalMinutes = $entries->sum('duration_minutes');

        // Check if there's an active trip with current driving time
        $activeTrip = $this->getActiveTripForDriver($driverId);
        $activeTripMinutes = 0;

        if ($activeTrip) {
            // Get the current open driving entry for this trip
            $openEntry = HosEntry::forDriver($driverId)
                ->where('trip_id', $activeTrip->id)
                ->where('status', HosEntry::STATUS_ON_DUTY_DRIVING)
                ->whereNull('end_time')
                ->first();

            if ($openEntry) {
                $activeTripMinutes = $openEntry->duration_minutes;
                $totalMinutes += $activeTripMinutes;
            }
        }

        Log::debug('HOS: Calculated driving minutes', [
            'driver_id' => $driverId,
            'date' => today()->toDateString(),
            'completed_entries_count' => $entries->count(),
            'completed_minutes' => $entries->sum('duration_minutes'),
            'active_trip_minutes' => $activeTripMinutes,
            'total_minutes' => $totalMinutes,
        ]);

        return $totalMinutes;
    }

    /**
     * Get continuous driving minutes without a break.
     */
    protected function getContinuousDrivingMinutes(int $driverId): int
    {
        // Get entries from today, ordered by start time
        $entries = HosEntry::forDriver($driverId)
            ->forDate(today())
            ->orderBy('start_time', 'desc')
            ->get();

        $continuousMinutes = 0;

        foreach ($entries as $entry) {
            if ($entry->status === HosEntry::STATUS_ON_DUTY_DRIVING) {
                $continuousMinutes += $entry->duration_minutes;
            } else {
                // Check if this was a qualifying break (30+ minutes off duty or on duty not driving)
                if ($entry->duration_minutes >= 30) {
                    break; // Reset counter
                }
            }
        }

        return $continuousMinutes;
    }

    /**
     * Get vehicle ID for a driver.
     * Priority: 1) Active trip, 2) Current vehicle assignment
     * 
     * @param int $driverId
     * @return int|null
     */
    protected function getDriverVehicleId(int $driverId): ?int
    {
        // Try to get vehicle from active trip first (preferred)
        $activeTrip = $this->getActiveTripForDriver($driverId);
        
        if ($activeTrip && $activeTrip->vehicle_id) {
            Log::debug('HOS: Retrieved vehicle from active trip', [
                'driver_id' => $driverId,
                'trip_id' => $activeTrip->id,
                'vehicle_id' => $activeTrip->vehicle_id,
            ]);
            
            return $activeTrip->vehicle_id;
        }

        // Fallback to current vehicle assignment
        $assignment = $this->getCurrentVehicleAssignment($driverId);
        
        if ($assignment && $assignment->vehicle_id) {
            Log::debug('HOS: Retrieved vehicle from assignment', [
                'driver_id' => $driverId,
                'assignment_id' => $assignment->id,
                'vehicle_id' => $assignment->vehicle_id,
            ]);
            
            return $assignment->vehicle_id;
        }

        // No vehicle found
        Log::warning('HOS: No vehicle found for driver', [
            'driver_id' => $driverId,
            'has_active_trip' => $activeTrip !== null,
            'has_assignment' => $assignment !== null,
        ]);

        return null;
    }

    /**
     * Get active trip for a driver.
     * 
     * @param int $driverId
     * @return Trip|null
     */
    protected function getActiveTripForDriver(int $driverId): ?Trip
    {
        return Trip::where('user_driver_detail_id', $driverId)
            ->where('status', Trip::STATUS_IN_PROGRESS)
            ->first();
    }

    /**
     * Get current vehicle assignment for a driver.
     * 
     * @param int $driverId
     * @return VehicleDriverAssignment|null
     */
    protected function getCurrentVehicleAssignment(int $driverId): ?VehicleDriverAssignment
    {
        return VehicleDriverAssignment::forDriver($driverId)
            ->current()
            ->first();
    }

    /**
     * Validate violation data before creating a violation record.
     * 
     * @param array $data
     * @param int $vehicleId
     * @throws ValidationException
     * @return void
     */
    protected function validateViolationData(array $data, int $vehicleId): void
    {
        $requiredFields = [
            'type' => 'violation_type',
            'severity' => 'violation_severity',
            'hours_exceeded' => 'hours_exceeded',
        ];

        $missingFields = [];

        foreach ($requiredFields as $key => $fieldName) {
            if (!isset($data[$key]) || $data[$key] === null) {
                $missingFields[] = $fieldName;
            }
        }

        // Check vehicle_id
        if (!$vehicleId) {
            $missingFields[] = 'vehicle_id';
        }

        if (!empty($missingFields)) {
            $message = 'Validation failed: Missing required fields: ' . implode(', ', $missingFields);
            
            Log::error('HOS violation validation failed', [
                'missing_fields' => $missingFields,
                'provided_data' => $data,
            ]);

            throw ValidationException::withMessages([
                'violation' => [$message],
            ]);
        }
    }
}
