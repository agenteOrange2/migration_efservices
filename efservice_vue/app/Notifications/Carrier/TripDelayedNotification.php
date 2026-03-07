<?php

namespace App\Notifications\Carrier;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Admin\Driver\Driver;

class TripDelayedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected int $tripId;
    protected Driver $driver;
    protected int $delayPercentage;
    protected ?string $estimatedArrival;

    public function __construct(int $tripId, Driver $driver, int $delayPercentage, ?string $estimatedArrival = null)
    {
        $this->tripId = $tripId;
        $this->driver = $driver;
        $this->delayPercentage = $delayPercentage;
        $this->estimatedArrival = $estimatedArrival;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $message = 'Trip #' . $this->tripId . ' is delayed by ' . $this->delayPercentage . '%';
        if ($this->estimatedArrival) {
            $message .= '. New ETA: ' . $this->estimatedArrival;
        }
        
        return [
            'title' => 'Trip Delayed',
            'message' => $message,
            'type' => 'trip_delayed',
            'category' => 'trips',
            'icon' => 'Clock',
            'urgent' => $this->delayPercentage >= 50,
            'trip_id' => $this->tripId,
            'driver_id' => $this->driver->id,
            'driver_name' => $this->driver->full_name,
            'delay_percentage' => $this->delayPercentage,
            'estimated_arrival' => $this->estimatedArrival,
            'url' => route('carrier.trips.show', $this->tripId),
        ];
    }
}
