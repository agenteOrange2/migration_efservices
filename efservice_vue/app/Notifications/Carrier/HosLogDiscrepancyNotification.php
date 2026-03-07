<?php

namespace App\Notifications\Carrier;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Admin\Driver\Driver;

class HosLogDiscrepancyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Driver $driver;
    protected string $discrepancyType;
    protected ?string $details;
    protected ?string $logDate;

    public function __construct(Driver $driver, string $discrepancyType, ?string $details = null, ?string $logDate = null)
    {
        $this->driver = $driver;
        $this->discrepancyType = $discrepancyType;
        $this->details = $details;
        $this->logDate = $logDate;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('HOS Log Discrepancy: ' . $this->driver->full_name)
            ->greeting('Hello,')
            ->line('A discrepancy has been found in a driver\'s HOS log.')
            ->line('**Driver:** ' . $this->driver->full_name)
            ->line('**Discrepancy Type:** ' . $this->discrepancyType)
            ->when($this->logDate, fn($mail) => $mail->line('**Log Date:** ' . $this->logDate))
            ->when($this->details, fn($mail) => $mail->line('**Details:** ' . $this->details))
            ->action('Review HOS Logs', route('carrier.drivers.show', $this->driver->id))
            ->line('Please review and address this discrepancy.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'HOS Log Discrepancy',
            'message' => $this->driver->full_name . ' has a log discrepancy: ' . $this->discrepancyType,
            'type' => 'hos_log_discrepancy',
            'category' => 'hos',
            'icon' => 'AlertCircle',
            'driver_id' => $this->driver->id,
            'driver_name' => $this->driver->full_name,
            'discrepancy_type' => $this->discrepancyType,
            'details' => $this->details,
            'log_date' => $this->logDate,
            'url' => route('carrier.drivers.show', $this->driver->id),
        ];
    }
}
