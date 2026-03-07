<?php

namespace App\Notifications\Driver;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $documentType;
    protected ?string $rejectionReason;

    public function __construct(string $documentType, ?string $rejectionReason = null)
    {
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
            ->greeting('Hello ' . ($notifiable->name ?? 'Driver') . ',')
            ->line('Your document has been rejected and requires resubmission.')
            ->line('**Document Type:** ' . $this->documentType)
            ->when($this->rejectionReason, fn($mail) => $mail->line('**Reason:** ' . $this->rejectionReason))
            ->action('Upload New Document', route('driver.documents.create'))
            ->line('Please upload a new document as soon as possible.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Document Rejected',
            'message' => 'Your ' . $this->documentType . ' was rejected. ' . ($this->rejectionReason ?? ''),
            'type' => 'document_rejected',
            'category' => 'documents',
            'icon' => 'XCircle',
            'urgent' => true,
            'document_type' => $this->documentType,
            'rejection_reason' => $this->rejectionReason,
            'url' => route('driver.documents.create'),
        ];
    }
}
