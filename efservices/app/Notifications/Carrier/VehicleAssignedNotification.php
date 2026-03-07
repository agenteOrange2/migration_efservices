<?php

namespace App\Notifications\Carrier;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Admin\Driver\Driver;

class VehicleAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Vehicle $vehicle;
    protected Driver $driver;

    public function __construct(Vehicle $vehicle, Driver $driver)
    {
        $this->vehicle = $vehicle;
        $this->driver = $driver;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Vehicle Assigned: ' . $this->vehicle->unit_number)
            ->greeting('Hello,')
            ->line('A vehicle has been assigned to a driver.')
            ->line('**Vehicle:** ' . $this->vehicle->unit_number)
            ->line('**Make/Model:** ' . ($this->vehicle->make ?? '') . ' ' . ($this->vehicle->model ?? ''))
            ->line('**Driver:** ' . $this->driver->full_name)
            ->action('View Vehicle', route('carrier.vehicles.show', $this->vehicle->id))
            ->line('The driver has been notified of this assignment.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Vehicle Assigned',
            'message' => 'Vehicle ' . $this->vehicle->unit_number . ' assigned to ' . $this->driver->full_name,
            'type' => 'vehicle_assigned',
            'category' => 'vehicles',
            'icon' => 'Truck',
            'vehicle_id' => $this->vehicle->id,
            'vehicle_unit' => $this->vehicle->unit_number,
            'driver_id' => $this->driver->id,
            'driver_name' => $this->driver->full_name,
            'url' => route('carrier.vehicles.show', $this->vehicle->id),
        ];
    }
}
