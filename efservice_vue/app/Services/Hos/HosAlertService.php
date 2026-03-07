<?php

namespace App\Services\Hos;

use Carbon\Carbon;
use App\Models\Trip;
use App\Models\UserDriverDetail;
use App\Models\Hos\HosEntry;
use App\Models\Hos\HosViolation;
use App\Models\Hos\HosConfiguration;
use App\Models\Admin\Vehicle\VehicleDriverAssignment;
use Illuminate\Support\Facades\Log;

class HosAlertService
{
    protected HosCalculationService $calculationService;

    public function __construct(HosCalculationService $calculationService)
    {
        $this->calculationService = $calculationService;
    }

    /**
     * Check thresholds and return alerts.
     *
     * @param int $driverId
     * @param array $totals
     * @param HosConfiguration $config
     * @return array
     */
    public function checkThresholds(int $driverId, array $totals, HosConfiguration $config): array
    {
        $alerts = [];
        
        $totalDutyMinutes = $totals['driving_minutes'] + $totals['on_duty_minutes'];
        
        // Check driving hours
        $remainingDriving = $config->max_driving_minutes - $totals['driving_minutes'];
        if ($remainingDriving <= 0) {
            $alerts[] = [
                'type' => 'violation',
                'category' => 'driving',
                'message' => 'You have exceeded your driving hour limit',
                'exceeded_by' => abs($remainingDriving),
                'exceeded_by_formatted' => HosTimeFormatter::formatTime(abs($remainingDriving)),
            ];
        } elseif ($remainingDriving <= $config->warning_threshold_minutes) {
            $alerts[] = [
                'type' => 'warning',
                'category' => 'driving',
                'message' => "You have {$this->formatMinutes($remainingDriving)} of driving time remaining",
                'remaining' => $remainingDriving,
                'remaining_formatted' => HosTimeFormatter::formatTime($remainingDriving),
            ];
        }

        // Check duty hours
        $remainingDuty = $config->max_duty_minutes - $totalDutyMinutes;
        if ($remainingDuty <= 0) {
            $alerts[] = [
                'type' => 'violation',
                'category' => 'duty',
                'message' => 'You have exceeded your total duty hour limit',
                'exceeded_by' => abs($remainingDuty),
                'exceeded_by_formatted' => HosTimeFormatter::formatTime(abs($remainingDuty)),
            ];
        } elseif ($remainingDuty <= $config->warning_threshold_minutes) {
            $alerts[] = [
                'type' => 'warning',
                'category' => 'duty',
                'message' => "You have {$this->formatMinutes($remainingDuty)} of duty time remaining",
                'remaining' => $remainingDuty,
                'remaining_formatted' => HosTimeFormatter::formatTime($remainingDuty),
            ];
        }

        return $alerts;
    }

    /**
     * Check thresholds and send alerts for a driver.
     *
     * @param int $driverId
     * @param Carbon|null $date
     * @return array
     */
    public function checkAndSendAlerts(int $driverId, ?Carbon $date = null): array
    {
        $date = $date ?? Carbon::today();
        $driver = UserDriverDetail::findOrFail($driverId);
        $config = HosConfiguration::getForCarrier($driver->carrier_id);
        
        $totals = $this->calculationService->calculateDailyTotals($driverId, $date);
        $alerts = $this->checkThresholds($driverId, $totals, $config);

        // Create violations for exceeded limits
        foreach ($alerts as $alert) {
            if ($alert['type'] === 'violation') {
                $this->createViolationIfNotExists($driverId, $date, $alert);
            }
        }

        // Send notifications (can be extended for push notifications)
        foreach ($alerts as $alert) {
            $this->sendAlert($driverId, $alert['type'], $alert);
        }

        return $alerts;
    }

    /**
     * Create a violation record.
     *
     * @param int $driverId
     * @param string $type
     * @param float $hoursExceeded
     * @param int|null $entryId
     * @return HosViolation|null
     */
    public function createViolation(int $driverId, string $type, float $hoursExceeded, ?int $entryId = null): ?HosViolation
    {
        $driver = UserDriverDetail::findOrFail($driverId);
        $vehicleId = $this->getDriverVehicleId($driverId);

        if (!$vehicleId) {
            Log::error('Cannot create HOS violation: No vehicle found', [
                'driver_id' => $driverId,
                'carrier_id' => $driver->carrier_id,
                'violation_type' => $type,
            ]);
            return null;
        }

        return HosViolation::create([
            'user_driver_detail_id' => $driverId,
            'carrier_id' => $driver->carrier_id,
            'vehicle_id' => $vehicleId,
            'violation_type' => $type,
            'violation_date' => Carbon::today(),
            'hours_exceeded' => $hoursExceeded,
            'hos_entry_id' => $entryId,
            'acknowledged' => false,
        ]);
    }

