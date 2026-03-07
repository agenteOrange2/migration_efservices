<?php

namespace App\Notifications\Driver;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrainingAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected int $trainingId;
    protected string $trainingTitle;
    protected ?string $dueDate;

    public function __construct(int $trainingId, string $trainingTitle, ?string $dueDate = null)
    {
        $this->trainingId = $trainingId;
        $this->trainingTitle = $trainingTitle;
        $this->dueDate = $dueDate;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Training Assigned: ' . $this->trainingTitle)
            ->greeting('Hello ' . ($notifiable->name ?? 'Driver') . ',')
            ->line('A new training has been assigned to you.')
            ->line('**Training:** ' . $this->trainingTitle)
            ->when($this->dueDate, fn($mail) => $mail->line('**Due Date:** ' . $this->dueDate))
            ->action('Start Training', route('driver.trainings.show', $this->trainingId))
            ->line('Please complete this training before the due date.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New Training Assigned',
            'message' => 'You have been assigned: ' . $this->trainingTitle,
            'type' => 'training_assigned',
            'category' => 'trainings',
            'icon' => 'GraduationCap',
            'training_id' => $this->trainingId,
            'training_title' => $this->trainingTitle,
            'due_date' => $this->dueDate,
            'url' => route('driver.trainings.show', $this->trainingId),
        ];
    }
}
