<?php

namespace App\Services\Hos;

use Carbon\Carbon;
use App\Models\Trip;
use App\Models\User;
use App\Models\Hos\HosEntry;
use App\Models\Hos\HosViolation;
use App\Models\Hos\HosConfiguration;
use App\Models\UserDriverDetail;
use App\Notifications\HosLimitWarningNotification;
use App\Notifications\HosAutoStopNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class HosAutoStopService
{
    protected HosCalculationService $calculationService;
    protected HosFMCSAService $fmcsaService;

    public function __construct(
        HosCalculationService $calculationService,
        HosFMCSAService $fmcsaService
    ) {
        $this->calculationService = $calculationService;
        $this->fmcsaService = $fmcsaService;
    }

    /**
     * Check all active trips and auto-stop if HOS limits exceeded.
     *
     * @return array Results of the check
     */
    public function checkAndAutoStopActiveTrips(): array
    {
        $results = [
            'checked' => 0,
            'warnings_sent' => 0,
            'auto_stopped' => 0,
            'errors' => 0,
            'details' => [],
        ];

        // Get all active trips (in_progress) - these need HOS checking
        $activeTrips = Trip::where('status', Trip::STATUS_IN_PROGRESS)
            ->whereNotNull('user_driver_detail_id')
            ->with(['driver.user', 'carrier'])
            ->get();

        foreach ($activeTrips as $trip) {
            try {
                $results['checked']++;
                $result = $this->checkTripHosLimits($trip);
                
                if ($result['action_taken']) {
                    $results['details'][] = $result;
                    
                    if ($result['action'] === 'warning') {
                        $results['warnings_sent']++;
                    } elseif ($result['action'] === 'auto_stop') {
                        $results['auto_stopped']++;
                    }
                }
            } catch (\Exception $e) {
                $results['errors']++;
                Log::error('HOS Auto-Stop check error', [
                    'trip_id' => $trip->id,
                    'driver_id' => $trip->user_driver_detail_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Check HOS limits for a specific trip and take action if needed.
     *
     * @param Trip $trip
     * @return array
     */
    public function checkTripHosLimits(Trip $trip): array
    {
        $driverId = $trip->user_driver_detail_id;
        $carrierId = $trip->carrier_id;
        
        $result = [
            'trip_id' => $trip->id,
            'driver_id' => $driverId,
            'action_taken' => false,
            'action' => null,
            'reason' => null,
        ];

        // Get HOS status
        $drivingLimit = $this->fmcsaService->checkDrivingLimit($driverId, $carrierId);
        $dutyPeriod = $this->fmcsaService->checkDutyPeriod($driverId, $carrierId);
        $weeklyLimit = $this->fmcsaService->checkWeeklyCycle($driverId, $carrierId);

        // Check for exceeded limits - AUTO STOP
        if ($drivingLimit['is_exceeded']) {
            $this->autoStopTrip($trip, 'driving_limit_exceeded', 
                'You have exceeded the 12-hour daily driving limit. Your trip has been automatically paused.');
            $result['action_taken'] = true;
            $result['action'] = 'auto_stop';
            $result['reason'] = 'driving_limit_exceeded';
            return $result;
        }

        if ($dutyPeriod['is_exceeded']) {
            $this->autoStopTrip($trip, 'duty_period_exceeded',
                'You have exceeded the 14-hour duty period. Your trip has been automatically paused.');
            $result['action_taken'] = true;
            $result['action'] = 'auto_stop';
            $result['reason'] = 'duty_period_exceeded';
            return $result;
        }

        if ($weeklyLimit['is_over_limit']) {
            $this->autoStopTrip($trip, 'weekly_limit_exceeded',
                'You have exceeded your weekly cycle limit. Your trip has been automatically paused.');
            $result['action_taken'] = true;
            $result['action'] = 'auto_stop';
            $result['reason'] = 'weekly_limit_exceeded';
            return $result;
        }

        // Check for critical warnings (30 minutes or less remaining)
        $warningThresholdMinutes = 30;

        if ($drivingLimit['remaining_minutes'] <= $warningThresholdMinutes && $drivingLimit['remaining_minutes'] > 0) {
            $this->sendWarningNotification($trip, 'driving_limit_warning',
                "Warning: You have only {$drivingLimit['remaining_minutes']} minutes of driving time remaining today.");
            $result['action_taken'] = true;
            $result['action'] = 'warning';
            $result['reason'] = 'driving_limit_warning';
            return $result;
        }

        if ($dutyPeriod['remaining_minutes'] <= $warningThresholdMinutes && $dutyPeriod['remaining_minutes'] > 0) {
            $this->sendWarningNotification($trip, 'duty_period_warning',
                "Warning: You have only {$dutyPeriod['remaining_minutes']} minutes left in your duty period.");
            $result['action_taken'] = true;
            $result['action'] = 'warning';
            $result['reason'] = 'duty_period_warning';
            return $result;
        }

        return $result;
    }

    /**
     * Auto-stop a trip due to HOS limit exceeded.
     * This will PAUSE the trip automatically and create violation records.
     * The driver can end the trip anytime, but cannot resume until penalty is served.
     *
     * @param Trip $trip
     * @param string $reason
     * @param string $message
     * @return void
     */
    protected function autoStopTrip(Trip $trip, string $reason, string $message): void
    {
        DB::transaction(function () use ($trip, $reason, $message) {
            $driverId = $trip->user_driver_detail_id;
            $now = now();

            // Determine penalty hours based on violation type
            $penaltyHours = match ($reason) {
                'weekly_limit_exceeded' => 34, // 34-hour reset for weekly
                default => 10, // 10-hour rest for daily limits
            };

            // Close the current driving HOS entry
            HosEntry::where('user_driver_detail_id', $driverId)
                ->where('status', HosEntry::STATUS_ON_DUTY_DRIVING)
                ->whereNull('end_time')
                ->update(['end_time' => $now]);

            // Create an off-duty entry (mandatory rest)
            HosEntry::create([
                'user_driver_detail_id' => $driverId,
                'vehicle_id' => $trip->vehicle_id,
                'carrier_id' => $trip->carrier_id,
                'trip_id' => $trip->id,
                'status' => HosEntry::STATUS_OFF_DUTY,
                'start_time' => $now,
                'date' => today(),
                'is_manual_entry' => true,
                'manual_entry_reason' => "Auto-stop: {$reason}",
                'formatted_address' => 'Auto-stopped by system due to HOS limit',
            ]);

            // PAUSE the trip - driver can end it but cannot resume until penalty served
            $trip->update([
                'status' => Trip::STATUS_PAUSED,
                'has_violations' => true,
                'auto_stopped_at' => $now,
                'auto_stop_reason' => $reason,
                'hos_penalty_end_time' => $now->copy()->addHours($penaltyHours),
                'penalty_notes' => ($trip->penalty_notes ? $trip->penalty_notes . "\n" : '') . 
                    "[{$now->format('Y-m-d H:i:s')}] Auto-paused: {$reason} - Must rest {$penaltyHours}h before resuming. Can end trip anytime.",
            ]);

            // Create violation record
            $this->createAutoStopViolation($driverId, $trip->carrier_id, $trip->vehicle_id, $reason);

            // Send notification to driver
            $this->sendAutoStopNotification($trip, $reason, $message);

            Log::info('Trip auto-paused due to HOS limit exceeded', [
                'trip_id' => $trip->id,
                'driver_id' => $driverId,
                'reason' => $reason,
                'new_status' => Trip::STATUS_PAUSED,
                'penalty_hours' => $penaltyHours,
                'penalty_end_time' => $now->copy()->addHours($penaltyHours)->toDateTimeString(),
            ]);
        });
    }

    /**
     * Create a violation record for auto-stop.
     *
     * @param int $driverId
     * @param int $carrierId
     * @param int|null $vehicleId
     * @param string $reason
     * @return HosViolation|null
     */
    protected function createAutoStopViolation(int $driverId, int $carrierId, ?int $vehicleId, string $reason): ?HosViolation
    {
        $violationType = match ($reason) {
            'driving_limit_exceeded' => HosViolation::TYPE_DRIVING_LIMIT_EXCEEDED,
            'duty_period_exceeded' => HosViolation::TYPE_DUTY_PERIOD_EXCEEDED,
            'weekly_limit_exceeded' => HosViolation::TYPE_WEEKLY_CYCLE_EXCEEDED,
            default => HosViolation::TYPE_DRIVING_LIMIT_EXCEEDED,
        };

        // Check if violation already exists today
        $existingViolation = HosViolation::where('user_driver_detail_id', $driverId)
            ->where('violation_type', $violationType)
            ->whereDate('violation_date', today())
            ->first();

        if ($existingViolation) {
            // Update to mark as auto-stopped
            $existingViolation->update([
                'penalty_notes' => ($existingViolation->penalty_notes ? $existingViolation->penalty_notes . "\n" : '') .
                    "Trip auto-stopped at " . now()->format('H:i:s'),
            ]);
            return $existingViolation;
        }

        return HosViolation::create([
            'user_driver_detail_id' => $driverId,
            'carrier_id' => $carrierId,
            'vehicle_id' => $vehicleId,
            'violation_type' => $violationType,
            'violation_severity' => HosViolation::SEVERITY_CRITICAL,
            'violation_date' => today(),
            'hours_exceeded' => 0,
            'has_penalty' => true,
            'penalty_type' => 'mandatory_rest',
            'penalty_hours' => $reason === 'weekly_limit_exceeded' ? 34 : 10,
            'penalty_start' => now(),
            'penalty_end' => now()->addHours($reason === 'weekly_limit_exceeded' ? 34 : 10),
            'penalty_notes' => "Auto-stopped by system: {$reason}",
        ]);
    }

    /**
     * Send auto-stop notification to driver and carrier.
     *
     * @param Trip $trip
     * @param string $reason
     * @param string $message
     * @return void
     */
    protected function sendAutoStopNotification(Trip $trip, string $reason, string $message): void
    {
        try {
            $driver = $trip->driver;
            $user = $driver?->user;

            if ($user) {
                $user->notify(new HosAutoStopNotification($trip, $reason, $message));
            }

            // Also notify carrier admin users
            $carrierUsers = User::whereHas('carrierDetails', function ($q) use ($trip) {
                $q->where('carrier_id', $trip->carrier_id);
            })->get();

            foreach ($carrierUsers as $carrierUser) {
                $carrierUser->notify(new HosAutoStopNotification($trip, $reason, $message, true));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send auto-stop notification', [
                'trip_id' => $trip->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send warning notification to driver.
     *
     * @param Trip $trip
     * @param string $type
     * @param string $message
     * @return void
     */
    protected function sendWarningNotification(Trip $trip, string $type, string $message): void
    {
        try {
            // Check if we already sent a warning recently (within 15 minutes)
            $recentWarning = DB::table('hos_warnings_sent')
                ->where('driver_id', $trip->user_driver_detail_id)
                ->where('warning_type', $type)
                ->where('sent_at', '>=', now()->subMinutes(15))
                ->first();

            if ($recentWarning) {
                return; // Don't spam warnings
            }

            $driver = $trip->driver;
            $user = $driver?->user;

            if ($user) {
                $user->notify(new HosLimitWarningNotification($trip, $type, $message));

                // Record the warning
                DB::table('hos_warnings_sent')->insert([
                    'driver_id' => $trip->user_driver_detail_id,
                    'warning_type' => $type,
                    'message' => $message,
                    'sent_at' => now(),
                ]);
            }
        } catch (\Exception $e) {
            // Table might not exist yet, just log
            Log::warning('Failed to send HOS warning notification', [
                'trip_id' => $trip->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get remaining time before auto-stop for a trip.
     *
     * @param Trip $trip
     * @return array
     */
    public function getRemainingTimeBeforeAutoStop(Trip $trip): array
    {
        $driverId = $trip->user_driver_detail_id;
        $carrierId = $trip->carrier_id;

        $drivingLimit = $this->fmcsaService->checkDrivingLimit($driverId, $carrierId);
        $dutyPeriod = $this->fmcsaService->checkDutyPeriod($driverId, $carrierId);

        $minRemaining = min(
            $drivingLimit['remaining_minutes'],
            $dutyPeriod['remaining_minutes']
        );

        $limitType = $drivingLimit['remaining_minutes'] <= $dutyPeriod['remaining_minutes']
            ? 'driving'
            : 'duty_period';

        return [
            'minutes_remaining' => max(0, $minRemaining),
            'hours_remaining' => round(max(0, $minRemaining) / 60, 2),
            'limit_type' => $limitType,
            'will_auto_stop' => $minRemaining <= 0,
            'is_critical' => $minRemaining <= 30 && $minRemaining > 0,
            'driving_remaining' => $drivingLimit['remaining_minutes'],
            'duty_remaining' => $dutyPeriod['remaining_minutes'],
        ];
    }
}
