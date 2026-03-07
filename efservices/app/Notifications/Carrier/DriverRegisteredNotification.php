<?php

namespace App\Notifications\Carrier;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Admin\Driver\Driver;

class DriverRegisteredNotification extends Notification implements ShouldQueue
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
            ->subject('New Driver Registration: ' . $this->driver->full_name)
            ->greeting('Hello,')
            ->line('A new driver has registered with your company.')
            ->line('**Driver:** ' . $this->driver->full_name)
            ->line('**Email:** ' . $this->driver->email)
            ->line('**Phone:** ' . ($this->driver->phone ?? 'Not provided'))
            ->action('View Driver', route('carrier.drivers.show', $this->driver->id))
            ->line('Please review the driver\'s application and documents.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New Driver Registered',
            'message' => $this->driver->full_name . ' has registered as a new driver.',
            'type' => 'driver_registered',
            'category' => 'drivers',
            'icon' => 'UserPlus',
            'driver_id' => $this->driver->id,
            'driver_name' => $this->driver->full_name,
            'url' => route('carrier.drivers.show', $this->driver->id),
        ];
    }
}
