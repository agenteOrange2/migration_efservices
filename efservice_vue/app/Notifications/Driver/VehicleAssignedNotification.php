<?php

namespace App\Notifications\Driver;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Admin\Vehicle\Vehicle;

class VehicleAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Vehicle $vehicle;

    public function __construct(Vehicle $vehicle)
    {
        $this->vehicle = $vehicle;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Vehicle Assigned: ' . $this->vehicle->unit_number)
            ->greeting('Hello ' . ($notifiable->name ?? 'Driver') . ',')
            ->line('A vehicle has been assigned to you.')
            ->line('**Vehicle:** ' . $this->vehicle->unit_number)
            ->line('**Make/Model:** ' . ($this->vehicle->make ?? '') . ' ' . ($this->vehicle->model ?? ''))
            ->action('View Vehicle', route('driver.vehicles.show', $this->vehicle->id))
            ->line('Please review the vehicle details and complete any required inspections.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Vehicle Assigned',
            'message' => 'Vehicle ' . $this->vehicle->unit_number . ' has been assigned to you.',
            'type' => 'vehicle_assigned',
            'category' => 'vehicles',
            'icon' => 'Truck',
            'vehicle_id' => $this->vehicle->id,
            'vehicle_unit' => $this->vehicle->unit_number,
            'url' => route('driver.vehicles.show', $this->vehicle->id),
        ];
    }
}
