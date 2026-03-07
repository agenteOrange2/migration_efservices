<?php

namespace App\Services\Hos;

use App\Models\Hos\HosViolation;
use App\Models\Hos\HosEntry;
use App\Models\Trip;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class HosViolationForgivenessService
{
    /**
     * Forgive a violation and optionally adjust trip end time.
     *
     * @param HosViolation $violation
     * @param int $userId
     * @param string $reason
     * @param Carbon|null $adjustedEndTime
     * @return HosViolation
     * @throws ValidationException
     */
    public function forgiveViolation(
        HosViolation $violation,
        int $userId,
        string $reason,
        ?Carbon $adjustedEndTime = null
    ): HosViolation {
        // Validate that violation can be forgiven
        if ($violation->isForgiven()) {
            throw ValidationException::withMessages([
                'violation' => ['This violation has already been forgiven.'],
            ]);
        }

        if (empty($reason) || strlen($reason) < 10) {
            throw ValidationException::withMessages([
                'forgiveness_reason' => ['A detailed justification (at least 10 characters) is required.'],
            ]);
        }

        // If adjusting end time, validate it
        if ($adjustedEndTime) {
            if ($adjustedEndTime->isFuture()) {
                throw ValidationException::withMessages([
                    'adjusted_end_time' => ['The adjusted end time cannot be in the future.'],
                ]);
            }

            // If trip exists, validate adjusted time is after trip start
            if ($violation->trip && $violation->trip->actual_start_time) {
                if ($adjustedEndTime->lt($violation->trip->actual_start_time)) {
                    throw ValidationException::withMessages([
                        'adjusted_end_time' => ['The adjusted end time cannot be before the trip start time.'],
                    ]);
                }
            }
        }

        return DB::transaction(function () use ($violation, $userId, $reason, $adjustedEndTime) {
            // 1. Forgive the violation
            $violation->forgive($userId, $reason, $adjustedEndTime);

            // 2. If this is a "forgot to close trip" violation with adjusted time
            if ($this->shouldAdjustTripTime($violation, $adjustedEndTime)) {
                $this->adjustTripEndTime($violation->trip, $adjustedEndTime, $userId);
                $this->recalculateHosEntries($violation, $adjustedEndTime);
            }

            // 3. Clear any blocking penalties on the driver
            $this->clearDriverBlockingStatus($violation);

            Log::info('Violation forgiven successfully', [
                'violation_id' => $violation->id,
                'forgiven_by' => $userId,
                'adjusted_end_time' => $adjustedEndTime?->toDateTimeString(),
            ]);

            return $violation->fresh();
        });
    }

    /**
     * Check if we should adjust trip time.
     */
    protected function shouldAdjustTripTime(HosViolation $violation, ?Carbon $adjustedEndTime): bool
    {
        if (!$adjustedEndTime) {
            return false;
        }

        if (!$violation->trip) {
            return false;
        }

        // Adjust time for forgot to close trip violations
        $adjustableTypes = [
            HosViolation::TYPE_FORGOT_TO_CLOSE_TRIP,
            HosViolation::TYPE_DRIVING_LIMIT_EXCEEDED,
            HosViolation::TYPE_DUTY_PERIOD_EXCEEDED,
        ];

        return in_array($violation->violation_type, $adjustableTypes);
    }

    /**
     * Adjust trip end time.
     */
    protected function adjustTripEndTime(Trip $trip, Carbon $adjustedEndTime, int $userId): void
    {
        $originalEndTime = $trip->actual_end_time ?? $trip->auto_stopped_at;

        // Calculate new duration
        $actualDuration = null;
        if ($trip->actual_start_time) {
            $actualDuration = $trip->actual_start_time->diffInMinutes($adjustedEndTime);
        }

        $adjustmentNote = "[" . now()->format('Y-m-d H:i:s') . "] End time adjusted via violation forgiveness. " .
            "Original: " . ($originalEndTime?->format('Y-m-d H:i:s') ?? 'N/A') . " → " .
            "Adjusted: " . $adjustedEndTime->format('Y-m-d H:i:s');

        $trip->update([
            'actual_end_time' => $adjustedEndTime,
            'actual_duration_minutes' => $actualDuration,
            'updated_by' => $userId,
            'notes' => ($trip->notes ? $trip->notes . "\n" : '') . $adjustmentNote,
        ]);

        Log::info('Trip end time adjusted', [
            'trip_id' => $trip->id,
            'original_end_time' => $originalEndTime?->toDateTimeString(),
            'adjusted_end_time' => $adjustedEndTime->toDateTimeString(),
        ]);
    }

    /**
     * Recalculate HOS entries based on adjusted end time.
     */
    protected function recalculateHosEntries(HosViolation $violation, Carbon $adjustedEndTime): void
    {
        $trip = $violation->trip;
        $driverId = $violation->user_driver_detail_id;

        // Find ghost log entries for this trip
        $ghostEntries = HosEntry::where('trip_id', $trip->id)
            ->where('user_driver_detail_id', $driverId)
            ->where('is_ghost_log', true)
            ->get();

        foreach ($ghostEntries as $entry) {
            // If the adjusted end time is before the entry end time, update it
            if ($entry->end_time && $entry->end_time->gt($adjustedEndTime)) {
                $entry->update([
                    'end_time' => $adjustedEndTime,
                    'is_ghost_log' => false, // Clear ghost log flag since time is now corrected
                    'ghost_log_reason' => ($entry->ghost_log_reason ?? '') . ' (Time corrected via forgiveness)',
                ]);

                Log::info('HOS entry adjusted (ghost log)', [
                    'entry_id' => $entry->id,
                    'new_end_time' => $adjustedEndTime->toDateTimeString(),
                ]);
            }
        }

        // Also update the last driving entry for this trip
        $lastDrivingEntry = HosEntry::where('trip_id', $trip->id)
            ->where('user_driver_detail_id', $driverId)
            ->where('status', HosEntry::STATUS_ON_DUTY_DRIVING)
            ->orderBy('start_time', 'desc')
            ->first();

        if ($lastDrivingEntry) {
            // If entry is open or ends after adjusted time, close it at adjusted time
            if (!$lastDrivingEntry->end_time || $lastDrivingEntry->end_time->gt($adjustedEndTime)) {
                // Only update if start time is before adjusted end time
                if ($lastDrivingEntry->start_time->lt($adjustedEndTime)) {
                    $lastDrivingEntry->update([
                        'end_time' => $adjustedEndTime,
                    ]);

                    Log::info('Last driving entry adjusted', [
                        'entry_id' => $lastDrivingEntry->id,
                        'new_end_time' => $adjustedEndTime->toDateTimeString(),
                    ]);
                }
            }
        }
    }

    /**
     * Clear any blocking status on the driver.
     */
    protected function clearDriverBlockingStatus(HosViolation $violation): void
    {
        $trip = $violation->trip;

        if ($trip) {
            $clearNote = "[" . now()->format('Y-m-d H:i:s') . "] Penalty cleared via violation forgiveness.";

            $trip->update([
                'hos_penalty_end_time' => null,
                'penalty_notes' => ($trip->penalty_notes ? $trip->penalty_notes . "\n" : '') . $clearNote,
            ]);

            Log::info('Trip penalty cleared', [
                'trip_id' => $trip->id,
            ]);
        }
    }

    /**
     * Check if a driver is currently blocked due to unforgiven violations.
     */
    public function isDriverBlocked(int $driverId): array
    {
        $blockingViolation = HosViolation::where('user_driver_detail_id', $driverId)
            ->blocking()
            ->first();

        if (!$blockingViolation) {
            return [
                'is_blocked' => false,
                'violation' => null,
                'penalty_end' => null,
                'remaining_minutes' => 0,
            ];
        }

        return [
            'is_blocked' => true,
            'violation' => $blockingViolation,
            'penalty_end' => $blockingViolation->penalty_end,
            'remaining_minutes' => $blockingViolation->remaining_penalty_hours
                ? (int)($blockingViolation->remaining_penalty_hours * 60)
                : 0,
        ];
    }

    /**
     * Get all violations that can be forgiven for a driver.
     */
    public function getForgivableViolations(int $driverId): \Illuminate\Database\Eloquent\Collection
    {
        return HosViolation::where('user_driver_detail_id', $driverId)
            ->where('is_forgiven', false)
            ->where('has_penalty', true)
            ->with(['trip', 'carrier', 'vehicle'])
            ->orderBy('violation_date', 'desc')
            ->get();
    }

    /**
     * Get forgiveness history for a violation.
     */
    public function getForgivenViolationsForDriver(int $driverId): \Illuminate\Database\Eloquent\Collection
    {
        return HosViolation::where('user_driver_detail_id', $driverId)
            ->where('is_forgiven', true)
            ->with(['forgivenByUser', 'trip', 'carrier'])
            ->orderBy('forgiven_at', 'desc')
            ->get();
    }
}
