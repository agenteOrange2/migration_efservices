<?php

namespace App\Imports;

use App\Models\Admin\Driver\DriverLicense;
use App\Models\User;
use App\Models\UserDriverDetail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class DriverLicensesImport extends BaseImport
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

            // Check for duplicate license
            if ($this->isDuplicateLicense($driverDetail->id, $rowData)) {
                $this->addSkippedRow($rowNumber, 'License already exists for this driver', $rowData);
                continue;
            }

            try {
                $license = DriverLicense::create([
                    'user_driver_detail_id' => $driverDetail->id,
                    'license_number' => trim($rowData['license_number'] ?? ''),
                    'state_of_issue' => strtoupper(trim($rowData['state_of_issue'] ?? $rowData['state'] ?? '')),
                    'license_class' => strtoupper(trim($rowData['license_class'] ?? 'A')),
                    'expiration_date' => $this->parseDate($rowData['expiration_date'] ?? null),
                    'is_cdl' => $this->normalizeBoolean($rowData['is_cdl'] ?? 'yes'),
                    'restrictions' => trim($rowData['restrictions'] ?? '') ?: null,
                    'status' => $this->normalizeLicenseStatus($rowData['status'] ?? 'active'),
                    'is_primary' => $this->normalizeBoolean($rowData['is_primary'] ?? 'yes'),
                ]);

                $this->addImportedRow($license->id);

                Log::info('Driver license imported', [
                    'row' => $rowNumber,
                    'license_id' => $license->id,
                    'driver_id' => $driverDetail->id,
                ]);
            } catch (\Exception $e) {
                $this->addSkippedRow($rowNumber, $e->getMessage(), $rowData);

                Log::error('Driver license import failed', [
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
            'license_number' => 'required|string',
            'state_of_issue' => 'required|string',
            'license_class' => 'required|string',
            'expiration_date' => 'required',
        ];
    }

    /**
     * Get the unique key for a row.
     */
    protected function getUniqueKey(array $row): string
    {
        return strtolower(trim($row['driver_email'] ?? '')) . '_' . trim($row['license_number'] ?? '');
    }

    /**
     * Check if a row is a duplicate.
     */
    protected function isDuplicate(array $row): bool
    {
        $driverDetail = $this->findDriverDetail($row);
        return $driverDetail ? $this->isDuplicateLicense($driverDetail->id, $row) : false;
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
     * Check for duplicate license.
     */
    protected function isDuplicateLicense(int $driverDetailId, array $row): bool
    {
        return DriverLicense::where('user_driver_detail_id', $driverDetailId)
            ->where('license_number', trim($row['license_number'] ?? ''))
            ->exists();
    }

    /**
     * Normalize license status.
     */
    protected function normalizeLicenseStatus(?string $value): string
    {
        if (empty($value)) {
            return 'active';
        }

        $value = strtolower(trim($value));

        $mapping = [
            'active' => 'active',
            'valid' => 'active',
            'expired' => 'expired',
            'revoked' => 'revoked',
            'suspended' => 'suspended',
        ];

        return $mapping[$value] ?? 'active';
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

        return in_array($value, ['1', 'true', 'yes', 'si', 'y', 's', 'cdl']);
    }
}
