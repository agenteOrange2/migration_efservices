<?php

namespace App\Notifications\Driver;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DriverHosViolationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $violationType;
    protected ?string $details;

    public function __construct(string $violationType, ?string $details = null)
    {
        $this->violationType = $violationType;
        $this->details = $details;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'HOS Violation',
            'message' => 'You have a ' . $this->violationType . ' violation. ' . ($this->details ?? ''),
            'type' => 'driver_hos_violation',
            'category' => 'hos',
            'icon' => 'AlertOctagon',
            'urgent' => true,
            'violation_type' => $this->violationType,
            'details' => $this->details,
            'url' => route('driver.hos.dashboard'),
        ];
    }
}
