<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Admin\Driver\DriverTraining;

class TrainingDueSoonNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $driverTraining;
    protected $daysRemaining;

    /**
     * Create a new notification instance.
     */
    public function __construct(DriverTraining $driverTraining, int $daysRemaining)
    {
        $this->driverTraining = $driverTraining;
        $this->daysRemaining = $daysRemaining;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $training = $this->driverTraining->training;
        $url = route('driver.trainings.show', $this->driverTraining->id);

        return (new MailMessage)
            ->subject('Training Due Soon: ' . $training->title)
            ->greeting('Hello ' . ($notifiable->name ?? 'Driver') . ',')
            ->line('This is a reminder that you have a training assignment due soon.')
            ->line('**Training:** ' . $training->title)
            ->line('**Due Date:** ' . $this->driverTraining->due_date->format('F d, Y'))
            ->line('**Days Remaining:** ' . $this->daysRemaining . ' ' . ($this->daysRemaining === 1 ? 'day' : 'days'))
            ->action('View Training', $url)
            ->line('Please complete this training before the due date to stay compliant.')
            ->salutation('Best regards, ' . config('app.name'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $training = $this->driverTraining->training;

        return [
            'type' => 'training_due_soon',
            'training_id' => $training->id,
            'training_assignment_id' => $this->driverTraining->id,
            'title' => $training->title,
            'due_date' => $this->driverTraining->due_date->toDateTimeString(),
            'days_remaining' => $this->daysRemaining,
            'message' => "Training \"{$training->title}\" is due in {$this->daysRemaining} " . ($this->daysRemaining === 1 ? 'day' : 'days'),
            'url' => route('driver.trainings.show', $this->driverTraining->id),
        ];
    }
}

