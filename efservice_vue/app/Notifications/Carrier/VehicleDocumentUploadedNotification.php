<?php

namespace App\Notifications\Carrier;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Admin\Vehicle\Vehicle;

class VehicleDocumentUploadedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Vehicle $vehicle;
    protected string $documentType;
    protected ?string $documentName;

    public function __construct(Vehicle $vehicle, string $documentType, ?string $documentName = null)
    {
        $this->vehicle = $vehicle;
        $this->documentType = $documentType;
        $this->documentName = $documentName;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $unit = $this->vehicle->company_unit_number ?? $this->vehicle->id;

        return (new MailMessage)
            ->subject('New Vehicle Document Uploaded: ' . $unit)
            ->greeting('Hello,')
            ->line('A new document has been uploaded for a vehicle.')
            ->line('**Vehicle:** ' . $unit)
            ->line('**Document Type:** ' . $this->documentType)
            ->when($this->documentName, fn($mail) => $mail->line('**Document Name:** ' . $this->documentName))
            ->action('View Vehicle', route('carrier.vehicles.show', $this->vehicle->id))
            ->line('Please review the uploaded document.');
    }

    public function toArray(object $notifiable): array
    {
        $unit = $this->vehicle->company_unit_number ?? $this->vehicle->id;

        return [
            'title' => 'Vehicle Document Uploaded',
            'message' => 'New ' . $this->documentType . ' uploaded for vehicle ' . $unit,
            'type' => 'vehicle_document_uploaded',
            'category' => 'vehicle_documents',
            'icon' => 'FileUp',
            'vehicle_id' => $this->vehicle->id,
            'vehicle_unit' => $unit,
            'document_type' => $this->documentType,
            'document_name' => $this->documentName,
            'url' => route('carrier.vehicles.show', $this->vehicle->id),
        ];
    }
}
