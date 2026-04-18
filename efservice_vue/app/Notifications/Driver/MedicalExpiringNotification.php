<?php

namespace App\Notifications\Driver;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MedicalExpiringNotification extends Notification implements ShouldQueue
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
            ->subject($urgency . 'Your Medical Card is Expiring Soon')
            ->greeting('Hello ' . ($notifiable->name ?? 'Driver') . ',')
            ->line('Your medical card is expiring soon.')
            ->line('**Expiration Date:** ' . ($this->expirationDate ?? 'N/A'))
            ->line('**Days Remaining:** ' . $this->daysRemaining)
            ->action('View Medical Record', route('driver.medical.index'))
            ->line('Please schedule a medical examination before expiration.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Medical Card Expiring Soon',
            'message' => 'Your medical card expires in ' . $this->daysRemaining . ' days.',
            'type' => 'medical_expiring',
            'category' => 'personal_compliance',
            'icon' => 'Heart',
            'urgent' => $this->daysRemaining <= 7,
            'days_remaining' => $this->daysRemaining,
            'expiration_date' => $this->expirationDate,
            'url' => route('driver.medical.index'),
        ];
    }
}
