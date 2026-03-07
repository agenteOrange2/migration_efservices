<?php

namespace App\Notifications\Carrier;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Admin\Driver\Driver;

class DriverApplicationCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Driver $driver;

    public function __construct(Driver $driver)
    {
        $this->driver = $driver;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Driver Application Completed: ' . $this->driver->full_name)
            ->greeting('Hello,')
            ->line('A driver has completed their application and is ready for review.')
            ->line('**Driver:** ' . $this->driver->full_name)
            ->line('**Email:** ' . $this->driver->email)
            ->action('Review Application', route('carrier.drivers.show', $this->driver->id))
            ->line('Please review and approve or reject the application.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Driver Application Completed',
            'message' => $this->driver->full_name . ' has completed their application.',
            'type' => 'driver_application_completed',
            'category' => 'drivers',
            'icon' => 'ClipboardCheck',
            'driver_id' => $this->driver->id,
            'driver_name' => $this->driver->full_name,
            'url' => route('carrier.drivers.show', $this->driver->id),
        ];
    }
}
