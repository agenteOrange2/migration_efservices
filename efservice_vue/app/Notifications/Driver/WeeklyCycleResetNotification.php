<?php

namespace App\Notifications\Driver;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WeeklyCycleResetNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $resetDate;
    protected ?int $hoursAvailable;

    public function __construct(string $resetDate, ?int $hoursAvailable = null)
    {
        $this->resetDate = $resetDate;
        $this->hoursAvailable = $hoursAvailable;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $message = 'Your weekly cycle has reset on ' . $this->resetDate . '.';
        if ($this->hoursAvailable) {
            $message .= ' You now have ' . $this->hoursAvailable . ' hours available.';
        }
        
        return [
            'title' => 'Weekly Cycle Reset',
            'message' => $message,
            'type' => 'weekly_cycle_reset',
            'category' => 'hos',
            'icon' => 'RefreshCw',
            'reset_date' => $this->resetDate,
            'hours_available' => $this->hoursAvailable,
            'url' => route('driver.hos.dashboard'),
        ];
    }
}
