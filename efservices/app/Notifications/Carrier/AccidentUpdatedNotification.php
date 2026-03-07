<?php

namespace App\Notifications\Carrier;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccidentUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected int $accidentId;
    protected string $updateType;
    protected ?string $details;

    public function __construct(int $accidentId, string $updateType, ?string $details = null)
    {
        $this->accidentId = $accidentId;
        $this->updateType = $updateType;
        $this->details = $details;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Accident Report Updated',
            'message' => 'Accident #' . $this->accidentId . ' has been updated: ' . $this->updateType,
            'type' => 'accident_updated',
            'category' => 'safety',
            'icon' => 'FileEdit',
            'accident_id' => $this->accidentId,
            'update_type' => $this->updateType,
            'details' => $this->details,
            'url' => route('carrier.accidents.show', $this->accidentId),
        ];
    }
}
