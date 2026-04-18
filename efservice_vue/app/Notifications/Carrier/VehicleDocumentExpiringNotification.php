<?php

namespace App\Notifications\Carrier;

use App\Models\Admin\Vehicle\Vehicle;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VehicleDocumentExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Vehicle $vehicle;
    protected int $daysRemaining;
    protected string $documentType;
    protected ?string $documentNumber;
    protected ?string $expirationDate;

    public function __construct(Vehicle $vehicle, int $daysRemaining, string $documentType, ?string $documentNumber = null, ?string $expirationDate = null)
    {
        $this->vehicle = $vehicle;
        $this->daysRemaining = $daysRemaining;
        $this->documentType = $documentType;
        $this->documentNumber = $documentNumber;
        $this->expirationDate = $expirationDate;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $urgency = $this->daysRemaining <= 7 ? 'URGENT: ' : '';
        $unitNumber = $this->vehicle->company_unit_number ?? $this->vehicle->id;

        return (new MailMessage)
            ->subject($urgency . 'Vehicle ' . $this->documentType . ' Expiring: Unit #' . $unitNumber)
            ->greeting('Hello,')
            ->line('A vehicle document is expiring soon and requires attention.')
            ->line('**Vehicle:** Unit #' . $unitNumber . ' (' . ($this->vehicle->make ?? '') . ' ' . ($this->vehicle->model ?? '') . ')')
            ->line('**Document Type:** ' . $this->documentType)
            ->when($this->documentNumber, fn($mail) => $mail->line('**Document Number:** ' . $this->documentNumber))
            ->line('**Expiration Date:** ' . ($this->expirationDate ?? 'N/A'))
            ->line('**Days Remaining:** ' . $this->daysRemaining)
            ->action('View Vehicle', route('carrier.vehicles.show', $this->vehicle))
            ->line('Please renew the document before expiration.');
    }

    public function toArray(object $notifiable): array
    {
        $unitNumber = $this->vehicle->company_unit_number ?? $this->vehicle->id;

        return [
            'title' => 'Vehicle ' . $this->documentType . ' Expiring',
            'message' => 'Unit #' . $unitNumber . ' ' . $this->documentType . ' expires in ' . $this->daysRemaining . ' days.',
            'type' => 'vehicle_document_expiring',
            'category' => 'vehicle_documents',
            'icon' => 'Truck',
            'urgent' => $this->daysRemaining <= 7,
            'vehicle_id' => $this->vehicle->id,
            'vehicle_unit' => $unitNumber,
            'document_type' => $this->documentType,
            'document_number' => $this->documentNumber,
            'days_remaining' => $this->daysRemaining,
            'expiration_date' => $this->expirationDate,
            'url' => route('carrier.vehicles.show', $this->vehicle),
        ];
    }
}
