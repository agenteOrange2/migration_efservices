<?php

namespace App\Notifications\Carrier;

use App\Models\User;
use App\Models\Carrier;
use App\Models\UserDriverDetail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DriverApplicationRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected User $driver;
    protected Carrier $carrier;
    protected UserDriverDetail $driverDetail;
    protected string $reason;

    public function __construct(User $driver, Carrier $carrier, UserDriverDetail $driverDetail, string $reason = '')
    {
        $this->driver = $driver;
        $this->carrier = $carrier;
        $this->driverDetail = $driverDetail;
        $this->reason = $reason;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Driver Application Rejected: ' . $this->driver->name)
            ->greeting('Hello,')
            ->line('A driver application has been rejected for your company.')
            ->line('**Driver:** ' . $this->driver->name)
            ->line('**Carrier:** ' . $this->carrier->name);

        if (!empty($this->reason)) {
            $mail->line('**Reason:** ' . $this->reason);
        }

        return $mail
            ->action('View Details', url('/carrier/drivers/' . $this->driverDetail->id))
            ->line('Please contact the driver if additional information is needed.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Driver Application Rejected',
            'message' => $this->driver->name . '\'s application has been rejected.' .
                (!empty($this->reason) ? ' Reason: ' . $this->reason : ''),
            'type' => 'driver_application_rejected',
            'category' => 'drivers',
            'icon' => 'XCircle',
            'driver_id' => $this->driverDetail->id,
            'driver_name' => $this->driver->name,
            'carrier_id' => $this->carrier->id,
            'reason' => $this->reason,
            'url' => '/carrier/drivers/' . $this->driverDetail->id,
        ];
    }
}
