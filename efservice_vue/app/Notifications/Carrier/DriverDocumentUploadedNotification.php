<?php

namespace App\Notifications\Carrier;

use App\Models\User;
use App\Models\Carrier;
use App\Models\UserDriverDetail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DriverDocumentUploadedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected User $driverUser;
    protected Carrier $carrier;
    protected UserDriverDetail $driverDetail;
    protected string $documentType;
    protected ?string $documentName;

    public function __construct(User $driverUser, Carrier $carrier, UserDriverDetail $driverDetail, string $documentType, ?string $documentName = null)
    {
        $this->driverUser = $driverUser;
        $this->carrier = $carrier;
        $this->driverDetail = $driverDetail;
        $this->documentType = $documentType;
        $this->documentName = $documentName;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $isAdmin = method_exists($notifiable, 'hasRole') && $notifiable->hasRole('superadmin');

        return [
            'title' => 'New Document Uploaded',
            'message' => $this->driverUser->name . ' uploaded a ' . $this->documentType . ' document.',
            'type' => 'driver_document_uploaded',
            'category' => 'driver_documents',
            'icon' => 'FileUp',
            'driver_id' => $this->driverDetail->id,
            'driver_name' => $this->driverUser->name,
            'carrier_id' => $this->carrier->id,
            'document_type' => $this->documentType,
            'document_name' => $this->documentName,
            'url' => $isAdmin
                ? route('admin.drivers.show', $this->driverDetail)
                : route('carrier.drivers.show', $this->driverDetail),
        ];
    }
}
