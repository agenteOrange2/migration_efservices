<?php

namespace App\Imports;

use App\Models\Admin\Driver\DriverMedicalQualification;
use App\Models\User;
use App\Models\UserDriverDetail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class DriverMedicalImport extends BaseImport
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

            // Check for duplicate medical record
            if ($this->isDuplicateMedical($driverDetail->id)) {
                $this->addSkippedRow($rowNumber, 'Medical record already exists for this driver', $rowData);
                continue;
            }

            try {
                $medical = DriverMedicalQualification::create([
                    'user_driver_detail_id' => $driverDetail->id,
                    'social_security_number' => trim($rowData['social_security_number'] ?? $rowData['ssn'] ?? '') ?: null,
                    'hire_date' => $this->parseDate($rowData['hire_date'] ?? null),
                    'location' => trim($rowData['location'] ?? '') ?: null,
                    'is_suspended' => $this->normalizeBoolean($rowData['is_suspended'] ?? 'no'),
                    'suspension_date' => $this->parseDate($rowData['suspension_date'] ?? null),
                    'is_terminated' => $this->normalizeBoolean($rowData['is_terminated'] ?? 'no'),
                    'termination_date' => $this->parseDate($rowData['termination_date'] ?? null),
                    'medical_examiner_name' => trim($rowData['medical_examiner_name'] ?? ''),
                    'medical_examiner_registry_number' => trim($rowData['medical_examiner_registry_number'] ?? $rowData['registry_number'] ?? ''),
                    'medical_card_expiration_date' => $this->parseDate($rowData['medical_card_expiration_date'] ?? $rowData['medical_expiration'] ?? null),
                ]);

                $this->addImportedRow($medical->id);

                Log::info('Driver medical imported', [
                    'row' => $rowNumber,
                    'medical_id' => $medical->id,
                    'driver_id' => $driverDetail->id,
                ]);
            } catch (\Exception $e) {
                $this->addSkippedRow($rowNumber, $e->getMessage(), $rowData);

                Log::error('Driver medical import failed', [
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
            'medical_examiner_name' => 'required|string',
            'medical_examiner_registry_number' => 'required|string',
            'medical_card_expiration_date' => 'required',
        ];
    }

    /**
     * Get the unique key for a row.
     */
    protected function getUniqueKey(array $row): string
    {
        return strtolower(trim($row['driver_email'] ?? ''));
    }

    /**
     * Check if a row is a duplicate.
     */
    protected function isDuplicate(array $row): bool
    {
        $driverDetail = $this->findDriverDetail($row);
        return $driverDetail ? $this->isDuplicateMedical($driverDetail->id) : false;
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
     * Check for duplicate medical record.
     */
    protected function isDuplicateMedical(int $driverDetailId): bool
    {
        return DriverMedicalQualification::where('user_driver_detail_id', $driverDetailId)->exists();
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
