<?php

namespace App\Notifications\Admin\Driver;

use App\Models\Carrier;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewDriverRegisteredNotification extends Notification
{
    use Queueable;

    protected User $driver;
    protected Carrier $carrier;

    public function __construct(User $driver, Carrier $carrier)
    {
        $this->driver = $driver;
        $this->carrier = $carrier;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $driverDetailId = $this->driver->driverDetails?->id ?? $this->driver->id;

        return (new MailMessage)
            ->subject('New Driver Registration')
            ->greeting('Hello Admin!')
            ->line('A new driver has registered on the platform.')
            ->line('Driver Name: ' . $this->driver->name)
            ->line('Carrier: ' . $this->carrier->name)
            ->action('Review Driver', route('admin.drivers.show', $driverDetailId))
            ->line('Please review their application.');
    }

    public function toDatabase($notifiable): array
    {
        $driverDetailId = $this->driver->driverDetails?->id;

        return [
            'driver_id' => $this->driver->id,
            'driver_detail_id' => $driverDetailId,
            'carrier_id' => $this->carrier->id,
            'title' => 'New driver registered',
            'message' => "New driver registered for {$this->carrier->name}: {$this->driver->name}",
            'icon' => 'UserPlus',
            'category' => 'drivers',
            'url' => $driverDetailId
                ? route('admin.drivers.show', $driverDetailId)
                : route('admin.drivers.index'),
        ];
    }

    public function toArray($notifiable): array
    {
        return [
            'driver_id' => $this->driver->id,
            'driver_detail_id' => $this->driver->driverDetails?->id,
            'driver_name' => $this->driver->name,
            'carrier_id' => $this->carrier->id,
            'carrier_name' => $this->carrier->name,
            'category' => 'drivers',
            'message' => 'New driver registration',
        ];
    }
}
