<?php

namespace App\Imports;

use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Admin\Vehicle\VehicleMaintenance;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class VehicleMaintenanceImport extends BaseImport
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

            // Check for duplicate (same vehicle, same date, same service)
            if ($this->isDuplicateForVehicle($vehicle->id, $rowData)) {
                $this->addSkippedRow($rowNumber, 'Duplicate maintenance record', $rowData);
                continue;
            }

            try {
                $maintenance = VehicleMaintenance::create([
                    'vehicle_id' => $vehicle->id,
                    'unit' => $vehicle->company_unit_number,
                    'service_date' => $this->parseDate($rowData['service_date'] ?? null),
                    'next_service_date' => $this->parseDate($rowData['next_service_date'] ?? null),
                    'service_tasks' => trim($rowData['service_tasks'] ?? '') ?: null,
                    'vendor_mechanic' => trim($rowData['vendor_mechanic'] ?? '') ?: null,
                    'description' => trim($rowData['description'] ?? '') ?: null,
                    'cost' => $this->parseDecimal($rowData['cost'] ?? null),
                    'odometer' => $this->parseInt($rowData['odometer'] ?? null),
                    'status' => $this->normalizeStatus($rowData['status'] ?? 'completed'),
                    'is_historical' => true,
                    'created_by' => $this->importedBy,
                ]);

                $this->addImportedRow($maintenance->id);

                Log::info('Maintenance record imported', [
                    'row' => $rowNumber,
                    'maintenance_id' => $maintenance->id,
                    'vehicle_id' => $vehicle->id,
                ]);
            } catch (\Exception $e) {
                $this->addSkippedRow($rowNumber, $e->getMessage(), $rowData);

                Log::error('Maintenance import failed', [
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
            'service_date' => 'required',
            'service_tasks' => 'nullable|string|max:500',
            'vendor_mechanic' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'cost' => 'nullable|numeric|min:0',
            'odometer' => 'nullable|integer|min:0',
        ];
    }

    /**
     * Get the unique key for a row.
     */
    protected function getUniqueKey(array $row): string
    {
        $vehicleRef = $row['vehicle_unit_number'] ?? $row['vehicle_vin'] ?? '';
        $date = $row['service_date'] ?? '';
        $tasks = $row['service_tasks'] ?? '';

        return md5($vehicleRef . '|' . $date . '|' . $tasks);
    }

    /**
     * Check if a row is a duplicate (not used directly, use isDuplicateForVehicle).
     */
    protected function isDuplicate(array $row): bool
    {
        return false; // We check per vehicle
    }

    /**
     * Find vehicle by unit_number or VIN.
     */
    protected function findVehicle(array $row): ?Vehicle
    {
        // Try by unit_number first
        if (!empty($row['vehicle_unit_number'])) {
            $vehicle = Vehicle::where('carrier_id', $this->carrierId)
                ->where('company_unit_number', trim($row['vehicle_unit_number']))
                ->first();

            if ($vehicle) {
                return $vehicle;
            }
        }

        // Then try by VIN
        if (!empty($row['vehicle_vin'])) {
            return Vehicle::where('carrier_id', $this->carrierId)
                ->where('vin', $this->cleanUppercase($row['vehicle_vin']))
                ->first();
        }

        return null;
    }

    /**
     * Check for duplicate maintenance record for a vehicle.
     */
    protected function isDuplicateForVehicle(int $vehicleId, array $row): bool
    {
        $serviceDate = $this->parseDate($row['service_date'] ?? null);
        $serviceTasks = trim($row['service_tasks'] ?? '') ?: null;

        if (!$serviceDate) {
            return false;
        }

        $query = VehicleMaintenance::where('vehicle_id', $vehicleId)
            ->whereDate('service_date', $serviceDate);

        if ($serviceTasks) {
            $query->where('service_tasks', $serviceTasks);
        }

        return $query->exists();
    }

    /**
     * Normalize status value.
     */
    protected function normalizeStatus(?string $value): bool
    {
        if (empty($value)) {
            return true; // Default to completed
        }

        $value = strtolower(trim($value));

        return in_array($value, ['completed', 'done', '1', 'true', 'yes']);
    }
}
