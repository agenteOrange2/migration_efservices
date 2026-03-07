<?php

namespace App\Notifications\Driver;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BreakTimeApproachingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected int $minutesUntilBreak;

    public function __construct(int $minutesUntilBreak)
    {
        $this->minutesUntilBreak = $minutesUntilBreak;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Break Time Required Soon',
            'message' => 'You must take a 30-minute break within ' . $this->minutesUntilBreak . ' minutes.',
            'type' => 'break_time_approaching',
            'category' => 'hos',
            'icon' => 'Coffee',
            'urgent' => $this->minutesUntilBreak <= 30,
            'minutes_until_break' => $this->minutesUntilBreak,
            'url' => route('driver.hos.dashboard'),
        ];
    }
}
