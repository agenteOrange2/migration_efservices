<?php

namespace App\Notifications\Carrier;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Admin\Vehicle\Vehicle;

class VehicleMaintenanceDueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Vehicle $vehicle;
    protected string $maintenanceType;
    protected int $daysRemaining;
    protected ?string $dueDate;

    public function __construct(Vehicle $vehicle, string $maintenanceType, int $daysRemaining, ?string $dueDate = null)
    {
        $this->vehicle = $vehicle;
        $this->maintenanceType = $maintenanceType;
        $this->daysRemaining = $daysRemaining;
        $this->dueDate = $dueDate;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Vehicle Maintenance Due: ' . $this->vehicle->unit_number)
            ->greeting('Hello,')
            ->line('A vehicle has scheduled maintenance coming due.')
            ->line('**Vehicle:** ' . $this->vehicle->unit_number)
            ->line('**Maintenance Type:** ' . $this->maintenanceType)
            ->line('**Due Date:** ' . ($this->dueDate ?? 'N/A'))
            ->line('**Days Remaining:** ' . $this->daysRemaining)
            ->action('View Vehicle', route('carrier.vehicles.show', $this->vehicle->id))
            ->line('Please schedule the maintenance service.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Vehicle Maintenance Due',
            'message' => 'Vehicle ' . $this->vehicle->unit_number . ' has ' . $this->maintenanceType . ' due in ' . $this->daysRemaining . ' days.',
            'type' => 'vehicle_maintenance_due',
            'category' => 'vehicles',
            'icon' => 'Wrench',
            'vehicle_id' => $this->vehicle->id,
            'vehicle_unit' => $this->vehicle->unit_number,
            'maintenance_type' => $this->maintenanceType,
            'days_remaining' => $this->daysRemaining,
            'due_date' => $this->dueDate,
            'url' => route('carrier.vehicles.show', $this->vehicle->id),
        ];
    }
}
