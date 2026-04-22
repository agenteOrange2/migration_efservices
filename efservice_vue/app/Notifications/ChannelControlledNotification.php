<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChannelControlledNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Notification $notification,
        private readonly array $channels,
    ) {}

    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        if (method_exists($this->notification, 'toMail')) {
            return $this->notification->toMail($notifiable);
        }

        return (new MailMessage)
            ->subject('Notification')
            ->line('You have a new notification.');
    }

    public function toArray(object $notifiable): array
    {
        $payload = method_exists($this->notification, 'toArray')
            ? (array) $this->notification->toArray($notifiable)
            : [];

        $payload['notification_class'] = get_class($this->notification);

        return $payload;
    }

    public function toDatabase(object $notifiable): array
    {
        if (method_exists($this->notification, 'toDatabase')) {
            $payload = (array) $this->notification->toDatabase($notifiable);
            $payload['notification_class'] = get_class($this->notification);

            return $payload;
        }

        return $this->toArray($notifiable);
    }
}
