<?php

namespace App\Notifications\Carrier;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Admin\Driver\Driver;

class HosLogMissingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Driver $driver;
    protected string $missingDate;

    public function __construct(Driver $driver, string $missingDate)
    {
        $this->driver = $driver;
        $this->missingDate = $missingDate;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Missing HOS Log: ' . $this->driver->full_name)
            ->greeting('Hello,')
            ->line('A driver is missing an HOS log entry.')
            ->line('**Driver:** ' . $this->driver->full_name)
            ->line('**Missing Date:** ' . $this->missingDate)
            ->action('View Driver HOS', route('carrier.drivers.show', $this->driver->id))
            ->line('Please ensure the driver completes their log for this date.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Missing HOS Log',
            'message' => $this->driver->full_name . ' is missing HOS log for ' . $this->missingDate,
            'type' => 'hos_log_missing',
            'category' => 'hos',
            'icon' => 'FileQuestion',
            'driver_id' => $this->driver->id,
            'driver_name' => $this->driver->full_name,
            'missing_date' => $this->missingDate,
            'url' => route('carrier.drivers.show', $this->driver->id),
        ];
    }
}
