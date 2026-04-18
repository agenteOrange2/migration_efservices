<?php

namespace App\Notifications\Admin\Driver;

use App\Models\Carrier;
use App\Models\User;
use App\Models\UserDriverDetail;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DriverApplicationCompletedNotification extends Notification
{
    use Queueable;

    protected User $driver;
    protected Carrier $carrier;
    protected UserDriverDetail $driverDetail;

    public function __construct(User $driver, Carrier $carrier, UserDriverDetail $driverDetail)
    {
        $this->driver = $driver;
        $this->carrier = $carrier;
        $this->driverDetail = $driverDetail;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $isAdmin = method_exists($notifiable, 'hasRole') && $notifiable->hasRole('superadmin');
        $url = $isAdmin
            ? route('admin.drivers.show', $this->driverDetail)
            : route('carrier.drivers.show', $this->driverDetail);

        return (new MailMessage)
            ->subject('Driver Application Completed')
            ->greeting($isAdmin ? 'Hello Admin!' : 'Hello Carrier Team!')
            ->line('A driver has completed their application and is pending review.')
            ->line('Driver Name: ' . $this->driver->name)
            ->line('Carrier: ' . $this->carrier->name)
            ->action($isAdmin ? 'Review Application' : 'View Driver', $url)
            ->line('Please review the application as soon as possible.');
    }

    public function toDatabase($notifiable): array
    {
        $isAdmin = method_exists($notifiable, 'hasRole') && $notifiable->hasRole('superadmin');

        return [
            'driver_id' => $this->driver->id,
            'driver_detail_id' => $this->driverDetail->id,
            'carrier_id' => $this->carrier->id,
            'title' => 'Driver application completed',
            'message' => "Driver {$this->driver->name} from {$this->carrier->name} has completed their application",
            'icon' => 'CircleCheckBig',
            'category' => 'driver_registration',
            'url' => $isAdmin
                ? route('admin.drivers.show', $this->driverDetail)
                : route('carrier.drivers.show', $this->driverDetail),
        ];
    }
}
