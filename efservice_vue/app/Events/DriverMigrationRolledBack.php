<?php

namespace App\Events;

use App\Models\MigrationRecord;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a driver migration is rolled back.
 */
class DriverMigrationRolledBack
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public MigrationRecord $migrationRecord,
        public User $rolledBackBy
    ) {}

    /**
     * Get the driver user ID.
     */
    public function getDriverUserId(): int
    {
        return $this->migrationRecord->driver_user_id;
    }

    /**
     * Get the original source carrier ID (where driver is being restored to).
     */
    public function getSourceCarrierId(): int
    {
        return $this->migrationRecord->source_carrier_id;
    }

    /**
     * Get the target carrier ID (where driver was migrated to before rollback).
     */
    public function getTargetCarrierId(): int
    {
        return $this->migrationRecord->target_carrier_id;
    }
}
