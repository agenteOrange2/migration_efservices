<?php

namespace App\Services\Trip;

use App\Models\Trip;
use App\Models\TripPause;
use App\Models\Hos\HosEntry;
use Illuminate\Support\Collection;

class TripTimelineService
{
    /**
     * Build a chronological timeline of all trip events.
     */
    public function buildTimeline(Trip $trip): Collection
    {
        $events = collect();

        // Creation event
        $events->push([
            'type' => 'created',
            'timestamp' => $trip->created_at,
            'icon' => 'Plus',
            'color' => 'success',
            'title' => 'Trip Creado',
            'description' => "Trip #{$trip->trip_number} creado",
        ]);

        // Acceptance event
        if ($trip->accepted_at) {
            $events->push([
                'type' => 'accepted',
                'timestamp' => $trip->accepted_at,
                'icon' => 'CheckCircle',
                'color' => 'info',
                'title' => 'Trip Aceptado',
                'description' => 'El driver aceptó el trip',
            ]);
        }

        // Start event
        if ($trip->started_at) {
            $events->push([
                'type' => 'started',
                'timestamp' => $trip->started_at,
                'icon' => 'PlayCircle',
                'color' => 'primary',
                'title' => 'Trip Iniciado',
                'description' => 'El viaje comenzó',
                'location' => $trip->origin_address,
            ]);
        }

        // Pause events
        $pauses = TripPause::where('trip_id', $trip->id)->orderBy('started_at')->get();
        foreach ($pauses as $pause) {
            $events->push([
                'type' => 'paused',
                'timestamp' => $pause->started_at,
                'icon' => 'PauseCircle',
                'color' => 'warning',
                'title' => 'Trip Pausado',
                'description' => $pause->reason ?? 'Pausa sin razón especificada',
                'location' => $pause->formatted_address,
                'coordinates' => $pause->coordinates,
                'forced_by' => $pause->forced_by ? $pause->forcedByUser?->name : null,
                'pause_id' => $pause->id,
            ]);

            if ($pause->ended_at) {
                $events->push([
                    'type' => 'resumed',
                    'timestamp' => $pause->ended_at,
                    'icon' => 'PlayCircle',
                    'color' => 'primary',
                    'title' => 'Trip Reanudado',
                    'description' => "Duración de pausa: {$pause->formatted_duration}",
                    'pause_duration' => $pause->duration_minutes,
                ]);
            }
        }

        // Completion event
        if ($trip->completed_at) {
            $events->push([
                'type' => 'completed',
                'timestamp' => $trip->completed_at,
                'icon' => 'CheckCircle2',
                'color' => 'success',
                'title' => 'Trip Completado',
                'description' => 'El viaje finalizó exitosamente',
                'location' => $trip->destination_address,
            ]);
        }

        // Cancellation event
        if ($trip->status === Trip::STATUS_CANCELLED) {
            $events->push([
                'type' => 'cancelled',
                'timestamp' => $trip->cancelled_at ?? $trip->updated_at,
                'icon' => 'XCircle',
                'color' => 'danger',
                'title' => 'Trip Cancelado',
                'description' => $trip->cancellation_reason ?? 'Sin razón especificada',
            ]);
        }

        // Auto-stop event
        if ($trip->auto_stopped_at) {
            $events->push([
                'type' => 'auto_stopped',
                'timestamp' => $trip->auto_stopped_at,
                'icon' => 'AlertTriangle',
                'color' => 'danger',
                'title' => 'Trip Auto-Detenido',
                'description' => $trip->auto_stop_reason ?? 'Detenido automáticamente por el sistema',
            ]);
        }

        return $events->sortBy('timestamp')->values();
    }

    /**
     * Get HOS entries related to a trip.
     */
    public function getHosEntriesForTrip(Trip $trip): Collection
    {
        return HosEntry::where('trip_id', $trip->id)
            ->orderBy('start_time')
            ->get()
            ->map(function ($entry) {
                return [
                    'id' => $entry->id,
                    'status' => $entry->status,
                    'status_name' => $this->getStatusName($entry->status),
                    'start_time' => $entry->start_time,
                    'end_time' => $entry->end_time,
                    'duration_minutes' => $entry->end_time 
                        ? $entry->start_time->diffInMinutes($entry->end_time)
                        : $entry->start_time->diffInMinutes(now()),
                    'location' => $entry->formatted_address,
                    'is_active' => $entry->end_time === null,
                ];
            });
    }

    /**
     * Get human-readable status name.
     */
    private function getStatusName(string $status): string
    {
        return match ($status) {
            'off_duty' => 'Off Duty',
            'sleeper_berth' => 'Sleeper Berth',
            'on_duty_driving' => 'Driving',
            'on_duty_not_driving' => 'On Duty (Not Driving)',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    /**
     * Get timeline with HOS entries merged.
     */
    public function buildTimelineWithHos(Trip $trip): Collection
    {
        $timeline = $this->buildTimeline($trip);
        $hosEntries = $this->getHosEntriesForTrip($trip);

        // Add HOS entries as timeline events
        foreach ($hosEntries as $entry) {
            $timeline->push([
                'type' => 'hos_entry',
                'timestamp' => $entry['start_time'],
                'icon' => $this->getHosIcon($entry['status']),
                'color' => $this->getHosColor($entry['status']),
                'title' => 'HOS: ' . $entry['status_name'],
                'description' => $entry['location'] ?? 'Sin ubicación',
                'duration_minutes' => $entry['duration_minutes'],
                'is_active' => $entry['is_active'],
            ]);
        }

        return $timeline->sortBy('timestamp')->values();
    }

    /**
     * Get icon for HOS status.
     */
    private function getHosIcon(string $status): string
    {
        return match ($status) {
            'off_duty' => 'Moon',
            'sleeper_berth' => 'Bed',
            'on_duty_driving' => 'Truck',
            'on_duty_not_driving' => 'Clock',
            default => 'Circle',
        };
    }

    /**
     * Get color for HOS status.
     */
    private function getHosColor(string $status): string
    {
        return match ($status) {
            'off_duty' => 'secondary',
            'sleeper_berth' => 'info',
            'on_duty_driving' => 'primary',
            'on_duty_not_driving' => 'warning',
            default => 'secondary',
        };
    }
}
