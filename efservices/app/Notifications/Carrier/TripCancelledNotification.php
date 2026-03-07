<?php

namespace App\Notifications\Carrier;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Admin\Driver\Driver;

class TripCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected int $tripId;
    protected Driver $driver;
    protected ?string $reason;

    public function __construct(int $tripId, Driver $driver, ?string $reason = null)
    {
        $this->tripId = $tripId;
        $this->driver = $driver;
        $this->reason = $reason;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $message = $this->driver->full_name . ' has cancelled trip #' . $this->tripId;
        if ($this->reason) {
            $message .= '. Reason: ' . $this->reason;
        }
        
        return [
            'title' => 'Trip Cancelled',
            'message' => $message,
            'type' => 'trip_cancelled',
            'category' => 'trips',
            'icon' => 'XCircle',
            'trip_id' => $this->tripId,
            'driver_id' => $this->driver->id,
            'driver_name' => $this->driver->full_name,
            'reason' => $this->reason,
            'url' => route('carrier.trips.show', $this->tripId),
        ];
    }
}
