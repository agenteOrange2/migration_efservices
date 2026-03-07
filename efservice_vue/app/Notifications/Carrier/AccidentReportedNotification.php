<?php

namespace App\Notifications\Carrier;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Admin\Driver\Driver;

class AccidentReportedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected int $accidentId;
    protected Driver $driver;
    protected string $severity;
    protected ?string $location;
    protected ?string $description;

    public function __construct(int $accidentId, Driver $driver, string $severity, ?string $location = null, ?string $description = null)
    {
        $this->accidentId = $accidentId;
        $this->driver = $driver;
        $this->severity = $severity;
        $this->location = $location;
        $this->description = $description;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('URGENT: Accident Reported - ' . $this->driver->full_name)
            ->greeting('Hello,')
            ->line('An accident has been reported by a driver.')
            ->line('**Driver:** ' . $this->driver->full_name)
            ->line('**Severity:** ' . ucfirst($this->severity))
            ->when($this->location, fn($mail) => $mail->line('**Location:** ' . $this->location))
            ->when($this->description, fn($mail) => $mail->line('**Description:** ' . $this->description))
            ->action('View Accident Report', route('carrier.accidents.show', $this->accidentId))
            ->line('Please respond to this incident immediately.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Accident Reported',
            'message' => $this->driver->full_name . ' reported an accident (' . $this->severity . ' severity)',
            'type' => 'accident_reported',
            'category' => 'safety',
            'icon' => 'AlertOctagon',
            'urgent' => true,
            'accident_id' => $this->accidentId,
            'driver_id' => $this->driver->id,
            'driver_name' => $this->driver->full_name,
            'severity' => $this->severity,
            'location' => $this->location,
            'url' => route('carrier.accidents.show', $this->accidentId),
        ];
    }
}
