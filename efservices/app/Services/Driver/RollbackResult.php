<?php

namespace App\Services\Driver;

use App\Models\UserDriverDetail;

/**
 * Data Transfer Object for rollback operation results.
 */
class RollbackResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?UserDriverDetail $driver = null,
        public readonly ?string $error = null
    ) {}

    /**
     * Check if rollback was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->success;
    }

    /**
     * Get the error message.
     */
    public function getError(): ?string
    {
        return $this->error;
    }
}
