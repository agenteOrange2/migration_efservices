<?php

namespace App\Services\Trip;

use App\Models\Trip;
use App\Models\TripPause;
use App\Models\Hos\HosEntry;
use Illuminate\Database\Eloquent\Collection;

class TripPauseService
{
    /**
     * Create a new pause for a trip.
     */
    public function createPause(
        Trip $trip,
        ?array $location = null,
        ?string $reason = null,
        ?int $forcedBy = null
    ): TripPause {
        // Get the current HOS entry of on_duty_not_driving if exists
        $hosEntry = HosEntry::where('trip_id', $trip->id)
            ->where('status', 'on_duty_not_driving')
            ->whereNull('end_time')
            ->latest('start_time')
            ->first();

        return TripPause::create([
            'trip_id' => $trip->id,
            'started_at' => now(),
            'latitude' => $location['latitude'] ?? null,
            'longitude' => $location['longitude'] ?? null,
            'formatted_address' => $location['address'] ?? null,
            'reason' => $reason,
            'forced_by' => $forcedBy,
            'hos_entry_id' => $hosEntry?->id,
        ]);
    }

    /**
     * End the active pause for a trip.
     */
    public function endPause(Trip $trip): ?TripPause
    {
        $activePause = TripPause::where('trip_id', $trip->id)
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first();

        if ($activePause) {
            $activePause->update(['ended_at' => now()]);
        }

        return $activePause;
    }

    /**
     * Get all pauses for a trip.
     */
    public function getTripPauses(Trip $trip): Collection
    {
        return TripPause::where('trip_id', $trip->id)
            ->orderBy('started_at')
            ->get();
    }

    /**
     * Get total pause duration in minutes for a trip.
     */
    public function getTotalPauseDuration(Trip $trip): int
    {
        return TripPause::where('trip_id', $trip->id)
            ->whereNotNull('ended_at')
            ->get()
            ->sum('duration_minutes');
    }

    /**
     * Check if trip has an active pause.
     */
    public function hasActivePause(Trip $trip): bool
    {
        return TripPause::where('trip_id', $trip->id)
            ->whereNull('ended_at')
            ->exists();
    }

    /**
     * Get the active pause for a trip.
     */
    public function getActivePause(Trip $trip): ?TripPause
    {
        return TripPause::where('trip_id', $trip->id)
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first();
    }
}
