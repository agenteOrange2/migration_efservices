<?php

namespace App\Notifications\Carrier;

use App\Models\UserDriverDetail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DriverMedicalExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected UserDriverDetail $driver;
    protected int $daysRemaining;
    protected ?string $expirationDate;

    public function __construct(UserDriverDetail $driver, int $daysRemaining, ?string $expirationDate = null)
    {
        $this->driver = $driver;
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
        $driverName = $this->driver->user->name ?? 'Unknown';
        
        return (new MailMessage)
            ->subject($urgency . 'Driver Medical Card Expiring: ' . $driverName)
            ->greeting('Hello,')
            ->line('A driver\'s medical card is expiring soon and requires attention.')
            ->line('**Driver:** ' . $driverName)
            ->line('**Expiration Date:** ' . ($this->expirationDate ?? 'N/A'))
            ->line('**Days Remaining:** ' . $this->daysRemaining)
            ->action('View Driver', url('/carrier/drivers/' . $this->driver->id))
            ->line('Please ensure the driver renews their medical card before expiration.');
    }

    public function toArray(object $notifiable): array
    {
        $driverName = $this->driver->user->name ?? 'Unknown';
        
        return [
            'title' => 'Driver Medical Card Expiring',
            'message' => $driverName . '\'s medical card expires in ' . $this->daysRemaining . ' days.',
            'type' => 'driver_medical_expiring',
            'category' => 'drivers',
            'icon' => 'Heart',
            'urgent' => $this->daysRemaining <= 7,
            'driver_id' => $this->driver->id,
            'driver_name' => $driverName,
            'days_remaining' => $this->daysRemaining,
            'expiration_date' => $this->expirationDate,
            'url' => '/carrier/drivers/' . $this->driver->id,
        ];
    }
}
