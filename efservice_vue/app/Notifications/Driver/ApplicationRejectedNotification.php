<?php

namespace App\Notifications\Driver;

use App\Models\Carrier;
use App\Models\UserDriverDetail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected UserDriverDetail $driver;
    protected Carrier $carrier;
    protected string $reason;

    public function __construct(UserDriverDetail $driver, Carrier $carrier, string $reason = '')
    {
        $this->driver = $driver;
        $this->carrier = $carrier;
        $this->reason = $reason;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Driver Application Update')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your driver application with **' . $this->carrier->name . '** has been reviewed.');

        if (!empty($this->reason)) {
            $mail->line('**Reason:** ' . $this->reason);
        }

        return $mail
            ->line('Please contact us if you have any questions or would like to reapply.')
            ->action('View Details', url('/driver/dashboard'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Application Rejected',
            'message' => 'Your driver application with ' . $this->carrier->name . ' has been rejected.' .
                (!empty($this->reason) ? ' Reason: ' . $this->reason : ''),
            'type' => 'application_rejected',
            'category' => 'drivers',
            'icon' => 'XCircle',
            'driver_id' => $this->driver->id,
            'carrier_id' => $this->carrier->id,
            'reason' => $this->reason,
            'url' => '/driver/dashboard',
        ];
    }
}
