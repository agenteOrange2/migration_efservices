<?php

namespace App\Imports;

use App\Models\Admin\Vehicle\Vehicle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class VehiclesImport extends BaseImport
{
    /**
     * Process the collection of rows.
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowData = $row->toArray();
            $rowNumber = $index + 2; // +2 for header row and 0-index

            // Check for duplicate
            $existingVehicle = $this->findDuplicateRecord($rowData);

            if ($existingVehicle) {
                if ($this->shouldUpdateDuplicates()) {
                    // Update existing record
                    try {
                        $this->updateRecord($existingVehicle, $rowData);
                        $this->addUpdatedRow($existingVehicle->id);

                        Log::info('Vehicle updated', [
                            'row' => $rowNumber,
                            'vehicle_id' => $existingVehicle->id,
                            'vin' => $existingVehicle->vin,
                        ]);
                    } catch (\Exception $e) {
                        $this->addSkippedRow($rowNumber, 'Update failed: ' . $e->getMessage(), $rowData);
                    }
                } else {
                    // Skip duplicate
                    $this->addSkippedRow($rowNumber, 'Duplicate VIN: ' . ($rowData['vin'] ?? 'N/A'), $rowData);
                }
                continue;
            }

            try {
                $vehicle = Vehicle::create([
                    'carrier_id' => $this->carrierId,
                    'company_unit_number' => trim($rowData['unit_number'] ?? '') ?: null,
                    'make' => trim($rowData['make'] ?? ''),
                    'model' => trim($rowData['model'] ?? ''),
                    'type' => trim($rowData['type'] ?? ''),
                    'year' => $this->parseInt($rowData['year'] ?? null),
                    'vin' => $this->cleanUppercase($rowData['vin'] ?? null),
                    'registration_number' => trim($rowData['registration_number'] ?? '') ?: null,
                    'registration_state' => trim($rowData['registration_state'] ?? '') ?: null,
                    'registration_expiration_date' => $this->parseDate($rowData['registration_expiration_date'] ?? null),
                    'driver_type' => $this->normalizeDriverType($rowData['driver_type'] ?? 'company'),
                    'status' => $this->normalizeStatus($rowData['status'] ?? 'active'),
                    'fuel_type' => trim($rowData['fuel_type'] ?? '') ?: 'diesel',
                    'gvwr' => $this->parseInt($rowData['gvwr'] ?? null),
                    'notes' => trim($rowData['notes'] ?? '') ?: null,
                ]);

                $this->addImportedRow($vehicle->id);

                Log::info('Vehicle imported', [
                    'row' => $rowNumber,
                    'vehicle_id' => $vehicle->id,
                    'vin' => $vehicle->vin,
                ]);
            } catch (\Exception $e) {
                $this->addSkippedRow($rowNumber, $e->getMessage(), $rowData);

                Log::error('Vehicle import failed', [
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
            'make' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'year' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'vin' => 'required|string|size:17',
            'fuel_type' => 'nullable|string|max:50',
            'unit_number' => 'nullable|string|max:50',
            'registration_number' => 'nullable|string|max:50',
            'registration_state' => 'nullable|string|max:10',
            'gvwr' => 'nullable|integer|min:0',
        ];
    }

    /**
     * Get the unique key for a row.
     */
    protected function getUniqueKey(array $row): string
    {
        return $this->cleanUppercase($row['vin'] ?? '') ?? '';
    }

    /**
     * Check if a row is a duplicate.
     */
    protected function isDuplicate(array $row): bool
    {
        return $this->findDuplicateRecord($row) !== null;
    }

    /**
     * Find duplicate record for update.
     */
    protected function findDuplicateRecord(array $row): ?object
    {
        $vin = $this->getUniqueKey($row);

        if (empty($vin)) {
            return null;
        }

        return Vehicle::where('carrier_id', $this->carrierId)
            ->where('vin', $vin)
            ->first();
    }

    /**
     * Update an existing vehicle with new data.
     */
    protected function updateRecord(object $record, array $row): void
    {
        $record->update([
            'company_unit_number' => trim($row['unit_number'] ?? '') ?: $record->company_unit_number,
            'make' => trim($row['make'] ?? '') ?: $record->make,
            'model' => trim($row['model'] ?? '') ?: $record->model,
            'type' => trim($row['type'] ?? '') ?: $record->type,
            'year' => $this->parseInt($row['year'] ?? null) ?? $record->year,
            'registration_number' => trim($row['registration_number'] ?? '') ?: $record->registration_number,
            'registration_state' => trim($row['registration_state'] ?? '') ?: $record->registration_state,
            'registration_expiration_date' => $this->parseDate($row['registration_expiration_date'] ?? null) ?? $record->registration_expiration_date,
            'driver_type' => $this->normalizeDriverType($row['driver_type'] ?? null) ?? $record->driver_type,
            'status' => $this->normalizeStatus($row['status'] ?? null) ?? $record->status,
            'fuel_type' => trim($row['fuel_type'] ?? '') ?: $record->fuel_type,
            'gvwr' => $this->parseInt($row['gvwr'] ?? null) ?? $record->gvwr,
            'notes' => trim($row['notes'] ?? '') ?: $record->notes,
        ]);
    }

    /**
     * Normalize driver type value.
     */
    protected function normalizeDriverType(?string $value): string
    {
        if (empty($value)) {
            return 'company';
        }

        $value = strtolower(trim($value));

        $mapping = [
            'owner_operator' => 'owner_operator',
            'owner-operator' => 'owner_operator',
            'owneroperator' => 'owner_operator',
            'oo' => 'owner_operator',
            'third_party' => 'third_party',
            'third-party' => 'third_party',
            'thirdparty' => 'third_party',
            'tp' => 'third_party',
            'company' => 'company',
            'comp' => 'company',
        ];

        return $mapping[$value] ?? 'company';
    }

    /**
     * Normalize status value.
     */
    protected function normalizeStatus(?string $value): string
    {
        if (empty($value)) {
            return 'active';
        }

        $value = strtolower(trim($value));

        $mapping = [
            'active' => 'active',
            'inactive' => 'inactive',
            'out_of_service' => 'out_of_service',
            'out-of-service' => 'out_of_service',
            'outofservice' => 'out_of_service',
            'oos' => 'out_of_service',
            'suspended' => 'suspended',
        ];

        return $mapping[$value] ?? 'active';
    }
}
