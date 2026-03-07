<?php

namespace App\Imports;

use App\Models\User;
use App\Models\UserDriverDetail;
use App\Models\Admin\Driver\DriverApplication;
use App\Models\Admin\Driver\DriverApplicationDetail;
use App\Models\Admin\Driver\DriverAddress;
use App\Models\Admin\Driver\DriverLicense;
use App\Models\Admin\Driver\DriverMedicalQualification;
use App\Models\Admin\Driver\DriverEmploymentCompany;
use App\Models\Admin\Driver\DriverTrainingSchool;
use App\Models\Admin\Driver\MasterCompany;
use App\Models\Carrier;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DriversImport extends BaseImport
{
    /**
     * Process the collection of rows.
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowData = $row->toArray();
            $rowNumber = $index + 2;

            // Check for duplicate by email
            if ($this->isDuplicate($rowData)) {
                $this->addSkippedRow($rowNumber, 'Email already exists: ' . ($rowData['email'] ?? 'N/A'), $rowData);
                continue;
            }

            // Validate age (must be 18+)
            $dob = $this->parseDate($rowData['date_of_birth'] ?? null);
            if ($dob && \Carbon\Carbon::parse($dob)->age < 18) {
                $this->addSkippedRow($rowNumber, 'Driver must be at least 18 years old', $rowData);
                continue;
            }

            try {
                DB::beginTransaction();

                // Step 1: Create User
                $user = User::create([
                    'name' => trim($rowData['name'] ?? ''),
                    'email' => strtolower(trim($rowData['email'] ?? '')),
                    'password' => Hash::make($rowData['password'] ?? Str::random(12)),
                    'status' => $this->normalizeUserStatus($rowData['status'] ?? 'active'),
                ]);

                // Assign driver role
                $user->assignRole('user_driver');

                // Step 2: Create DriverApplication (default approved for imports)
                $applicationStatus = $this->normalizeApplicationStatus($rowData['application_status'] ?? 'approved');
                $application = DriverApplication::create([
                    'user_id' => $user->id,
                    'status' => $applicationStatus,
                    'completed_at' => $applicationStatus === 'approved' ? now() : null,
                ]);

                // Step 3: Create DriverApplicationDetail
                DriverApplicationDetail::create([
                    'driver_application_id' => $application->id,
                    'applying_position' => $this->normalizePosition($rowData['applying_position'] ?? 'company_driver'),
                    'applying_position_other' => trim($rowData['applying_position_other'] ?? '') ?: null,
                    'applying_location' => trim($rowData['applying_location'] ?? $this->getCarrierLocation()),
                    'eligible_to_work' => $this->normalizeBoolean($rowData['eligible_to_work'] ?? 'yes'),
                    'can_speak_english' => $this->normalizeBoolean($rowData['can_speak_english'] ?? 'yes'),
                    'has_twic_card' => $this->normalizeBoolean($rowData['has_twic_card'] ?? 'no'),
                    'twic_expiration_date' => $this->parseDate($rowData['twic_expiration_date'] ?? null),
                    'how_did_hear' => $this->normalizeHowDidHear($rowData['how_did_hear'] ?? 'other'),
                    'how_did_hear_other' => trim($rowData['how_did_hear_other'] ?? '') ?: null,
                    'referral_employee_name' => trim($rowData['referral_employee_name'] ?? '') ?: null,
                    'expected_pay' => $this->parseDecimal($rowData['expected_pay'] ?? 0),
                ]);

                // Step 4: Create UserDriverDetail
                $driverDetail = UserDriverDetail::create([
                    'user_id' => $user->id,
                    'carrier_id' => $this->carrierId,
                    'middle_name' => trim($rowData['middle_name'] ?? '') ?: null,
                    'last_name' => trim($rowData['last_name'] ?? ''),
                    'phone' => trim($rowData['phone'] ?? '') ?: null,
                    'date_of_birth' => $dob,
                    'status' => $this->normalizeDriverStatus($rowData['driver_status'] ?? 'active'),
                    'terms_accepted' => true,
                    'confirmation_token' => Str::random(60),
                    'current_step' => 10, // Mark as completed
                    'completion_percentage' => 100,
                    'hos_cycle_type' => $this->normalizeHosCycle($rowData['hos_cycle'] ?? '70_8'),
                ]);

                // Step 5: Create Driver Address (if provided)
                $this->createDriverAddress($application->id, $rowData);

                // Step 6: Create Driver License (if provided)
                $this->createDriverLicense($driverDetail->id, $rowData);

                // Step 7: Create Medical Qualification (if provided)
                $this->createMedicalQualification($driverDetail->id, $rowData);

                // Step 8: Create Employment History (if provided)
                $this->createEmploymentHistory($driverDetail->id, $rowData);

                // Step 9: Create Training School (if provided)
                $this->createTrainingSchool($driverDetail->id, $rowData);

                DB::commit();

                $this->addImportedRow($driverDetail->id);

                Log::info('Driver imported', [
                    'row' => $rowNumber,
                    'user_id' => $user->id,
                    'driver_id' => $driverDetail->id,
                    'application_id' => $application->id,
                    'email' => $user->email,
                ]);
            } catch (\Exception $e) {
                DB::rollBack();

                $this->addSkippedRow($rowNumber, $e->getMessage(), $rowData);

                Log::error('Driver import failed', [
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
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'last_name' => 'required|string|max:255',
            'date_of_birth' => 'required',
            'phone' => 'nullable|string|max:15',
        ];
    }

    /**
     * Get the unique key for a row.
     */
    protected function getUniqueKey(array $row): string
    {
        return strtolower(trim($row['email'] ?? ''));
    }

    /**
     * Check if a row is a duplicate.
     */
    protected function isDuplicate(array $row): bool
    {
        $email = $this->getUniqueKey($row);

        if (empty($email)) {
            return false;
        }

        return User::where('email', $email)->exists();
    }

    /**
     * Normalize user status.
     */
    protected function normalizeUserStatus(?string $value): bool
    {
        if (empty($value)) {
            return true;
        }

        $value = strtolower(trim($value));

        return in_array($value, ['1', 'true', 'yes', 'active', 'enabled']);
    }

    /**
     * Normalize driver status (0=inactive, 1=active, 2=pending).
     */
    protected function normalizeDriverStatus(?string $value): int
    {
        if (empty($value)) {
            return 1; // active
        }

        $value = strtolower(trim($value));

        $mapping = [
            'inactive' => 0,
            '0' => 0,
            'active' => 1,
            '1' => 1,
            'pending' => 2,
            '2' => 2,
        ];

        return $mapping[$value] ?? 1;
    }

    /**
     * Normalize HOS cycle type.
     */
    protected function normalizeHosCycle(?string $value): string
    {
        if (empty($value)) {
            return '70_8';
        }

        $value = strtolower(str_replace([' ', '-'], '_', trim($value)));

        if (in_array($value, ['60_7', '60/7', '607'])) {
            return '60_7';
        }

        return '70_8';
    }

    /**
     * Normalize application status.
     */
    protected function normalizeApplicationStatus(?string $value): string
    {
        if (empty($value)) {
            return 'approved'; // Default approved for imports
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

        return $mapping[$value] ?? 'approved';
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

    /**
     * Get carrier location as default applying_location.
     */
    protected function getCarrierLocation(): string
    {
        $carrier = Carrier::find($this->carrierId);

        if ($carrier && $carrier->state) {
            return $carrier->state;
        }

        return 'Not specified';
    }

    /**
     * Create driver address record.
     */
    protected function createDriverAddress(int $applicationId, array $rowData): void
    {
        $addressLine1 = trim($rowData['address_line1'] ?? '');

        if (empty($addressLine1)) {
            return; // Skip if no address provided
        }

        DriverAddress::create([
            'driver_application_id' => $applicationId,
            'primary' => true,
            'address_line1' => $addressLine1,
            'address_line2' => trim($rowData['address_line2'] ?? '') ?: null,
            'city' => trim($rowData['city'] ?? ''),
            'state' => trim($rowData['state'] ?? ''),
            'zip_code' => trim($rowData['zip_code'] ?? ''),
            'lived_three_years' => $this->normalizeBoolean($rowData['lived_three_years'] ?? 'yes'),
            'from_date' => $this->parseDate($rowData['address_from_date'] ?? null),
            'to_date' => null, // Current address
        ]);
    }

    /**
     * Create driver license record.
     */
    protected function createDriverLicense(int $driverDetailId, array $rowData): void
    {
        $licenseNumber = trim($rowData['license_number'] ?? '');

        if (empty($licenseNumber)) {
            return; // Skip if no license provided
        }

        DriverLicense::create([
            'user_driver_detail_id' => $driverDetailId,
            'license_number' => $licenseNumber,
            'state_of_issue' => trim($rowData['license_state'] ?? ''),
            'license_class' => strtoupper(trim($rowData['license_class'] ?? 'A')),
            'expiration_date' => $this->parseDate($rowData['license_expiration_date'] ?? null),
            'is_cdl' => $this->normalizeBoolean($rowData['is_cdl'] ?? 'yes'),
            'restrictions' => trim($rowData['license_restrictions'] ?? '') ?: null,
            'status' => 'active',
            'is_primary' => true,
        ]);
    }

    /**
     * Create medical qualification record.
     */
    protected function createMedicalQualification(int $driverDetailId, array $rowData): void
    {
        $examinerName = trim($rowData['medical_examiner_name'] ?? '');

        if (empty($examinerName)) {
            return; // Skip if no medical data provided
        }

        DriverMedicalQualification::create([
            'user_driver_detail_id' => $driverDetailId,
            'social_security_number' => trim($rowData['social_security_number'] ?? '') ?: null,
            'hire_date' => $this->parseDate($rowData['hire_date'] ?? null),
            'medical_examiner_name' => $examinerName,
            'medical_examiner_registry_number' => trim($rowData['medical_examiner_registry_number'] ?? '') ?: null,
            'medical_card_expiration_date' => $this->parseDate($rowData['medical_card_expiration_date'] ?? null),
            'location' => trim($rowData['medical_location'] ?? '') ?: null,
            'is_suspended' => $this->normalizeBoolean($rowData['is_suspended'] ?? 'no'),
            'is_terminated' => $this->normalizeBoolean($rowData['is_terminated'] ?? 'no'),
        ]);
    }

    /**
     * Create employment history record.
     */
    protected function createEmploymentHistory(int $driverDetailId, array $rowData): void
    {
        $companyName = trim($rowData['previous_employer_name'] ?? '');

        if (empty($companyName)) {
            return; // Skip if no previous employment provided
        }

        // Find or create MasterCompany
        $masterCompany = MasterCompany::firstOrCreate(
            ['company_name' => $companyName],
            [
                'address' => trim($rowData['previous_employer_address'] ?? '') ?: null,
                'city' => trim($rowData['previous_employer_city'] ?? '') ?: null,
                'state' => trim($rowData['previous_employer_state'] ?? '') ?: null,
                'zip' => trim($rowData['previous_employer_zip'] ?? '') ?: null,
                'phone' => trim($rowData['previous_employer_phone'] ?? '') ?: null,
                'email' => trim($rowData['previous_employer_email'] ?? '') ?: null,
            ]
        );

        DriverEmploymentCompany::create([
            'user_driver_detail_id' => $driverDetailId,
            'master_company_id' => $masterCompany->id,
            'employed_from' => $this->parseDate($rowData['previous_employment_from'] ?? null),
            'employed_to' => $this->parseDate($rowData['previous_employment_to'] ?? null),
            'positions_held' => trim($rowData['previous_positions_held'] ?? 'Driver') ?: 'Driver',
            'subject_to_fmcsr' => $this->normalizeBoolean($rowData['previous_subject_to_fmcsr'] ?? 'yes'),
            'safety_sensitive_function' => $this->normalizeBoolean($rowData['previous_safety_sensitive_function'] ?? 'yes'),
            'reason_for_leaving' => trim($rowData['previous_employment_reason_leaving'] ?? '') ?: null,
        ]);
    }

    /**
     * Create training school record.
     */
    protected function createTrainingSchool(int $driverDetailId, array $rowData): void
    {
        $schoolName = trim($rowData['training_school_name'] ?? '');

        if (empty($schoolName)) {
            return; // Skip if no training school provided
        }

        DriverTrainingSchool::create([
            'user_driver_detail_id' => $driverDetailId,
            'school_name' => $schoolName,
            'city' => trim($rowData['training_school_city'] ?? '') ?: null,
            'state' => trim($rowData['training_school_state'] ?? '') ?: null,
            'date_start' => $this->parseDate($rowData['training_date_start'] ?? null),
            'date_end' => $this->parseDate($rowData['training_date_end'] ?? null),
            'graduated' => $this->normalizeBoolean($rowData['training_graduated'] ?? 'yes'),
            'subject_to_safety_regulations' => $this->normalizeBoolean($rowData['training_subject_to_safety_regulations'] ?? 'yes'),
            'performed_safety_functions' => $this->normalizeBoolean($rowData['training_performed_safety_functions'] ?? 'yes'),
            'training_skills' => trim($rowData['training_skills'] ?? '') ?: null,
        ]);
    }
}
