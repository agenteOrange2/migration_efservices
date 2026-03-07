<?php

namespace App\Notifications;

use App\Models\Trip;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HosAutoStopNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Trip $trip;
    protected string $reason;
    protected string $message;
    protected bool $isCarrierNotification;

    /**
     * Create a new notification instance.
     */
    public function __construct(Trip $trip, string $reason, string $message, bool $isCarrierNotification = false)
    {
        $this->trip = $trip;
        $this->reason = $reason;
        $this->message = $message;
        $this->isCarrierNotification = $isCarrierNotification;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->isCarrierNotification
            ? "Driver Trip Auto-Stopped: HOS Limit Exceeded"
            : "Your Trip Has Been Auto-Stopped: HOS Limit Exceeded";

        $reasonText = match ($this->reason) {
            'driving_limit_exceeded' => '12-hour daily driving limit exceeded',
            'duty_period_exceeded' => '14-hour duty period limit exceeded',
            'weekly_limit_exceeded' => 'Weekly cycle limit exceeded',
            default => 'HOS limit exceeded',
        };

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting($this->isCarrierNotification ? 'Driver HOS Alert' : 'Important Safety Notice');

        if ($this->isCarrierNotification) {
            $driverName = $this->trip->driver?->user?->name ?? 'Unknown Driver';
            $mail->line("Driver **{$driverName}** has had their trip automatically stopped due to HOS regulations.");
        } else {
            $mail->line('Your trip has been automatically paused for your safety and compliance with HOS regulations.');
        }

        $mail->line("**Reason:** {$reasonText}")
            ->line("**Trip Number:** {$this->trip->trip_number}")
            ->line("**Destination:** {$this->trip->destination_address}")
            ->line('')
            ->line($this->message);

        if (!$this->isCarrierNotification) {
            $mail->line('')
                ->line('**What you need to do:**')
                ->line('1. Stop driving immediately if you have not already')
                ->line('2. Take the required rest period')
                ->line('3. Contact your carrier if you have questions');
        }

        $mail->action('View Trip Details', $this->isCarrierNotification 
            ? route('carrier.trips.show', $this->trip) 
            : route('driver.trips.show', $this->trip));

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'hos_auto_stop',
            'trip_id' => $this->trip->id,
            'trip_number' => $this->trip->trip_number,
            'reason' => $this->reason,
            'message' => $this->message,
            'is_carrier_notification' => $this->isCarrierNotification,
            'driver_id' => $this->trip->user_driver_detail_id,
            'driver_name' => $this->trip->driver?->user?->name,
        ];
    }
}
