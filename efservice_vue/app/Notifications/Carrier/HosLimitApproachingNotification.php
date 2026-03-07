<?php

namespace App\Notifications\Carrier;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Admin\Driver\Driver;

class HosLimitApproachingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Driver $driver;
    protected string $limitType;
    protected int $minutesRemaining;

    public function __construct(Driver $driver, string $limitType, int $minutesRemaining)
    {
        $this->driver = $driver;
        $this->limitType = $limitType;
        $this->minutesRemaining = $minutesRemaining;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $hours = floor($this->minutesRemaining / 60);
        $mins = $this->minutesRemaining % 60;
        $timeStr = $hours > 0 ? "{$hours}h {$mins}m" : "{$mins} minutes";
        
        return (new MailMessage)
            ->subject('HOS Limit Approaching: ' . $this->driver->full_name)
            ->greeting('Hello,')
            ->line('A driver is approaching their HOS limit.')
            ->line('**Driver:** ' . $this->driver->full_name)
            ->line('**Limit Type:** ' . $this->limitType)
            ->line('**Time Remaining:** ' . $timeStr)
            ->action('View Driver HOS', route('carrier.drivers.show', $this->driver->id))
            ->line('Please ensure the driver takes appropriate action.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'HOS Limit Approaching',
            'message' => $this->driver->full_name . ' has ' . $this->minutesRemaining . ' minutes until ' . $this->limitType . ' limit.',
            'type' => 'hos_limit_approaching',
            'category' => 'hos',
            'icon' => 'Clock',
            'urgent' => $this->minutesRemaining <= 30,
            'driver_id' => $this->driver->id,
            'driver_name' => $this->driver->full_name,
            'limit_type' => $this->limitType,
            'minutes_remaining' => $this->minutesRemaining,
            'url' => route('carrier.drivers.show', $this->driver->id),
        ];
    }
}
