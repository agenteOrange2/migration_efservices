<?php

namespace App\Notifications\Carrier;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Admin\Driver\Driver;

class TripCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected int $tripId;
    protected Driver $driver;
    protected ?string $duration;
    protected ?string $origin;
    protected ?string $destination;

    public function __construct(int $tripId, Driver $driver, ?string $duration = null, ?string $origin = null, ?string $destination = null)
    {
        $this->tripId = $tripId;
        $this->driver = $driver;
        $this->duration = $duration;
        $this->origin = $origin;
        $this->destination = $destination;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $message = $this->driver->full_name . ' has completed trip #' . $this->tripId;
        if ($this->duration) {
            $message .= ' (Duration: ' . $this->duration . ')';
        }
            
        return [
            'title' => 'Trip Completed',
            'message' => $message,
            'type' => 'trip_completed',
            'category' => 'trips',
            'icon' => 'CheckCircle',
            'trip_id' => $this->tripId,
            'driver_id' => $this->driver->id,
            'driver_name' => $this->driver->full_name,
            'duration' => $this->duration,
            'origin' => $this->origin,
            'destination' => $this->destination,
            'url' => route('carrier.trips.show', $this->tripId),
        ];
    }
}
