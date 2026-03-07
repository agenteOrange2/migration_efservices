<?php

namespace App\Imports;

use App\Models\User;
use App\Models\UserCarrierDetail;
use App\Models\Carrier;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UserCarriersImport extends BaseImport
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

            // Find carrier - can be specified or use the carrier_id from constructor
            $carrierId = $this->findCarrierId($rowData);
            if (!$carrierId) {
                $this->addSkippedRow($rowNumber, 'Carrier not found: ' . ($rowData['carrier_name'] ?? $rowData['carrier_id'] ?? 'N/A'), $rowData);
                continue;
            }

            try {
                // Step 1: Create User
                $user = User::create([
                    'name' => trim($rowData['name'] ?? ''),
                    'middle_name' => trim($rowData['middle_name'] ?? '') ?: null,
                    'last_name' => trim($rowData['last_name'] ?? '') ?: null,
                    'email' => strtolower(trim($rowData['email'] ?? '')),
                    'password' => Hash::make($rowData['password'] ?? Str::random(12)),
                    'status' => $this->normalizeUserStatus($rowData['status'] ?? 'active'),
                ]);

                // Assign carrier user role
                $user->assignRole('user_carrier');

                // Step 2: Create UserCarrierDetail
                $carrierDetail = UserCarrierDetail::create([
                    'user_id' => $user->id,
                    'carrier_id' => $carrierId,
                    'phone' => trim($rowData['phone'] ?? '') ?: null,
                    'job_position' => trim($rowData['job_position'] ?? 'Staff') ?: 'Staff',
                    'status' => $this->normalizeCarrierUserStatus($rowData['carrier_status'] ?? 'active'),
                    'confirmation_token' => Str::random(64),
                ]);

                $this->addImportedRow($carrierDetail->id);

                Log::info('User Carrier imported', [
                    'row' => $rowNumber,
                    'user_id' => $user->id,
                    'carrier_detail_id' => $carrierDetail->id,
                    'email' => $user->email,
                    'carrier_id' => $carrierId,
                ]);
            } catch (\Exception $e) {
                // Rollback user if carrier detail creation fails
                if (isset($user)) {
                    $user->delete();
                }

                $this->addSkippedRow($rowNumber, $e->getMessage(), $rowData);

                Log::error('User Carrier import failed', [
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
            'phone' => 'nullable|string|max:15',
            'job_position' => 'nullable|string|max:255',
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
     * Find carrier ID from row data or use default.
     */
    protected function findCarrierId(array $row): ?int
    {
        // If carrier_id specified in row
        if (!empty($row['carrier_id']) && is_numeric($row['carrier_id'])) {
            $carrier = Carrier::find((int) $row['carrier_id']);
            if ($carrier) {
                return $carrier->id;
            }
        }

        // If carrier_name specified
        if (!empty($row['carrier_name'])) {
            $carrier = Carrier::where('name', 'LIKE', '%' . trim($row['carrier_name']) . '%')->first();
            if ($carrier) {
                return $carrier->id;
            }
        }

        // If carrier_ein specified
        if (!empty($row['carrier_ein'])) {
            $carrier = Carrier::where('ein_number', trim($row['carrier_ein']))->first();
            if ($carrier) {
                return $carrier->id;
            }
        }

        // If carrier_dot specified
        if (!empty($row['carrier_dot'])) {
            $carrier = Carrier::where('dot_number', trim($row['carrier_dot']))->first();
            if ($carrier) {
                return $carrier->id;
            }
        }

        // Use the carrier_id from constructor as fallback
        return $this->carrierId;
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
     * Normalize carrier user status (0=inactive, 1=active, 2=pending).
     */
    protected function normalizeCarrierUserStatus(?string $value): int
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
}
