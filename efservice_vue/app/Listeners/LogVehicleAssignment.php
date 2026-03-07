<?php

namespace App\Listeners;

use App\Events\VehicleAssigned;
use Illuminate\Support\Facades\Log;

/**
 * Log Vehicle Assignment Listener
 * 
 * Registra la asignación de vehículo en los logs.
 */
class LogVehicleAssignment
{
    /**
     * Handle the event.
     */
    public function handle(VehicleAssigned $event): void
    {
        $assignment = $event->assignment;

        Log::info('Vehicle assigned to driver', [
            'assignment_id' => $assignment->id,
            'vehicle_id' => $assignment->vehicle_id,
            'driver_id' => $assignment->user_driver_detail_id,
            'assignment_type' => $assignment->assignment_type,
            'vehicle_vin' => $assignment->vehicle->vin ?? null,
            'driver_name' => $assignment->driverDetail->user->name ?? null,
        ]);

        // TODO: Enviar notificación al driver
        // Mail::to($assignment->driverDetail->user->email)
        //     ->send(new VehicleAssignedMail($assignment));

        // TODO: Crear notificación en el sistema
        // Notification::create([
        //     'user_id' => $assignment->driverDetail->user_id,
        //     'type' => 'vehicle_assigned',
        //     'data' => [
        //         'vehicle_id' => $assignment->vehicle_id,
        //         'assignment_type' => $assignment->assignment_type,
        //     ],
        // ]);
    }
}
