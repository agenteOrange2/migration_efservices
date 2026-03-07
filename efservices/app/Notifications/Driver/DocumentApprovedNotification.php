<?php

namespace App\Notifications\Driver;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $documentType;
    protected ?string $documentName;

    public function __construct(string $documentType, ?string $documentName = null)
    {
        $this->documentType = $documentType;
        $this->documentName = $documentName;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Document Approved',
            'message' => 'Your ' . $this->documentType . ' has been approved.',
            'type' => 'document_approved',
            'category' => 'documents',
            'icon' => 'CheckCircle',
            'document_type' => $this->documentType,
            'document_name' => $this->documentName,
            'url' => route('driver.documents.index'),
        ];
    }
}
