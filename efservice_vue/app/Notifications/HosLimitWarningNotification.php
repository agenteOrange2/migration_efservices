<?php

namespace App\Notifications;

use App\Models\Trip;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HosLimitWarningNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Trip $trip;
    protected string $warningType;
    protected string $message;

    /**
     * Create a new notification instance.
     */
    public function __construct(Trip $trip, string $warningType, string $message)
    {
        $this->trip = $trip;
        $this->warningType = $warningType;
        $this->message = $message;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database']; // Only database notification for warnings to avoid spam
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('HOS Warning: Time Limit Approaching')
            ->greeting('Warning!')
            ->line($this->message)
            ->line("**Trip Number:** {$this->trip->trip_number}")
            ->line("**Destination:** {$this->trip->destination_address}")
            ->line('')
            ->line('Please plan to stop soon or take a required break.')
            ->action('View HOS Status', route('driver.hos.dashboard'));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'hos_limit_warning',
            'warning_type' => $this->warningType,
            'trip_id' => $this->trip->id,
            'trip_number' => $this->trip->trip_number,
            'message' => $this->message,
            'driver_id' => $this->trip->user_driver_detail_id,
        ];
    }
}
