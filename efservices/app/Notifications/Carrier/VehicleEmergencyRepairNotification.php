<?php

namespace App\Notifications\Carrier;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Admin\Vehicle\Vehicle;

class VehicleEmergencyRepairNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Vehicle $vehicle;
    protected string $repairDescription;
    protected ?string $driverName;
    protected ?string $location;

    public function __construct(Vehicle $vehicle, string $repairDescription, ?string $driverName = null, ?string $location = null)
    {
        $this->vehicle = $vehicle;
        $this->repairDescription = $repairDescription;
        $this->driverName = $driverName;
        $this->location = $location;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('URGENT: Emergency Repair Request - ' . $this->vehicle->unit_number)
            ->greeting('Hello,')
            ->line('An emergency repair has been requested for a vehicle.')
            ->line('**Vehicle:** ' . $this->vehicle->unit_number)
            ->when($this->driverName, fn($mail) => $mail->line('**Driver:** ' . $this->driverName))
            ->when($this->location, fn($mail) => $mail->line('**Location:** ' . $this->location))
            ->line('**Issue:** ' . $this->repairDescription)
            ->action('View Details', route('carrier.vehicles.show', $this->vehicle->id))
            ->line('Please respond to this emergency repair request immediately.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Emergency Repair Request',
            'message' => 'Vehicle ' . $this->vehicle->unit_number . ' requires emergency repair: ' . $this->repairDescription,
            'type' => 'vehicle_emergency_repair',
            'category' => 'vehicles',
            'icon' => 'AlertTriangle',
            'urgent' => true,
            'vehicle_id' => $this->vehicle->id,
            'vehicle_unit' => $this->vehicle->unit_number,
            'repair_description' => $this->repairDescription,
            'driver_name' => $this->driverName,
            'location' => $this->location,
            'url' => route('carrier.vehicles.show', $this->vehicle->id),
        ];
    }
}
