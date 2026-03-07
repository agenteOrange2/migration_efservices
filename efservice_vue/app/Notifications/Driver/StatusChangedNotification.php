<?php

namespace App\Notifications\Driver;

use App\Models\Carrier;
use App\Models\UserDriverDetail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected UserDriverDetail $driver;
    protected Carrier $carrier;
    protected string $newStatus;

    public function __construct(UserDriverDetail $driver, Carrier $carrier, string $newStatus)
    {
        $this->driver = $driver;
        $this->carrier = $carrier;
        $this->newStatus = $newStatus;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Driver Status Has Changed')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your driver status with **' . $this->carrier->name . '** has been updated to: **' . $this->newStatus . '**.')
            ->action('View Dashboard', url('/driver/dashboard'))
            ->line('Please contact your carrier if you have any questions.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Status Changed',
            'message' => 'Your driver status has been changed to ' . $this->newStatus . '.',
            'type' => 'driver_status_changed',
            'category' => 'drivers',
            'icon' => 'UserCog',
            'driver_id' => $this->driver->id,
            'carrier_id' => $this->carrier->id,
            'new_status' => $this->newStatus,
            'url' => '/driver/dashboard',
        ];
    }
}
