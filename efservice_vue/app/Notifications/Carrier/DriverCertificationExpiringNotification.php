<?php

namespace App\Notifications\Carrier;

use App\Models\UserDriverDetail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DriverCertificationExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected UserDriverDetail $driver;
    protected int $daysRemaining;
    protected string $certificationType;
    protected ?string $certificationName;
    protected ?string $expirationDate;

    public function __construct(UserDriverDetail $driver, int $daysRemaining, string $certificationType, ?string $certificationName = null, ?string $expirationDate = null)
    {
        $this->driver = $driver;
        $this->daysRemaining = $daysRemaining;
        $this->certificationType = $certificationType;
        $this->certificationName = $certificationName;
        $this->expirationDate = $expirationDate;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $driverName = $this->driver->user->name ?? 'Unknown';

        return [
            'title' => 'Driver ' . $this->certificationType . ' Expiring',
            'message' => $driverName . '\'s ' . $this->certificationType .
                ($this->certificationName ? ' (' . $this->certificationName . ')' : '') .
                ' expires in ' . $this->daysRemaining . ' days.',
            'type' => 'driver_certification_expiring',
            'category' => 'drivers',
            'icon' => 'Award',
            'urgent' => $this->daysRemaining <= 7,
            'driver_id' => $this->driver->id,
            'driver_name' => $driverName,
            'days_remaining' => $this->daysRemaining,
            'certification_type' => $this->certificationType,
            'certification_name' => $this->certificationName,
            'expiration_date' => $this->expirationDate,
            'url' => '/carrier/drivers/' . $this->driver->id,
        ];
    }
}
