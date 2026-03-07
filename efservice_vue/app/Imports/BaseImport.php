<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Collection;
use Carbon\Carbon;

abstract class BaseImport implements ToCollection, WithHeadingRow, WithValidation, SkipsOnFailure, WithBatchInserts, WithChunkReading
{
    use SkipsFailures;

    protected ?int $carrierId;
    protected int $importedBy;
    protected string $duplicateAction;
    protected array $importedRows = [];
    protected array $updatedRows = [];
    protected array $skippedRows = [];

    public function __construct(?int $carrierId, int $importedBy, string $duplicateAction = 'skip')
    {
        $this->carrierId = $carrierId;
        $this->importedBy = $importedBy;
        $this->duplicateAction = $duplicateAction;
    }

    /**
     * Process the collection of rows.
     */
    abstract public function collection(Collection $rows);

    /**
     * Get validation rules.
     */
    abstract public function rules(): array;

    /**
     * Get the unique key for a row (for duplicate detection).
     */
    abstract protected function getUniqueKey(array $row): string;

    /**
     * Check if a row is a duplicate.
     */
    abstract protected function isDuplicate(array $row): bool;

    /**
     * Batch size for inserts.
     */
    public function batchSize(): int
    {
        return 100;
    }

    /**
     * Chunk size for reading.
     */
    public function chunkSize(): int
    {
        return 500;
    }

    /**
     * Get the imported row IDs.
     */
    public function getImportedRows(): array
    {
        return $this->importedRows;
    }

    /**
     * Get the updated row IDs.
     */
    public function getUpdatedRows(): array
    {
        return $this->updatedRows;
    }

    /**
     * Get the skipped rows.
     */
    public function getSkippedRows(): array
    {
        return $this->skippedRows;
    }

    /**
     * Get validation failures.
     */
    public function getFailures(): array
    {
        return collect($this->failures())->map(function ($failure) {
            return [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
                'values' => $failure->values(),
            ];
        })->toArray();
    }

    /**
     * Parse a date value safely.
     */
    protected function parseDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            // Handle Excel date serial numbers
            if (is_numeric($value)) {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value))->format('Y-m-d');
            }

            $value = trim((string) $value);

            if (empty($value)) {
                return null;
            }

            // Try common date formats with strict parsing
            // US format (m/d/Y) MUST be before d/m/Y - system uses US dates
            $formats = [
                'Y-m-d',      // 1990-05-15 (ISO) - Most common, check first
                'm/d/Y',      // 05/15/1990 (US format) - Before d/m/Y!
                'm-d-Y',      // 05-15-1990 (US format)
                'd/m/Y',      // 15/05/1990 (DD/MM/YYYY)
                'd-m-Y',      // 15-05-1990
                'd.m.Y',      // 15.05.1990
                'Y/m/d',      // 1990/05/15
                'd/m/y',      // 15/05/90
                'd-m-y',      // 15-05-90
            ];

            foreach ($formats as $format) {
                try {
                    $date = Carbon::createFromFormat($format, $value);
                    if ($date) {
                        $errors = $date->getLastErrors();
                        if ($errors['warning_count'] == 0 && $errors['error_count'] == 0) {
                            return $date->format('Y-m-d');
                        }
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            // Fallback to Carbon::parse for flexible parsing
            $parsed = Carbon::parse($value);
            return $parsed->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse a datetime value safely.
     */
    protected function parseDateTime($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            // Handle Excel date serial numbers
            if (is_numeric($value)) {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value))->format('Y-m-d H:i:s');
            }

            $value = trim((string) $value);

            if (empty($value)) {
                return null;
            }

            // Try common datetime formats with strict parsing
            // US format (m/d/Y) MUST be before d/m/Y - system uses US dates
            $formats = [
                'Y-m-d H:i:s',       // 2025-01-15 08:00:00 (ISO with seconds)
                'Y-m-d H:i',         // 2025-01-15 08:00 (ISO)
                'm/d/Y H:i',         // 01/15/2025 08:00 (US format) - Before d/m/Y!
                'm/d/Y H:i:s',       // 01/15/2025 08:00:00 (US format)
                'd/m/Y H:i',         // 15/01/2025 08:00 (DD/MM/YYYY HH:MM)
                'd-m-Y H:i',         // 15-01-2025 08:00
                'd/m/Y H:i:s',       // 15/01/2025 08:00:00
                'd-m-Y H:i:s',       // 15-01-2025 08:00:00
                'Y/m/d H:i',         // 2025/01/15 08:00
                'Y/m/d H:i:s',       // 2025/01/15 08:00:00
            ];

            foreach ($formats as $format) {
                try {
                    $date = Carbon::createFromFormat($format, $value);
                    if ($date) {
                        $errors = $date->getLastErrors();
                        if ($errors['warning_count'] == 0 && $errors['error_count'] == 0) {
                            return $date->format('Y-m-d H:i:s');
                        }
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            // Fallback to Carbon::parse for flexible parsing
            $parsed = Carbon::parse($value);
            return $parsed->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse a decimal value safely.
     */
    protected function parseDecimal($value): ?float
    {
        if (empty($value) && $value !== 0 && $value !== '0') {
            return null;
        }

        // Remove currency symbols and commas
        $cleaned = preg_replace('/[^0-9.\-]/', '', (string) $value);
        return is_numeric($cleaned) ? (float) $cleaned : null;
    }

    /**
     * Parse an integer value safely.
     */
    protected function parseInt($value): ?int
    {
        if (empty($value) && $value !== 0 && $value !== '0') {
            return null;
        }

        $cleaned = preg_replace('/[^0-9\-]/', '', (string) $value);
        return is_numeric($cleaned) ? (int) $cleaned : null;
    }

    /**
     * Clean and uppercase a string (for VINs, etc.).
     */
    protected function cleanUppercase(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        return strtoupper(trim(preg_replace('/\s+/', '', $value)));
    }

    /**
     * Add a skipped row record.
     */
    protected function addSkippedRow(int $rowNumber, string $reason, array $data): void
    {
        $this->skippedRows[] = [
            'row' => $rowNumber,
            'reason' => $reason,
            'data' => $data,
        ];
    }

    /**
     * Add an imported row record.
     */
    protected function addImportedRow(int $id): void
    {
        $this->importedRows[] = $id;
    }

    /**
     * Add an updated row record.
     */
    protected function addUpdatedRow(int $id): void
    {
        $this->updatedRows[] = $id;
    }

    /**
     * Check if we should update duplicates.
     */
    protected function shouldUpdateDuplicates(): bool
    {
        return $this->duplicateAction === 'update';
    }

    /**
     * Find duplicate record for update.
     * Override in child classes to return the model to update.
     */
    protected function findDuplicateRecord(array $row): ?object
    {
        return null;
    }

    /**
     * Update an existing record with new data.
     * Override in child classes to implement update logic.
     */
    protected function updateRecord(object $record, array $row): void
    {
        // Override in child classes
    }
}
