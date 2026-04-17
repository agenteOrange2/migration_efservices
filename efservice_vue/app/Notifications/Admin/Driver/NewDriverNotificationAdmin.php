<?php

namespace App\Notifications\Admin\Driver;

use App\Models\Carrier;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewDriverNotificationAdmin extends Notification
{
    use Queueable;

    protected User $user;
    protected Carrier $carrier;

    public function __construct(User $user, Carrier $carrier)
    {
        $this->user = $user;
        $this->carrier = $carrier;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $driverDetailId = $this->user->driverDetails?->id ?? $this->user->id;

        return (new MailMessage)
            ->subject('New Driver Registration')
            ->line('A new driver has been registered.')
            ->line('Driver: ' . $this->user->name)
            ->line('Carrier: ' . $this->carrier->name)
            ->action('View Driver', route('admin.drivers.show', $driverDetailId))
            ->line('The driver will now complete their application process.');
    }

    public function toArray(object $notifiable): array
    {
        return [];
    }
}
