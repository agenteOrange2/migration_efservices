<?php

namespace App\Notifications\Carrier;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Admin\Driver\Driver;

class TripStartedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected int $tripId;
    protected Driver $driver;
    protected ?string $origin;
    protected ?string $destination;

    public function __construct(int $tripId, Driver $driver, ?string $origin = null, ?string $destination = null)
    {
        $this->tripId = $tripId;
        $this->driver = $driver;
        $this->origin = $origin;
        $this->destination = $destination;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $route = $this->origin && $this->destination 
            ? $this->origin . ' → ' . $this->destination 
            : 'Trip #' . $this->tripId;
            
        return [
            'title' => 'Trip Started',
            'message' => $this->driver->full_name . ' has started trip: ' . $route,
            'type' => 'trip_started',
            'category' => 'trips',
            'icon' => 'Play',
            'trip_id' => $this->tripId,
            'driver_id' => $this->driver->id,
            'driver_name' => $this->driver->full_name,
            'origin' => $this->origin,
            'destination' => $this->destination,
            'url' => route('carrier.trips.show', $this->tripId),
        ];
    }
}
