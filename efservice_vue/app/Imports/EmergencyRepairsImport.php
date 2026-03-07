<?php

namespace App\Imports;

use App\Models\Admin\Vehicle\Vehicle;
use App\Models\EmergencyRepair;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class EmergencyRepairsImport extends BaseImport
{
    /**
     * Process the collection of rows.
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowData = $row->toArray();
            $rowNumber = $index + 2;

            // Find vehicle by unit_number or VIN
            $vehicle = $this->findVehicle($rowData);

            if (!$vehicle) {
                $this->addSkippedRow(
                    $rowNumber,
                    'Vehicle not found: ' . ($rowData['vehicle_unit_number'] ?? $rowData['vehicle_vin'] ?? 'N/A'),
                    $rowData
                );
                continue;
            }

            // Check for duplicate
            if ($this->isDuplicateForVehicle($vehicle->id, $rowData)) {
                $this->addSkippedRow($rowNumber, 'Duplicate repair record', $rowData);
                continue;
            }

            try {
                $repair = EmergencyRepair::create([
                    'vehicle_id' => $vehicle->id,
                    'repair_name' => trim($rowData['repair_name'] ?? '') ?: 'Imported Repair',
                    'repair_date' => $this->parseDate($rowData['repair_date'] ?? null),
                    'cost' => $this->parseDecimal($rowData['cost'] ?? null),
                    'odometer' => $this->parseInt($rowData['odometer'] ?? null),
                    'status' => $this->normalizeStatus($rowData['status'] ?? 'completed'),
                    'description' => trim($rowData['description'] ?? '') ?: null,
                    'notes' => trim($rowData['notes'] ?? '') ?: null,
                ]);

                $this->addImportedRow($repair->id);

                Log::info('Emergency repair imported', [
                    'row' => $rowNumber,
                    'repair_id' => $repair->id,
                    'vehicle_id' => $vehicle->id,
                ]);
            } catch (\Exception $e) {
                $this->addSkippedRow($rowNumber, $e->getMessage(), $rowData);

                Log::error('Emergency repair import failed', [
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
            'repair_name' => 'required|string|max:255',
            'repair_date' => 'required',
            'cost' => 'nullable|numeric|min:0',
            'odometer' => 'nullable|integer|min:0',
            'status' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get the unique key for a row.
     */
    protected function getUniqueKey(array $row): string
    {
        $vehicleRef = $row['vehicle_unit_number'] ?? $row['vehicle_vin'] ?? '';
        $date = $row['repair_date'] ?? '';
        $name = $row['repair_name'] ?? '';

        return md5($vehicleRef . '|' . $date . '|' . $name);
    }

    /**
     * Check if a row is a duplicate.
     */
    protected function isDuplicate(array $row): bool
    {
        return false;
    }

    /**
     * Find vehicle by unit_number or VIN.
     */
    protected function findVehicle(array $row): ?Vehicle
    {
        if (!empty($row['vehicle_unit_number'])) {
            $vehicle = Vehicle::where('carrier_id', $this->carrierId)
                ->where('company_unit_number', trim($row['vehicle_unit_number']))
                ->first();

            if ($vehicle) {
                return $vehicle;
            }
        }

        if (!empty($row['vehicle_vin'])) {
            return Vehicle::where('carrier_id', $this->carrierId)
                ->where('vin', $this->cleanUppercase($row['vehicle_vin']))
                ->first();
        }

        return null;
    }

    /**
     * Check for duplicate repair record.
     */
    protected function isDuplicateForVehicle(int $vehicleId, array $row): bool
    {
        $repairDate = $this->parseDate($row['repair_date'] ?? null);
        $repairName = trim($row['repair_name'] ?? '') ?: null;

        if (!$repairDate) {
            return false;
        }

        $query = EmergencyRepair::where('vehicle_id', $vehicleId)
            ->whereDate('repair_date', $repairDate);

        if ($repairName) {
            $query->where('repair_name', $repairName);
        }

        return $query->exists();
    }

    /**
     * Normalize status value.
     */
    protected function normalizeStatus(?string $value): string
    {
        if (empty($value)) {
            return 'completed';
        }

        $value = strtolower(trim($value));

        $mapping = [
            'completed' => 'completed',
            'done' => 'completed',
            'in_progress' => 'in_progress',
            'in-progress' => 'in_progress',
            'inprogress' => 'in_progress',
            'pending' => 'pending',
            'scheduled' => 'pending',
        ];

        return $mapping[$value] ?? 'completed';
    }
}
