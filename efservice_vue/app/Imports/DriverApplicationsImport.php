<?php

namespace App\Imports;

use App\Models\Admin\Driver\DriverApplication;
use App\Models\Admin\Driver\DriverApplicationDetail;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DriverApplicationsImport extends BaseImport
{
    /**
     * Process the collection of rows.
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowData = $row->toArray();
            $rowNumber = $index + 2;

            // Find user by email
            $user = $this->findUser($rowData);

            if (!$user) {
                $this->addSkippedRow(
                    $rowNumber,
                    'User not found: ' . ($rowData['user_email'] ?? 'N/A'),
                    $rowData
                );
                continue;
            }

            // Check for duplicate application
            if ($this->isDuplicateApplication($user->id)) {
                $this->addSkippedRow($rowNumber, 'Application already exists for this user', $rowData);
                continue;
            }

            try {
                DB::beginTransaction();

                // Create DriverApplication
                $application = DriverApplication::create([
                    'user_id' => $user->id,
                    'status' => $this->normalizeStatus($rowData['status'] ?? 'pending'),
                    'completed_at' => $this->parseDateTime($rowData['completed_at'] ?? $rowData['applied_date'] ?? null),
                    'rejection_reason' => trim($rowData['rejection_reason'] ?? '') ?: null,
                ]);

                // Create DriverApplicationDetail
                DriverApplicationDetail::create([
                    'driver_application_id' => $application->id,
                    'applying_position' => $this->normalizePosition($rowData['applying_position'] ?? 'company_driver'),
                    'applying_position_other' => trim($rowData['applying_position_other'] ?? '') ?: null,
                    'applying_location' => trim($rowData['applying_location'] ?? 'Not specified'),
                    'eligible_to_work' => $this->normalizeBoolean($rowData['eligible_to_work'] ?? 'yes'),
                    'can_speak_english' => $this->normalizeBoolean($rowData['can_speak_english'] ?? 'yes'),
                    'has_twic_card' => $this->normalizeBoolean($rowData['has_twic_card'] ?? 'no'),
                    'twic_expiration_date' => $this->parseDate($rowData['twic_expiration_date'] ?? null),
                    'how_did_hear' => $this->normalizeHowDidHear($rowData['how_did_hear'] ?? 'other'),
                    'how_did_hear_other' => trim($rowData['how_did_hear_other'] ?? '') ?: null,
                    'referral_employee_name' => trim($rowData['referral_employee_name'] ?? '') ?: null,
                    'expected_pay' => $this->parseDecimal($rowData['expected_pay'] ?? 0),
                ]);

                DB::commit();

                $this->addImportedRow($application->id);

                Log::info('Driver application imported', [
                    'row' => $rowNumber,
                    'application_id' => $application->id,
                    'user_id' => $user->id,
                ]);
            } catch (\Exception $e) {
                DB::rollBack();

                $this->addSkippedRow($rowNumber, $e->getMessage(), $rowData);

                Log::error('Driver application import failed', [
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
            'user_email' => 'required|email',
            'applying_position' => 'required|string',
            'applying_location' => 'required|string',
            'expected_pay' => 'required|numeric',
        ];
    }

    /**
     * Get the unique key for a row.
     */
    protected function getUniqueKey(array $row): string
    {
        return trim($row['user_email'] ?? '');
    }

    /**
     * Check if a row is a duplicate.
     */
    protected function isDuplicate(array $row): bool
    {
        $user = $this->findUser($row);
        return $user ? $this->isDuplicateApplication($user->id) : false;
    }

    /**
     * Find user by email.
     */
    protected function findUser(array $row): ?User
    {
        if (empty($row['user_email'])) {
            return null;
        }

        return User::where('email', trim($row['user_email']))->first();
    }

    /**
     * Check for duplicate application.
     */
    protected function isDuplicateApplication(int $userId): bool
    {
        return DriverApplication::where('user_id', $userId)->exists();
    }

    /**
     * Normalize status value.
     */
    protected function normalizeStatus(?string $value): string
    {
        if (empty($value)) {
            return 'pending';
        }

        $value = strtolower(trim($value));

        $mapping = [
            'draft' => 'draft',
            'pending' => 'pending',
            'submitted' => 'pending',
            'approved' => 'approved',
            'accepted' => 'approved',
            'rejected' => 'rejected',
            'denied' => 'rejected',
        ];

        return $mapping[$value] ?? 'pending';
    }

    /**
     * Normalize applying position value.
     */
    protected function normalizePosition(?string $value): string
    {
        if (empty($value)) {
            return 'company_driver';
        }

        $value = strtolower(trim($value));

        $mapping = [
            'company_driver' => 'company_driver',
            'company' => 'company_driver',
            'driver' => 'company_driver',
            'owner_operator' => 'owner_operator',
            'owner' => 'owner_operator',
            'oo' => 'owner_operator',
            'third_party_driver' => 'third_party_driver',
            'third_party' => 'third_party_driver',
            'third' => 'third_party_driver',
            'other' => 'other',
        ];

        return $mapping[$value] ?? 'company_driver';
    }

    /**
     * Normalize how did hear value.
     */
    protected function normalizeHowDidHear(?string $value): string
    {
        if (empty($value)) {
            return 'other';
        }

        $value = strtolower(trim($value));

        $validOptions = [
            'indeed',
            'facebook',
            'google',
            'linkedin',
            'referral',
            'employee_referral',
            'website',
            'other',
        ];

        return in_array($value, $validOptions) ? $value : 'other';
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

    /**
     * Parse decimal value.
     */
    protected function parseDecimal($value): float
    {
        if (empty($value)) {
            return 0.00;
        }

        // Remove currency symbols and commas
        $cleaned = preg_replace('/[^0-9.]/', '', (string) $value);

        return (float) $cleaned;
    }
}
