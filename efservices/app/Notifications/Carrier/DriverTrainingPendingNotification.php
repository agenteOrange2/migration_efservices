<?php

namespace App\Notifications\Carrier;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Admin\Driver\Driver;

class DriverTrainingPendingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Driver $driver;
    protected string $trainingTitle;
    protected ?string $dueDate;

    public function __construct(Driver $driver, string $trainingTitle, ?string $dueDate = null)
    {
        $this->driver = $driver;
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
            ->subject('Driver Training Pending: ' . $this->driver->full_name)
            ->greeting('Hello,')
            ->line('A driver has pending training that requires completion.')
            ->line('**Driver:** ' . $this->driver->full_name)
            ->line('**Training:** ' . $this->trainingTitle)
            ->when($this->dueDate, fn($mail) => $mail->line('**Due Date:** ' . $this->dueDate))
            ->action('View Driver', route('carrier.drivers.show', $this->driver->id))
            ->line('Please follow up with the driver to ensure training completion.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Driver Training Pending',
            'message' => $this->driver->full_name . ' has pending training: ' . $this->trainingTitle,
            'type' => 'driver_training_pending',
            'category' => 'drivers',
            'icon' => 'GraduationCap',
            'driver_id' => $this->driver->id,
            'driver_name' => $this->driver->full_name,
            'training_title' => $this->trainingTitle,
            'due_date' => $this->dueDate,
            'url' => route('carrier.drivers.show', $this->driver->id),
        ];
    }
}
