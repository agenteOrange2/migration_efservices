<?php

namespace App\Notifications\Carrier;

use App\Models\User;
use App\Models\Carrier;
use App\Models\UserDriverDetail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DriverApplicationApprovedNotification extends Notification implements ShouldQueue
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

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Driver Application Approved: ' . $this->driver->name)
            ->greeting('Hello,')
            ->line('A driver application has been approved for your company.')
            ->line('**Driver:** ' . $this->driver->name)
            ->line('**Carrier:** ' . $this->carrier->name)
            ->line('The driver is now active and ready to work.')
            ->action('View Driver', url('/carrier/drivers/' . $this->driverDetail->id))
            ->line('Thank you for using our platform.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Driver Application Approved',
            'message' => $this->driver->name . '\'s application has been approved.',
            'type' => 'driver_application_approved',
            'category' => 'drivers',
            'icon' => 'CircleCheckBig',
            'driver_id' => $this->driverDetail->id,
            'driver_name' => $this->driver->name,
            'carrier_id' => $this->carrier->id,
            'url' => '/carrier/drivers/' . $this->driverDetail->id,
        ];
    }
}
