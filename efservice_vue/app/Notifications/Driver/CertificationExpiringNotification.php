<?php

namespace App\Notifications\Driver;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CertificationExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected int $daysRemaining;
    protected string $certificationType;
    protected ?string $certificationName;
    protected ?string $expirationDate;

    public function __construct(int $daysRemaining, string $certificationType, ?string $certificationName = null, ?string $expirationDate = null)
    {
        $this->daysRemaining = $daysRemaining;
        $this->certificationType = $certificationType;
        $this->certificationName = $certificationName;
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
            ->subject($urgency . 'Your ' . $this->certificationType . ' is Expiring Soon')
            ->greeting('Hello ' . ($notifiable->name ?? 'Driver') . ',')
            ->line('Your ' . $this->certificationType . ' is expiring soon.')
            ->when($this->certificationName, fn($mail) => $mail->line('**Name:** ' . $this->certificationName))
            ->line('**Expiration Date:** ' . ($this->expirationDate ?? 'N/A'))
            ->line('**Days Remaining:** ' . $this->daysRemaining)
            ->action('View Dashboard', url('/driver/dashboard'))
            ->line('Please renew before expiration.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->certificationType . ' Expiring Soon',
            'message' => 'Your ' . $this->certificationType .
                ($this->certificationName ? ' (' . $this->certificationName . ')' : '') .
                ' expires in ' . $this->daysRemaining . ' days.',
            'type' => 'certification_expiring',
            'category' => 'personal',
            'icon' => 'Award',
            'urgent' => $this->daysRemaining <= 7,
            'days_remaining' => $this->daysRemaining,
            'certification_type' => $this->certificationType,
            'certification_name' => $this->certificationName,
            'expiration_date' => $this->expirationDate,
            'url' => '/driver/dashboard',
        ];
    }
}
