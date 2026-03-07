<?php

namespace App\Events;

use App\Models\DriverArchive;
use App\Models\MigrationRecord;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a driver migration is completed successfully.
 */
class DriverMigrationCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public MigrationRecord $migrationRecord,
        public DriverArchive $archive
    ) {}

    /**
     * Get the driver user ID.
     */
    public function getDriverUserId(): int
    {
        return $this->migrationRecord->driver_user_id;
    }

    /**
     * Get the source carrier ID.
     */
    public function getSourceCarrierId(): int
    {
        return $this->migrationRecord->source_carrier_id;
    }

    /**
     * Get the target carrier ID.
     */
    public function getTargetCarrierId(): int
    {
        return $this->migrationRecord->target_carrier_id;
    }
}
