<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when attempting to modify an immutable record.
 * Used primarily for MigrationRecord and DriverArchive models.
 */
class ImmutableRecordException extends Exception
{
    /**
     * Create a new ImmutableRecordException instance.
     *
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $message = "This record is immutable and cannot be modified.",
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