    /**
     * Create violation if it doesn't already exist for today.
     *
     * @param int $driverId
     * @param Carbon $date
     * @param array $alert
     * @return HosViolation|null
     */
    protected function createViolationIfNotExists(int $driverId, Carbon $date, array $alert): ?HosViolation
    {
        $violationType = $alert['category'] === 'driving' 
            ? HosViolation::TYPE_DRIVING_LIMIT_EXCEEDED 
            : HosViolation::TYPE_DUTY_LIMIT_EXCEEDED;

        // Check if violation already exists for today
        $existingViolation = HosViolation::forDriver($driverId)
            ->where('violation_type', $violationType)
            ->whereDate('violation_date', $date)
            ->first();

        if ($existingViolation) {
            // Update exceeded hours if it increased
            $hoursExceeded = HosTimeFormatter::minutesToHours($alert['exceeded_by']);
            if ($hoursExceeded > $existingViolation->hours_exceeded) {
                $existingViolation->update(['hours_exceeded' => $hoursExceeded]);
            }
            return $existingViolation;
        }

        $driver = UserDriverDetail::findOrFail($driverId);
        $vehicleId = $this->getDriverVehicleId($driverId);

        if (!$vehicleId) {
            Log::error('Cannot create HOS violation: No vehicle found', [
                'driver_id' => $driverId,
                'carrier_id' => $driver->carrier_id,
                'violation_type' => $violationType,
            ]);
            return null;
        }

        // Get the latest entry that caused the violation
        $latestEntry = HosEntry::forDriver($driverId)
            ->forDate($date)
            ->orderBy('start_time', 'desc')
            ->first();

        return HosViolation::create([
            'user_driver_detail_id' => $driverId,
            'carrier_id' => $driver->carrier_id,
            'vehicle_id' => $vehicleId,
            'violation_type' => $violationType,
            'violation_date' => $date,
            'hours_exceeded' => HosTimeFormatter::minutesToHours($alert['exceeded_by']),
            'hos_entry_id' => $latestEntry?->id,
            'acknowledged' => false,
        ]);
    }

    /**
     * Send an alert to the driver.
     *
     * @param int $driverId
     * @param string $alertType
     * @param array $data
     * @return void
     */
    public function sendAlert(int $driverId, string $alertType, array $data): void
    {
        // Log the alert
        Log::info("HOS Alert for driver {$driverId}", [
            'type' => $alertType,
            'data' => $data,
        ]);

        // TODO: Implement push notifications if needed
        // This could dispatch a job to send push notifications
        // event(new HosAlertEvent($driverId, $alertType, $data));
    }

    /**
     * Get active alerts for a driver.
     *
     * @param int $driverId
     * @return array
     */
    public function getActiveAlerts(int $driverId): array
    {
        $driver = UserDriverDetail::find($driverId);
        
        if (!$driver || !$driver->carrier_id) {
            return [];
        }
        
        $config = HosConfiguration::getForCarrier($driver->carrier_id);
        $totals = $this->calculationService->calculateDailyTotals($driverId, Carbon::today());
        
        return $this->checkThresholds($driverId, $totals, $config);
    }

    /**
     * Get violations for a driver in a date range.
     *
     * @param int $driverId
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getDriverViolations(int $driverId, Carbon $startDate, Carbon $endDate)
    {
        return HosViolation::forDriver($driverId)
            ->forDateRange($startDate, $endDate)
            ->orderBy('violation_date', 'desc')
            ->get();
    }

    /**
     * Get violations for a carrier in a date range.
     *
     * @param int $carrierId
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCarrierViolations(int $carrierId, Carbon $startDate, Carbon $endDate)
    {
        return HosViolation::forCarrier($carrierId)
            ->forDateRange($startDate, $endDate)
            ->with(['driver', 'vehicle'])
            ->orderBy('violation_date', 'desc')
            ->get();
    }

    /**
     * Format minutes to readable string.
     *
     * @param int $minutes
     * @return string
     */
    protected function formatMinutes(int $minutes): string
    {
        return HosTimeFormatter::formatTime($minutes);
    }

    /**
     * Get vehicle ID for a driver.
     * Priority: 1) Active trip, 2) Current vehicle assignment, 3) Latest entry vehicle
     * 
     * @param int $driverId
     * @return int|null
     */
    protected function getDriverVehicleId(int $driverId): ?int
    {
        // Try to get vehicle from active trip first
        $activeTrip = Trip::where('user_driver_detail_id', $driverId)
            ->where('status', Trip::STATUS_IN_PROGRESS)
            ->first();
        
        if ($activeTrip && $activeTrip->vehicle_id) {
            return $activeTrip->vehicle_id;
        }

        // Fallback to current vehicle assignment
        $assignment = VehicleDriverAssignment::forDriver($driverId)
            ->current()
            ->first();
        
        if ($assignment && $assignment->vehicle_id) {
            return $assignment->vehicle_id;
        }

        // Last resort: get from latest entry
        $latestEntry = HosEntry::forDriver($driverId)
            ->whereNotNull('vehicle_id')
            ->orderBy('start_time', 'desc')
            ->first();

        return $latestEntry?->vehicle_id;
    }
}
