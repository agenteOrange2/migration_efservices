<?php

namespace App\Imports;

use App\Models\Carrier;
use App\Models\Membership;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CarriersImport extends BaseImport
{
    /**
     * Process the collection of rows.
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowData = $row->toArray();
            $rowNumber = $index + 2;

            // Check for duplicate by EIN, DOT, or MC number
            $duplicateCheck = $this->checkDuplicates($rowData);
            if ($duplicateCheck) {
                $this->addSkippedRow($rowNumber, $duplicateCheck, $rowData);
                continue;
            }

            // Find membership plan
            $membership = $this->findMembership($rowData);
            if (!$membership) {
                $this->addSkippedRow($rowNumber, 'Membership plan not found: ' . ($rowData['membership'] ?? 'N/A'), $rowData);
                continue;
            }

            try {
                $carrier = Carrier::create([
                    'name' => trim($rowData['name'] ?? ''),
                    'slug' => Str::slug(trim($rowData['name'] ?? '')),
                    'address' => trim($rowData['address'] ?? ''),
                    'headquarters' => trim($rowData['headquarters'] ?? '') ?: null,
                    'state' => strtoupper(trim($rowData['state'] ?? '')),
                    'zipcode' => trim($rowData['zipcode'] ?? ''),
                    'ein_number' => $this->formatEIN($rowData['ein_number'] ?? ''),
                    'dot_number' => trim($rowData['dot_number'] ?? '') ?: null,
                    'mc_number' => trim($rowData['mc_number'] ?? '') ?: null,
                    'state_dot' => trim($rowData['state_dot'] ?? '') ?: null,
                    'ifta_account' => trim($rowData['ifta_account'] ?? '') ?: null,
                    'id_plan' => $membership->id,
                    'status' => $this->normalizeStatus($rowData['status'] ?? 'active'),
                    'document_status' => Carrier::DOCUMENT_STATUS_PENDING ?? 0,
                    'referrer_token' => Str::random(16),
                    'country' => 'US',
                    'business_type' => trim($rowData['business_type'] ?? '') ?: null,
                    'years_in_business' => trim($rowData['years_in_business'] ?? '') ?: null,
                    'fleet_size' => trim($rowData['fleet_size'] ?? '') ?: null,
                ]);

                $this->addImportedRow($carrier->id);

                Log::info('Carrier imported', [
                    'row' => $rowNumber,
                    'carrier_id' => $carrier->id,
                    'name' => $carrier->name,
                    'ein' => $carrier->ein_number,
                ]);
            } catch (\Exception $e) {
                $this->addSkippedRow($rowNumber, $e->getMessage(), $rowData);

                Log::error('Carrier import failed', [
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
            'name' => 'required|max:255',
            'address' => 'required|max:255',
            'state' => 'required|max:2',
            'zipcode' => 'required|max:10',
            'ein_number' => 'required',
        ];
    }

    /**
     * Get the unique key for a row.
     */
    protected function getUniqueKey(array $row): string
    {
        return $this->formatEIN($row['ein_number'] ?? '');
    }

    /**
     * Check if a row is a duplicate.
     */
    protected function isDuplicate(array $row): bool
    {
        return $this->checkDuplicates($row) !== null;
    }

    /**
     * Check for duplicates by EIN, DOT, or MC number.
     */
    protected function checkDuplicates(array $row): ?string
    {
        $ein = $this->formatEIN($row['ein_number'] ?? '');
        $dot = trim($row['dot_number'] ?? '');
        $mc = trim($row['mc_number'] ?? '');

        if ($ein && Carrier::where('ein_number', $ein)->exists()) {
            return "Duplicate EIN: {$ein}";
        }

        if ($dot && Carrier::where('dot_number', $dot)->exists()) {
            return "Duplicate DOT Number: {$dot}";
        }

        if ($mc && Carrier::where('mc_number', $mc)->exists()) {
            return "Duplicate MC Number: {$mc}";
        }

        return null;
    }

    /**
     * Format EIN number to XX-XXXXXXX format.
     */
    protected function formatEIN(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        // Remove all non-numeric characters
        $digits = preg_replace('/[^0-9]/', '', $value);

        if (strlen($digits) !== 9) {
            return $value; // Return as-is if not 9 digits
        }

        return substr($digits, 0, 2) . '-' . substr($digits, 2);
    }

    /**
     * Find membership plan by name or ID.
     */
    protected function findMembership(array $row): ?Membership
    {
        $membershipValue = trim($row['membership'] ?? $row['id_plan'] ?? '');

        if (empty($membershipValue)) {
            // Return first active membership as default
            return Membership::where('status', 1)->first();
        }

        // Try by ID first
        if (is_numeric($membershipValue)) {
            $membership = Membership::find((int) $membershipValue);
            if ($membership) {
                return $membership;
            }
        }

        // Try by name
        return Membership::where('name', 'LIKE', "%{$membershipValue}%")
            ->where('status', 1)
            ->first();
    }

    /**
     * Normalize status (0=inactive, 1=active, 2=pending, 3=pending_validation, 4=rejected).
     */
    protected function normalizeStatus(?string $value): int
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
            'pending_validation' => 3,
            '3' => 3,
            'rejected' => 4,
            '4' => 4,
        ];

        return $mapping[$value] ?? 1;
    }
}
