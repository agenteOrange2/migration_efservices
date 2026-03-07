<?php

namespace App\Notifications\Driver;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DrivingLimitApproachingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected int $minutesRemaining;

    public function __construct(int $minutesRemaining)
    {
        $this->minutesRemaining = $minutesRemaining;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $hours = floor($this->minutesRemaining / 60);
        $mins = $this->minutesRemaining % 60;
        $timeStr = $hours > 0 ? "{$hours}h {$mins}m" : "{$mins} minutes";
        
        return [
            'title' => 'Driving Limit Approaching',
            'message' => 'You have ' . $timeStr . ' of driving time remaining.',
            'type' => 'driving_limit_approaching',
            'category' => 'hos',
            'icon' => 'Clock',
            'urgent' => $this->minutesRemaining <= 30,
            'minutes_remaining' => $this->minutesRemaining,
            'url' => route('driver.hos.dashboard'),
        ];
    }
}
