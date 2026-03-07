<?php

namespace App\Notifications\Driver;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TwicExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected int $daysRemaining;
    protected ?string $expirationDate;

    public function __construct(int $daysRemaining, ?string $expirationDate = null)
    {
        $this->daysRemaining = $daysRemaining;
        $this->expirationDate = $expirationDate;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $urgency = $this->daysRemaining <= 7 ? 'URGENT: ' : '';

        return (new MailMessage)
            ->subject($urgency . 'Your TWIC Card is Expiring Soon')
            ->greeting('Hello ' . ($notifiable->name ?? 'Driver') . ',')
            ->line('Your TWIC (Transportation Worker Identification Credential) card is expiring soon.')
            ->line('**Expiration Date:** ' . ($this->expirationDate ?? 'N/A'))
            ->line('**Days Remaining:** ' . $this->daysRemaining)
            ->action('View Dashboard', url('/driver/dashboard'))
            ->line('Please renew your TWIC card before expiration to avoid service interruptions.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'TWIC Card Expiring Soon',
            'message' => 'Your TWIC card expires in ' . $this->daysRemaining . ' days.',
            'type' => 'twic_expiring',
            'category' => 'personal',
            'icon' => 'IdCard',
            'urgent' => $this->daysRemaining <= 7,
            'days_remaining' => $this->daysRemaining,
            'expiration_date' => $this->expirationDate,
            'url' => '/driver/dashboard',
        ];
    }
}
