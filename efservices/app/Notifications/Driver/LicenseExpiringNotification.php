<?php

namespace App\Notifications\Driver;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LicenseExpiringNotification extends Notification implements ShouldQueue
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
            ->subject($urgency . 'Your License is Expiring Soon')
            ->greeting('Hello ' . ($notifiable->name ?? 'Driver') . ',')
            ->line('Your driver\'s license is expiring soon.')
            ->line('**Expiration Date:** ' . ($this->expirationDate ?? 'N/A'))
            ->line('**Days Remaining:** ' . $this->daysRemaining)
            ->action('View License', route('driver.licenses.index'))
            ->line('Please renew your license before expiration to continue driving.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'License Expiring Soon',
            'message' => 'Your license expires in ' . $this->daysRemaining . ' days.',
            'type' => 'license_expiring',
            'category' => 'personal',
            'icon' => 'CreditCard',
            'urgent' => $this->daysRemaining <= 7,
            'days_remaining' => $this->daysRemaining,
            'expiration_date' => $this->expirationDate,
            'url' => route('driver.licenses.index'),
        ];
    }
}
