<?php

namespace App\Services\Trip;

use App\Models\Trip;
use App\Models\TripGpsPoint;
use Illuminate\Support\Collection;

class TripGpsTrackingService
{
    /**
     * Capture a GPS point for a trip.
     */
    public function captureGpsPoint(Trip $trip, array $gpsData): TripGpsPoint
    {
        return TripGpsPoint::create([
            'trip_id' => $trip->id,
            'latitude' => $gpsData['latitude'],
            'longitude' => $gpsData['longitude'],
            'speed' => $gpsData['speed'] ?? null,
            'heading' => $gpsData['heading'] ?? null,
            'formatted_address' => $gpsData['formatted_address'] ?? null,
            'recorded_at' => $gpsData['recorded_at'] ?? now(),
        ]);
    }

    /**
     * Get the latest GPS position for a trip.
     */
    public function getLatestPosition(Trip $trip): ?TripGpsPoint
    {
        return TripGpsPoint::where('trip_id', $trip->id)
            ->orderBy('recorded_at', 'desc')
            ->first();
    }

    /**
     * Get all GPS points for a trip (the route).
     */
    public function getTripRoute(Trip $trip): Collection
    {
        return TripGpsPoint::where('trip_id', $trip->id)
            ->orderBy('recorded_at', 'asc')
            ->get();
    }

    /**
     * Calculate actual distance traveled based on GPS points.
     */
    public function calculateActualDistance(Trip $trip): float
    {
        $points = $this->getTripRoute($trip);
        
        if ($points->count() < 2) {
            return 0;
        }

        $totalDistance = 0;
        $previousPoint = null;

        foreach ($points as $point) {
            if ($previousPoint) {
                $totalDistance += $this->calculateDistanceBetweenPoints(
                    $previousPoint->latitude,
                    $previousPoint->longitude,
                    $point->latitude,
                    $point->longitude
                );
            }
            $previousPoint = $point;
        }

        return round($totalDistance, 2);
    }

    /**
     * Get GPS points within a time range.
     */
    public function getPointsBetween(Trip $trip, $startTime, $endTime): Collection
    {
        return TripGpsPoint::where('trip_id', $trip->id)
            ->whereBetween('recorded_at', [$startTime, $endTime])
            ->orderBy('recorded_at', 'asc')
            ->get();
    }

    /**
     * Get stationary periods (where speed was 0).
     */
    public function getStationaryPeriods(Trip $trip, int $minMinutes = 5): array
    {
        $points = $this->getTripRoute($trip);
        $stationaryPeriods = [];
        $currentPeriod = null;

        foreach ($points as $point) {
            if ($point->isStationary()) {
                if (!$currentPeriod) {
                    $currentPeriod = [
                        'start' => $point->recorded_at,
                        'start_location' => $point->coordinates,
                        'points' => [],
                    ];
                }
                $currentPeriod['points'][] = $point;
                $currentPeriod['end'] = $point->recorded_at;
            } else {
                if ($currentPeriod) {
                    $duration = $currentPeriod['start']->diffInMinutes($currentPeriod['end']);
                    if ($duration >= $minMinutes) {
                        $currentPeriod['duration_minutes'] = $duration;
                        $stationaryPeriods[] = $currentPeriod;
                    }
                    $currentPeriod = null;
                }
            }
        }

        // Check if we ended in a stationary period
        if ($currentPeriod) {
            $duration = $currentPeriod['start']->diffInMinutes($currentPeriod['end'] ?? now());
            if ($duration >= $minMinutes) {
                $currentPeriod['duration_minutes'] = $duration;
                $stationaryPeriods[] = $currentPeriod;
            }
        }

        return $stationaryPeriods;
    }

    /**
     * Get average speed for a trip.
     */
    public function getAverageSpeed(Trip $trip): float
    {
        $points = TripGpsPoint::where('trip_id', $trip->id)
            ->whereNotNull('speed')
            ->where('speed', '>', 0)
            ->get();

        if ($points->isEmpty()) {
            return 0;
        }

        return round($points->avg('speed'), 1);
    }

    /**
     * Get maximum speed recorded during trip.
     */
    public function getMaxSpeed(Trip $trip): float
    {
        return TripGpsPoint::where('trip_id', $trip->id)
            ->max('speed') ?? 0;
    }

    /**
     * Update trip's actual duration based on GPS data.
     */
    public function updateTripDuration(Trip $trip): void
    {
        $points = $this->getTripRoute($trip);
        
        if ($points->count() < 2) {
            return;
        }

        $firstPoint = $points->first();
        $lastPoint = $points->last();
        
        $durationMinutes = $firstPoint->recorded_at->diffInMinutes($lastPoint->recorded_at);
        
        $trip->update([
            'actual_duration_minutes' => $durationMinutes,
        ]);
    }

    /**
     * Calculate distance between two GPS coordinates using Haversine formula.
     * Returns distance in miles.
     */
    protected function calculateDistanceBetweenPoints(
        float $lat1, 
        float $lon1, 
        float $lat2, 
        float $lon2
    ): float {
        $earthRadius = 3959; // Earth's radius in miles

        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLon = deg2rad($lon2 - $lon1);

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
             cos($lat1Rad) * cos($lat2Rad) *
             sin($deltaLon / 2) * sin($deltaLon / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Get trip statistics.
     */
    public function getTripStatistics(Trip $trip): array
    {
        $points = $this->getTripRoute($trip);
        
        return [
            'total_points' => $points->count(),
            'total_distance_miles' => $this->calculateActualDistance($trip),
            'average_speed_mph' => $this->getAverageSpeed($trip),
            'max_speed_mph' => $this->getMaxSpeed($trip),
            'stationary_periods' => count($this->getStationaryPeriods($trip)),
            'duration_minutes' => $trip->actual_duration_minutes,
        ];
    }
}
