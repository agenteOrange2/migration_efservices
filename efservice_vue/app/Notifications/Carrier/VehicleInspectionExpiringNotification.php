<?php

namespace App\Notifications\Carrier;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Admin\Vehicle\Vehicle;

class VehicleInspectionExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Vehicle $vehicle;
    protected int $daysRemaining;
    protected ?string $expirationDate;

    public function __construct(Vehicle $vehicle, int $daysRemaining, ?string $expirationDate = null)
    {
        $this->vehicle = $vehicle;
        $this->daysRemaining = $daysRemaining;
        $this->expirationDate = $expirationDate;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $urgency = $this->daysRemaining <= 7 ? 'URGENT: ' : '';
        
        return (new MailMessage)
            ->subject($urgency . 'Vehicle Inspection Expiring: Unit #' . ($this->vehicle->company_unit_number ?? $this->vehicle->id))
            ->greeting('Hello,')
            ->line('A vehicle inspection is expiring soon.')
            ->line('**Vehicle:** Unit #' . ($this->vehicle->company_unit_number ?? $this->vehicle->id))
            ->line('**Make/Model:** ' . ($this->vehicle->make ?? '') . ' ' . ($this->vehicle->model ?? ''))
            ->line('**Expiration Date:** ' . ($this->expirationDate ?? 'N/A'))
            ->line('**Days Remaining:** ' . $this->daysRemaining)
            ->action('View Vehicle', route('carrier.vehicles.show', $this->vehicle))
            ->line('Please schedule a new inspection before expiration.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Vehicle Inspection Expiring',
            'message' => 'Unit #' . ($this->vehicle->company_unit_number ?? $this->vehicle->id) . ' inspection expires in ' . $this->daysRemaining . ' days.',
            'type' => 'vehicle_inspection_expiring',
            'category' => 'vehicle_compliance',
            'icon' => 'ClipboardCheck',
            'urgent' => $this->daysRemaining <= 7,
            'vehicle_id' => $this->vehicle->id,
            'vehicle_unit' => $this->vehicle->company_unit_number ?? $this->vehicle->id,
            'days_remaining' => $this->daysRemaining,
            'expiration_date' => $this->expirationDate,
            'url' => route('carrier.vehicles.show', $this->vehicle),
        ];
    }
}
