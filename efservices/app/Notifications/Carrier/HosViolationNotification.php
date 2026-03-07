<?php

namespace App\Notifications\Carrier;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Admin\Driver\Driver;

class HosViolationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Driver $driver;
    protected string $violationType;
    protected string $severity;
    protected ?string $details;

    public function __construct(Driver $driver, string $violationType, string $severity = 'warning', ?string $details = null)
    {
        $this->driver = $driver;
        $this->violationType = $violationType;
        $this->severity = $severity;
        $this->details = $details;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('URGENT: HOS Violation - ' . $this->driver->full_name)
            ->greeting('Hello,')
            ->line('A Hours of Service violation has been detected.')
            ->line('**Driver:** ' . $this->driver->full_name)
            ->line('**Violation Type:** ' . $this->violationType)
            ->line('**Severity:** ' . ucfirst($this->severity))
            ->when($this->details, fn($mail) => $mail->line('**Details:** ' . $this->details))
            ->action('View Driver HOS', route('carrier.drivers.show', $this->driver->id))
            ->line('Please address this violation immediately.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'HOS Violation Detected',
            'message' => $this->driver->full_name . ' has a ' . $this->violationType . ' violation.',
            'type' => 'hos_violation',
            'category' => 'hos',
            'icon' => 'AlertOctagon',
            'urgent' => true,
            'driver_id' => $this->driver->id,
            'driver_name' => $this->driver->full_name,
            'violation_type' => $this->violationType,
            'severity' => $this->severity,
            'details' => $this->details,
            'url' => route('carrier.drivers.show', $this->driver->id),
        ];
    }
}
