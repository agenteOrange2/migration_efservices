<?php

namespace App\Notifications\Carrier;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Admin\Driver\Driver;

class DriverDocumentRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Driver $driver;
    protected string $documentType;
    protected ?string $rejectionReason;

    public function __construct(Driver $driver, string $documentType, ?string $rejectionReason = null)
    {
        $this->driver = $driver;
        $this->documentType = $documentType;
        $this->rejectionReason = $rejectionReason;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Document Rejected: ' . $this->documentType)
            ->greeting('Hello,')
            ->line('A driver document has been rejected.')
            ->line('**Driver:** ' . $this->driver->full_name)
            ->line('**Document Type:** ' . $this->documentType)
            ->when($this->rejectionReason, fn($mail) => $mail->line('**Reason:** ' . $this->rejectionReason))
            ->action('View Driver', route('carrier.drivers.show', $this->driver->id))
            ->line('The driver has been notified to upload a new document.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Document Rejected',
            'message' => $this->driver->full_name . '\'s ' . $this->documentType . ' was rejected.',
            'type' => 'driver_document_rejected',
            'category' => 'drivers',
            'icon' => 'FileX',
            'driver_id' => $this->driver->id,
            'driver_name' => $this->driver->full_name,
            'document_type' => $this->documentType,
            'rejection_reason' => $this->rejectionReason,
            'url' => route('carrier.drivers.show', $this->driver->id),
        ];
    }
}
