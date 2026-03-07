<?php

namespace App\Events;

use App\Models\Carrier;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Carrier Approved Event
 * 
 * Se dispara cuando un carrier es aprobado por un administrador.
 */
class CarrierApproved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Carrier $carrier,
        public int $approvedBy
    ) {}
}
