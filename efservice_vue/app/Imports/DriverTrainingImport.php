<?php

namespace App\Imports;

use App\Models\Admin\Driver\DriverTrainingSchool;
use App\Models\User;
use App\Models\UserDriverDetail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class DriverTrainingImport extends BaseImport
{
    /**
     * Process the collection of rows.
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowData = $row->toArray();
            $rowNumber = $index + 2;

            // Find driver detail by email
            $driverDetail = $this->findDriverDetail($rowData);

            if (!$driverDetail) {
                $this->addSkippedRow(
                    $rowNumber,
                    'Driver not found for: ' . ($rowData['driver_email'] ?? 'N/A'),
                    $rowData
                );
                continue;
            }

            // Check for duplicate training
            if ($this->isDuplicateTraining($driverDetail->id, $rowData)) {
                $this->addSkippedRow($rowNumber, 'Training record already exists for this school', $rowData);
                continue;
            }

            try {
                $training = DriverTrainingSchool::create([
                    'user_driver_detail_id' => $driverDetail->id,
                    'date_start' => $this->parseDate($rowData['date_start'] ?? $rowData['start_date'] ?? null),
                    'date_end' => $this->parseDate($rowData['date_end'] ?? $rowData['end_date'] ?? null),
                    'school_name' => trim($rowData['school_name'] ?? ''),
                    'city' => trim($rowData['city'] ?? ''),
                    'state' => strtoupper(trim($rowData['state'] ?? '')),
                    'graduated' => $this->normalizeBoolean($rowData['graduated'] ?? 'yes'),
                    'subject_to_safety_regulations' => $this->normalizeBoolean($rowData['subject_to_safety_regulations'] ?? 'no'),
                    'performed_safety_functions' => $this->normalizeBoolean($rowData['performed_safety_functions'] ?? 'no'),
                    'training_skills' => $this->parseTrainingSkills($rowData['training_skills'] ?? null),
                ]);

                $this->addImportedRow($training->id);

                Log::info('Driver training imported', [
                    'row' => $rowNumber,
                    'training_id' => $training->id,
                    'driver_id' => $driverDetail->id,
                    'school' => $training->school_name,
                ]);
            } catch (\Exception $e) {
                $this->addSkippedRow($rowNumber, $e->getMessage(), $rowData);

                Log::error('Driver training import failed', [
                    'row' => $rowNumber,
                    'error' => $e->getMessage(),
                    'data' => $rowData,
                ]);
            }
        }
    }

    /**
     * Get validation rules.
     */
    public function rules(): array
    {
        return [
            'driver_email' => 'required|email',
            'school_name' => 'required|string',
            'date_start' => 'required',
            'date_end' => 'required',
            'city' => 'required|string',
            'state' => 'required|string',
        ];
    }

    /**
     * Get the unique key for a row.
     */
    protected function getUniqueKey(array $row): string
    {
        return strtolower(trim($row['driver_email'] ?? '')) . '_' . strtolower(trim($row['school_name'] ?? ''));
    }

    /**
     * Check if a row is a duplicate.
     */
    protected function isDuplicate(array $row): bool
    {
        $driverDetail = $this->findDriverDetail($row);
        return $driverDetail ? $this->isDuplicateTraining($driverDetail->id, $row) : false;
    }

    /**
     * Find driver detail by email.
     */
    protected function findDriverDetail(array $row): ?UserDriverDetail
    {
        if (empty($row['driver_email'])) {
            return null;
        }

        $user = User::where('email', strtolower(trim($row['driver_email'])))->first();

        if (!$user) {
            return null;
        }

        return UserDriverDetail::where('user_id', $user->id)
            ->where('carrier_id', $this->carrierId)
            ->first();
    }

    /**
     * Check for duplicate training.
     */
    protected function isDuplicateTraining(int $driverDetailId, array $row): bool
    {
        return DriverTrainingSchool::where('user_driver_detail_id', $driverDetailId)
            ->where('school_name', trim($row['school_name'] ?? ''))
            ->exists();
    }

    /**
     * Parse training skills from CSV.
     */
    protected function parseTrainingSkills($value): ?array
    {
        if (empty($value)) {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        // Parse comma-separated skills
        $skills = array_map('trim', explode(',', (string) $value));
        return array_filter($skills);
    }

    /**
     * Normalize boolean value.
     */
    protected function normalizeBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (empty($value)) {
            return false;
        }

        $value = strtolower(trim((string) $value));

        return in_array($value, ['1', 'true', 'yes', 'si', 'y', 's']);
    }
}
