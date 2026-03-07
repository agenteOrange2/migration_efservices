<?php

namespace App\Services\Driver;

use App\Models\DriverArchive;
use App\Models\MigrationRecord;

/**
 * Data Transfer Object for migration operation results.
 */
class MigrationResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?MigrationRecord $migrationRecord = null,
        public readonly ?DriverArchive $archive = null,
        public readonly array $errors = []
    ) {}

    /**
     * Check if migration was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->success;
    }

    /**
     * Get the first error message.
     */
    public function getFirstError(): ?string
    {
        return $this->errors[0] ?? null;
    }

    /**
     * Check if migration can be rolled back.
     */
    public function canRollback(): bool
    {
        return $this->success && $this->migrationRecord?->canRollback();
    }
}
