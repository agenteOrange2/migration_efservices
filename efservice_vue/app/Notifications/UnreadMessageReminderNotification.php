<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UnreadMessageReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected int $unreadCount;

    public function __construct(int $unreadCount)
    {
        $this->unreadCount = $unreadCount;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('You have ' . $this->unreadCount . ' unread messages')
            ->greeting('Hello ' . ($notifiable->name ?? '') . ',')
            ->line('You have ' . $this->unreadCount . ' unread message(s) waiting for your attention.')
            ->line('Please log in to view and respond to your messages.')
            ->salutation('Best regards, ' . config('app.name'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Unread Messages Reminder',
            'message' => 'You have ' . $this->unreadCount . ' unread message(s).',
            'type' => 'unread_message_reminder',
            'category' => 'messages',
            'icon' => 'Mail',
            'unread_count' => $this->unreadCount,
        ];
    }
}
