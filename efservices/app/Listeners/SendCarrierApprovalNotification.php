<?php

namespace App\Listeners;

use App\Events\CarrierApproved;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Send Carrier Approval Notification Listener
 * 
 * Envía notificación al carrier cuando es aprobado.
 */
class SendCarrierApprovalNotification
{
    /**
     * Handle the event.
     */
    public function handle(CarrierApproved $event): void
    {
        $carrier = $event->carrier;

        // Log de la aprobación
        Log::info('Carrier approved', [
            'carrier_id' => $carrier->id,
            'carrier_name' => $carrier->name,
            'approved_by' => $event->approvedBy,
        ]);

        // TODO: Enviar email de notificación
        // Mail::to($carrier->user->email)->send(new CarrierApprovedMail($carrier));

        // TODO: Crear notificación en el sistema
        // Notification::create([
        //     'user_id' => $carrier->user_id,
        //     'type' => 'carrier_approved',
        //     'data' => ['carrier_id' => $carrier->id],
        // ]);
    }
}
