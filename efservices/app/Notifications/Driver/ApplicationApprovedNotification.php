<?php

namespace App\Notifications\Driver;

use App\Models\Carrier;
use App\Models\UserDriverDetail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected UserDriverDetail $driver;
    protected Carrier $carrier;

    public function __construct(UserDriverDetail $driver, Carrier $carrier)
    {
        $this->driver = $driver;
        $this->carrier = $carrier;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Driver Application Has Been Approved!')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Great news! Your driver application with **' . $this->carrier->name . '** has been approved.')
            ->line('You can now access your driver dashboard and start working.')
            ->action('Go to Dashboard', url('/driver/dashboard'))
            ->line('Welcome aboard!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Application Approved',
            'message' => 'Your driver application with ' . $this->carrier->name . ' has been approved.',
            'type' => 'application_approved',
            'category' => 'drivers',
            'icon' => 'CircleCheckBig',
            'driver_id' => $this->driver->id,
            'carrier_id' => $this->carrier->id,
            'url' => '/driver/dashboard',
        ];
    }
}
