<?php

namespace App\Services\Hos;

use App\Models\Trip;
use App\Models\TripGpsPoint;
use App\Models\Hos\HosEntry;
use App\Models\Hos\HosViolation;
use App\Models\Hos\HosConfiguration;
use Carbon\Carbon;

class HosGhostLogDetectionService
{
    /**
     * Check if a driver has a potential ghost log.
     * A ghost log is when the driver is in "driving" status but GPS shows zero speed for extended period.
     */
    public function checkForGhostLog(int $driverId, Trip $trip): bool
    {
        $config = $this->getConfiguration($trip->carrier_id);
        $thresholdMinutes = $config->ghost_log_threshold_minutes ?? 30;

        // Get the current open driving entry
        $currentEntry = HosEntry::forDriver($driverId)
            ->where('status', HosEntry::STATUS_ON_DUTY_DRIVING)
            ->open()
            ->first();

        if (!$currentEntry) {
            return false;
        }

        // Get GPS points for the trip in the last threshold period
        $checkFrom = now()->subMinutes($thresholdMinutes);
        
        $gpsPoints = TripGpsPoint::where('trip_id', $trip->id)
            ->where('recorded_at', '>=', $checkFrom)
            ->orderBy('recorded_at', 'desc')
            ->get();

        if ($gpsPoints->isEmpty()) {
            return false;
        }

        // Check if all GPS points show zero speed
        $allStationary = $gpsPoints->every(function ($point) {
            return $point->speed !== null && $point->speed == 0;
        });

        // Also check if we have enough data points (at least 2 points in the threshold period)
        if ($allStationary && $gpsPoints->count() >= 2) {
            // Calculate the time span of stationary points
            $firstPoint = $gpsPoints->last();
            $lastPoint = $gpsPoints->first();
            
            $stationaryMinutes = $firstPoint->recorded_at->diffInMinutes($lastPoint->recorded_at);
            
            return $stationaryMinutes >= $thresholdMinutes;
        }

        return false;
    }

    /**
     * Process a detected ghost log.
     * - Mark the HOS entry as ghost log
     * - Change driver status to "On Duty - Not Driving"
     * - Create a minor violation
     * - Notify driver and carrier
     */
    public function processGhostLog(int $driverId, HosEntry $entry, string $reason): void
    {
        // 1. Mark the entry as ghost log
        $entry->markAsGhostLog($reason);

        // 2. Close the current driving entry
        $entry->update([
            'end_time' => now(),
        ]);

        // 3. Create a new "On Duty - Not Driving" entry
        HosEntry::create([
            'user_driver_detail_id' => $driverId,
            'vehicle_id' => $entry->vehicle_id,
            'carrier_id' => $entry->carrier_id,
            'trip_id' => $entry->trip_id,
            'status' => HosEntry::STATUS_ON_DUTY_NOT_DRIVING,
            'start_time' => now(),
            'latitude' => $entry->latitude,
            'longitude' => $entry->longitude,
            'formatted_address' => $entry->formatted_address,
            'location_available' => $entry->location_available,
            'is_manual_entry' => false,
            'is_ghost_log' => false,
            'date' => today(),
        ]);

        // 4. Create a minor violation
        $this->createGhostLogViolation($driverId, $entry->carrier_id, $entry->id, $reason);

        // 5. Update trip to mark forgot_to_close
        if ($entry->trip_id) {
            Trip::where('id', $entry->trip_id)->update([
                'forgot_to_close' => true,
            ]);
        }
    }

    /**
     * Get ghost log detection threshold for a carrier.
     */
    public function getGhostLogThreshold(int $carrierId): int
    {
        $config = $this->getConfiguration($carrierId);
        return $config->ghost_log_threshold_minutes ?? 30;
    }

    /**
     * Check if ghost log detection is enabled for a carrier.
     */
    public function isEnabled(int $carrierId): bool
    {
        $config = $this->getConfiguration($carrierId);
        return $config->isGhostLogDetectionEnabled();
    }

    /**
     * Scan all active driving entries for potential ghost logs.
     * This is meant to be called by a scheduled job.
     */
    public function scanForGhostLogs(): array
    {
        $processed = [];

        // Get all active driving entries with associated trips
        $activeEntries = HosEntry::where('status', HosEntry::STATUS_ON_DUTY_DRIVING)
            ->whereNull('end_time')
            ->whereNotNull('trip_id')
            ->where('is_ghost_log', false)
            ->with(['trip', 'driver'])
            ->get();

        foreach ($activeEntries as $entry) {
            if (!$entry->trip) {
                continue;
            }

            // Check if ghost log detection is enabled for this carrier
            if (!$this->isEnabled($entry->carrier_id)) {
                continue;
            }

            // Check for ghost log
            if ($this->checkForGhostLog($entry->user_driver_detail_id, $entry->trip)) {
                $this->processGhostLog(
                    $entry->user_driver_detail_id,
                    $entry,
                    'GPS speed was 0 for ' . $this->getGhostLogThreshold($entry->carrier_id) . ' consecutive minutes'
                );

                $processed[] = [
                    'driver_id' => $entry->user_driver_detail_id,
                    'entry_id' => $entry->id,
                    'trip_id' => $entry->trip_id,
                    'processed_at' => now()->toIso8601String(),
                ];
            }
        }

        return $processed;
    }

    /**
     * Get consecutive stationary minutes for a trip.
     */
    public function getConsecutiveStationaryMinutes(Trip $trip): int
    {
        // Get GPS points ordered by most recent first
        $gpsPoints = TripGpsPoint::where('trip_id', $trip->id)
            ->orderBy('recorded_at', 'desc')
            ->get();

        if ($gpsPoints->isEmpty()) {
            return 0;
        }

        $stationaryMinutes = 0;
        $lastMovingTime = null;

        foreach ($gpsPoints as $point) {
            if ($point->speed !== null && $point->speed > 0) {
                $lastMovingTime = $point->recorded_at;
                break;
            }
        }

        if ($lastMovingTime === null) {
            // All points are stationary, calculate from first point
            $firstPoint = $gpsPoints->last();
            $stationaryMinutes = $firstPoint->recorded_at->diffInMinutes(now());
        } else {
            $stationaryMinutes = $lastMovingTime->diffInMinutes(now());
        }

        return $stationaryMinutes;
    }

    /**
     * Create a ghost log violation.
     */
    protected function createGhostLogViolation(int $driverId, int $carrierId, int $entryId, string $reason): ?HosViolation
    {
        // Get vehicle_id from the entry
        $entry = HosEntry::find($entryId);
        $vehicleId = $entry?->vehicle_id;

        if (!$vehicleId) {
            \Log::error('Cannot create ghost log violation: No vehicle found', [
                'driver_id' => $driverId,
                'carrier_id' => $carrierId,
                'entry_id' => $entryId,
            ]);
            return null;
        }

        return HosViolation::create([
            'user_driver_detail_id' => $driverId,
            'carrier_id' => $carrierId,
            'vehicle_id' => $vehicleId,
            'violation_type' => HosViolation::TYPE_FORGOT_TO_CLOSE_TRIP,
            'violation_severity' => HosViolation::SEVERITY_MINOR,
            'violation_date' => today(),
            'hours_exceeded' => 0,
            'hos_entry_id' => $entryId,
            'has_penalty' => true,
            'penalty_type' => 'warning',
            'penalty_notes' => $reason,
        ]);
    }

    /**
     * Get carrier configuration.
     */
    protected function getConfiguration(int $carrierId): HosConfiguration
    {
        return HosConfiguration::getForCarrier($carrierId);
    }
}
