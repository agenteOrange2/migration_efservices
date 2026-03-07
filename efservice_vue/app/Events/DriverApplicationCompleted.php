<?php

namespace App\Events;

use App\Models\UserDriverDetail;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Driver Application Completed Event
 * 
 * Se dispara cuando un conductor completa su aplicación.
 */
class DriverApplicationCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public UserDriverDetail $driver
    ) {}
}
