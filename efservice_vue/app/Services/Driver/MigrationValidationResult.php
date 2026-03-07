<?php

namespace App\Services\Driver;

/**
 * Data Transfer Object for migration validation results.
 */
class MigrationValidationResult
{
    public function __construct(
        public readonly bool $isValid,
        public readonly array $errors = [],
        public readonly array $warnings = []
    ) {}

    /**
     * Check if there are any warnings.
     */
    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }

    /**
     * Check if there are any errors.
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Get all messages (errors + warnings).
     */
    public function getAllMessages(): array
    {
        return [
            'errors' => $this->errors,
            'warnings' => $this->warnings,
        ];
    }
}
