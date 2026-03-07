<?php

namespace App\Events;

use App\Models\Admin\Vehicle\VehicleDriverAssignment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Vehicle Assigned Event
 * 
 * Se dispara cuando un vehículo es asignado a un conductor.
 */
class VehicleAssigned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public VehicleDriverAssignment $assignment
    ) {}
}
