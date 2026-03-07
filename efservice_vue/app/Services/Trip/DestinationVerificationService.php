<?php

namespace App\Services\Trip;

use App\Models\Trip;
use App\Models\TripGpsPoint;

class DestinationVerificationService
{
    private const ARRIVAL_THRESHOLD_METERS = 500;

    /**
     * Verify if the driver arrived at the destination.
     */
    public function verifyArrival(Trip $trip): array
    {
        // Get last GPS point
        $lastGpsPoint = TripGpsPoint::where('trip_id', $trip->id)
            ->orderBy('recorded_at', 'desc')
            ->first();

        if (!$lastGpsPoint) {
            return [
                'verified' => false,
                'has_gps_data' => false,
                'message' => 'No hay datos GPS disponibles para este viaje',
                'distance_meters' => null,
                'final_location' => null,
                'google_maps_url' => null,
            ];
        }

        // Check if trip has destination coordinates
        if (!$trip->destination_latitude || !$trip->destination_longitude) {
            return [
                'verified' => false,
                'has_gps_data' => true,
                'message' => 'El destino no tiene coordenadas registradas',
                'distance_meters' => null,
                'final_location' => [
                    'lat' => (float) $lastGpsPoint->latitude,
                    'lng' => (float) $lastGpsPoint->longitude,
                ],
                'google_maps_url' => $this->buildGoogleMapsUrl($lastGpsPoint->latitude, $lastGpsPoint->longitude),
            ];
        }

        // Calculate distance using Haversine formula
        $distance = $this->calculateDistance(
            $lastGpsPoint->latitude,
            $lastGpsPoint->longitude,
            $trip->destination_latitude,
            $trip->destination_longitude
        );

        $arrived = $distance <= self::ARRIVAL_THRESHOLD_METERS;

        return [
            'verified' => true,
            'has_gps_data' => true,
            'arrived' => $arrived,
            'distance_meters' => round($distance),
            'distance_formatted' => $this->formatDistance($distance),
            'threshold_meters' => self::ARRIVAL_THRESHOLD_METERS,
            'final_location' => [
                'lat' => (float) $lastGpsPoint->latitude,
                'lng' => (float) $lastGpsPoint->longitude,
            ],
            'destination_location' => $trip->destination_coordinates,
            'message' => $arrived 
                ? 'El driver llegó al destino' 
                : 'El driver no llegó al destino programado',
            'google_maps_url' => $this->buildRouteGoogleMapsUrl($trip, $lastGpsPoint),
        ];
    }

    /**
     * Generate Google Maps URL to view a location.
     */
    public function buildGoogleMapsUrl(float $lat, float $lng): string
    {
        return "https://www.google.com/maps?q={$lat},{$lng}";
    }

    /**
     * Generate Google Maps URL showing origin, destination and final location.
     */
    public function buildRouteGoogleMapsUrl(Trip $trip, ?TripGpsPoint $lastPoint = null): string
    {
        if (!$trip->origin_latitude || !$trip->origin_longitude) {
            if ($lastPoint) {
                return $this->buildGoogleMapsUrl($lastPoint->latitude, $lastPoint->longitude);
            }
            return '';
        }

        $origin = "{$trip->origin_latitude},{$trip->origin_longitude}";
        
        if (!$trip->destination_latitude || !$trip->destination_longitude) {
            return "https://www.google.com/maps?q={$origin}";
        }

        $destination = "{$trip->destination_latitude},{$trip->destination_longitude}";
        
        // If there's a final point different from destination, show route with waypoint
        if ($lastPoint && 
            ($lastPoint->latitude != $trip->destination_latitude || 
             $lastPoint->longitude != $trip->destination_longitude)) {
            $finalPoint = "{$lastPoint->latitude},{$lastPoint->longitude}";
            return "https://www.google.com/maps/dir/{$origin}/{$finalPoint}/{$destination}";
        }
        
        return "https://www.google.com/maps/dir/{$origin}/{$destination}";
    }

    /**
     * Generate URL to view pause location.
     */
    public function buildPauseLocationUrl(float $lat, float $lng): string
    {
        return $this->buildGoogleMapsUrl($lat, $lng);
    }

    /**
     * Calculate distance between two coordinates using Haversine formula.
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // meters

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
     * Format distance for display.
     */
    private function formatDistance(float $meters): string
    {
        if ($meters < 1000) {
            return round($meters) . ' m';
        }
        return round($meters / 1000, 1) . ' km';
    }
}
