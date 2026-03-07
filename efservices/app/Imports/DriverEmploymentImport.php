<?php

namespace App\Imports;

use App\Models\Admin\Driver\DriverEmploymentCompany;
use App\Models\Admin\Driver\MasterCompany;
use App\Models\User;
use App\Models\UserDriverDetail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DriverEmploymentImport extends BaseImport
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

            // Check for duplicate employment
            if ($this->isDuplicateEmployment($driverDetail->id, $rowData)) {
                $this->addSkippedRow($rowNumber, 'Employment record already exists for this company', $rowData);
                continue;
            }

            try {
                DB::beginTransaction();

                // Find or create the master company
                $masterCompany = $this->findOrCreateMasterCompany($rowData);

                $employment = DriverEmploymentCompany::create([
                    'user_driver_detail_id' => $driverDetail->id,
                    'master_company_id' => $masterCompany->id,
                    'employed_from' => $this->parseDate($rowData['employed_from'] ?? $rowData['start_date'] ?? null),
                    'employed_to' => $this->parseDate($rowData['employed_to'] ?? $rowData['end_date'] ?? null),
                    'positions_held' => trim($rowData['positions_held'] ?? $rowData['position'] ?? '') ?: null,
                    'subject_to_fmcsr' => $this->normalizeBoolean($rowData['subject_to_fmcsr'] ?? 'no'),
                    'safety_sensitive_function' => $this->normalizeBoolean($rowData['safety_sensitive_function'] ?? 'no'),
                    'reason_for_leaving' => trim($rowData['reason_for_leaving'] ?? '') ?: null,
                    'email' => trim($rowData['company_email'] ?? '') ?: null,
                    'other_reason_description' => trim($rowData['other_reason_description'] ?? '') ?: null,
                    'explanation' => trim($rowData['explanation'] ?? '') ?: null,
                ]);

                DB::commit();

                $this->addImportedRow($employment->id);

                Log::info('Driver employment imported', [
                    'row' => $rowNumber,
                    'employment_id' => $employment->id,
                    'driver_id' => $driverDetail->id,
                    'company' => $masterCompany->company_name,
                ]);
            } catch (\Exception $e) {
                DB::rollBack();

                $this->addSkippedRow($rowNumber, $e->getMessage(), $rowData);

                Log::error('Driver employment import failed', [
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
            'company_name' => 'required|string',
            'employed_from' => 'required',
            'employed_to' => 'required',
        ];
    }

    /**
     * Get the unique key for a row.
     */
    protected function getUniqueKey(array $row): string
    {
        return strtolower(trim($row['driver_email'] ?? '')) . '_' . strtolower(trim($row['company_name'] ?? ''));
    }

    /**
     * Check if a row is a duplicate.
     */
    protected function isDuplicate(array $row): bool
    {
        $driverDetail = $this->findDriverDetail($row);
        return $driverDetail ? $this->isDuplicateEmployment($driverDetail->id, $row) : false;
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
     * Find or create master company.
     */
    protected function findOrCreateMasterCompany(array $row): MasterCompany
    {
        $companyName = trim($row['company_name'] ?? '');

        // Try to find existing company by name
        $company = MasterCompany::where('company_name', $companyName)->first();

        if ($company) {
            return $company;
        }

        // Create new company
        return MasterCompany::create([
            'company_name' => $companyName,
            'address' => trim($row['company_address'] ?? '') ?: null,
            'city' => trim($row['company_city'] ?? '') ?: null,
            'state' => trim($row['company_state'] ?? '') ?: null,
            'zip' => trim($row['company_zip'] ?? '') ?: null,
            'contact' => trim($row['company_contact'] ?? '') ?: null,
            'phone' => trim($row['company_phone'] ?? '') ?: null,
            'email' => trim($row['company_email'] ?? '') ?: null,
            'fax' => trim($row['company_fax'] ?? '') ?: null,
        ]);
    }

    /**
     * Check for duplicate employment.
     */
    protected function isDuplicateEmployment(int $driverDetailId, array $row): bool
    {
        $companyName = trim($row['company_name'] ?? '');
        $company = MasterCompany::where('company_name', $companyName)->first();

        if (!$company) {
            return false;
        }

        return DriverEmploymentCompany::where('user_driver_detail_id', $driverDetailId)
            ->where('master_company_id', $company->id)
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

        return in_array($value, ['1', 'true', 'yes', 'si', 'y', 's']);
    }
}
