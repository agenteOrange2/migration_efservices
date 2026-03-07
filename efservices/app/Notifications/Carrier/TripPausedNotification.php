<?php

namespace App\Notifications\Carrier;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Admin\Driver\Driver;

class TripPausedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected int $tripId;
    protected Driver $driver;
    protected int $pauseDurationMinutes;
    protected ?string $reason;

    public function __construct(int $tripId, Driver $driver, int $pauseDurationMinutes, ?string $reason = null)
    {
        $this->tripId = $tripId;
        $this->driver = $driver;
        $this->pauseDurationMinutes = $pauseDurationMinutes;
        $this->reason = $reason;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $hours = floor($this->pauseDurationMinutes / 60);
        $mins = $this->pauseDurationMinutes % 60;
        $durationStr = $hours > 0 ? "{$hours}h {$mins}m" : "{$mins} minutes";
        
        return [
            'title' => 'Trip Paused',
            'message' => $this->driver->full_name . ' has paused trip #' . $this->tripId . ' for ' . $durationStr,
            'type' => 'trip_paused',
            'category' => 'trips',
            'icon' => 'Pause',
            'trip_id' => $this->tripId,
            'driver_id' => $this->driver->id,
            'driver_name' => $this->driver->full_name,
            'pause_duration_minutes' => $this->pauseDurationMinutes,
            'reason' => $this->reason,
            'url' => route('carrier.trips.show', $this->tripId),
        ];
    }
}
