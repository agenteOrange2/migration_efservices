<?php

namespace App\Imports;

use App\Models\Admin\Driver\DriverApplication;
use App\Models\Admin\Driver\DriverAddress;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class DriverAddressesImport extends BaseImport
{
    /**
     * Process the collection of rows.
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowData = $row->toArray();
            $rowNumber = $index + 2;

            // Find driver application by email
            $application = $this->findDriverApplication($rowData);

            if (!$application) {
                $this->addSkippedRow(
                    $rowNumber,
                    'Driver application not found for: ' . ($rowData['driver_email'] ?? 'N/A'),
                    $rowData
                );
                continue;
            }

            // Check for duplicate address
            if ($this->isDuplicateAddress($application->id, $rowData)) {
                $this->addSkippedRow($rowNumber, 'Address already exists for this driver', $rowData);
                continue;
            }

            try {
                $address = DriverAddress::create([
                    'driver_application_id' => $application->id,
                    'primary' => $this->normalizeBoolean($rowData['primary'] ?? 'no'),
                    'address_line1' => trim($rowData['address_line1'] ?? ''),
                    'address_line2' => trim($rowData['address_line2'] ?? '') ?: null,
                    'city' => trim($rowData['city'] ?? ''),
                    'state' => strtoupper(trim($rowData['state'] ?? '')),
                    'zip_code' => trim($rowData['zip_code'] ?? ''),
                    'lived_three_years' => $this->normalizeBoolean($rowData['lived_three_years'] ?? 'no'),
                    'from_date' => $this->parseDate($rowData['from_date'] ?? null),
                    'to_date' => $this->parseDate($rowData['to_date'] ?? null),
                ]);

                $this->addImportedRow($address->id);

                Log::info('Driver address imported', [
                    'row' => $rowNumber,
                    'address_id' => $address->id,
                    'driver_application_id' => $application->id,
                ]);
            } catch (\Exception $e) {
                $this->addSkippedRow($rowNumber, $e->getMessage(), $rowData);

                Log::error('Driver address import failed', [
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
            'address_line1' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string',
            'zip_code' => 'required|string',
            'from_date' => 'required',
        ];
    }

    /**
     * Get the unique key for a row.
     */
    protected function getUniqueKey(array $row): string
    {
        return strtolower(trim($row['driver_email'] ?? '')) . '_' . trim($row['address_line1'] ?? '');
    }

    /**
     * Check if a row is a duplicate.
     */
    protected function isDuplicate(array $row): bool
    {
        $application = $this->findDriverApplication($row);
        return $application ? $this->isDuplicateAddress($application->id, $row) : false;
    }

    /**
     * Find driver application by email.
     */
    protected function findDriverApplication(array $row): ?DriverApplication
    {
        if (empty($row['driver_email'])) {
            return null;
        }

        $user = User::where('email', strtolower(trim($row['driver_email'])))->first();

        if (!$user) {
            return null;
        }

        return DriverApplication::where('user_id', $user->id)->first();
    }

    /**
     * Check for duplicate address.
     */
    protected function isDuplicateAddress(int $applicationId, array $row): bool
    {
        return DriverAddress::where('driver_application_id', $applicationId)
            ->where('address_line1', trim($row['address_line1'] ?? ''))
            ->where('city', trim($row['city'] ?? ''))
            ->exists();
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

        return in_array($value, ['1', 'true', 'yes', 'si', 'y', 's', 'primary', 'current']);
    }
}
