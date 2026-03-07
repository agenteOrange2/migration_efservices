<?php

namespace App\Traits;

use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Admin\Vehicle\VehicleMake;
use App\Models\Admin\Vehicle\VehicleType;
use App\Models\Admin\Vehicle\VehicleDriverAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Vehicle Management Trait
 * 
 * Proporciona funcionalidad para gestionar vehículos en el registro de drivers.
 * Extraído del DriverApplicationStep para mejorar la mantenibilidad.
 */
trait VehicleManagementTrait
{
    /**
     * Lista de vehículos existentes del driver
     */
    public array $existingVehicles = [];

    /**
     * ID del vehículo seleccionado
     */
    public ?int $selectedVehicleId = null;

    /**
     * Cargar vehículos existentes para el driver
     *
     * @param int $driverId
     * @param int|null $carrierId
     * @return void
     */
    public function loadExistingVehicles(int $driverId, ?int $carrierId = null): void
    {
        try {
            $query = Vehicle::query();

            // Si hay carrier, filtrar por carrier
            if ($carrierId) {
                $query->where('carrier_id', $carrierId);
            }

            // Obtener vehículos asignados al driver o sin asignar
            $query->where(function ($q) use ($driverId) {
                $q->where('user_driver_detail_id', $driverId)
                  ->orWhereNull('user_driver_detail_id');
            });

            $this->existingVehicles = $query
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($vehicle) {
                    return [
                        'id' => $vehicle->id,
                        'make' => $vehicle->make,
                        'model' => $vehicle->model,
                        'year' => $vehicle->year,
                        'vin' => $vehicle->vin,
                        'type' => $vehicle->type,
                        'status' => $vehicle->status,
                        'driver_type' => $vehicle->driver_type,
                        'display_name' => "{$vehicle->year} {$vehicle->make} {$vehicle->model} - {$vehicle->vin}",
                    ];
                })
                ->toArray();

            Log::info('Vehicles loaded', [
                'driver_id' => $driverId,
                'count' => count($this->existingVehicles),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to load vehicles', [
                'driver_id' => $driverId,
                'error' => $e->getMessage(),
            ]);
            $this->existingVehicles = [];
        }
    }

    /**
     * Crear un nuevo vehículo
     *
     * @param array $data
     * @param int $carrierId
     * @param int|null $driverId
     * @return Vehicle
     * @throws \Exception
     */
    public function createVehicle(array $data, int $carrierId, ?int $driverId = null): Vehicle
    {
        return DB::transaction(function () use ($data, $carrierId, $driverId) {
            $vehicleData = $this->prepareVehicleData($data, $carrierId, $driverId);
            
            $vehicle = Vehicle::create($vehicleData);

            Log::info('Vehicle created', [
                'vehicle_id' => $vehicle->id,
                'carrier_id' => $carrierId,
                'driver_id' => $driverId,
            ]);

            return $vehicle;
        });
    }

    /**
     * Actualizar un vehículo existente
     *
     * @param int $vehicleId
     * @param array $data
     * @return Vehicle
     * @throws \Exception
     */
    public function updateVehicle(int $vehicleId, array $data): Vehicle
    {
        return DB::transaction(function () use ($vehicleId, $data) {
            $vehicle = Vehicle::findOrFail($vehicleId);
            
            $updateData = $this->prepareVehicleUpdateData($data);
            $vehicle->update($updateData);

            Log::info('Vehicle updated', [
                'vehicle_id' => $vehicleId,
            ]);

            return $vehicle->fresh();
        });
    }

    /**
     * Asignar un vehículo a un driver
     *
     * @param int $vehicleId
     * @param int $driverId
     * @param string $driverType owner_operator, third_party, company_driver
     * @return VehicleDriverAssignment
     * @throws \Exception
     */
    public function assignVehicleToDriver(int $vehicleId, int $driverId, string $driverType = 'company_driver'): VehicleDriverAssignment
    {
        return DB::transaction(function () use ($vehicleId, $driverId, $driverType) {
            $vehicle = Vehicle::findOrFail($vehicleId);

            // Desactivar asignaciones anteriores del mismo vehículo
            VehicleDriverAssignment::where('vehicle_id', $vehicleId)
                ->where('status', 'active')
                ->update(['status' => 'inactive', 'end_date' => now()]);

            // Crear nueva asignación
            $assignment = VehicleDriverAssignment::create([
                'vehicle_id' => $vehicleId,
                'user_driver_detail_id' => $driverId,
                'driver_type' => $driverType,
                'status' => 'active',
                'start_date' => now(),
            ]);

            // Actualizar el vehículo
            $vehicle->update([
                'user_driver_detail_id' => $driverId,
                'driver_type' => $driverType,
            ]);

            Log::info('Vehicle assigned to driver', [
                'vehicle_id' => $vehicleId,
                'driver_id' => $driverId,
                'driver_type' => $driverType,
                'assignment_id' => $assignment->id,
            ]);

            return $assignment;
        });
    }

    /**
     * Desasignar un vehículo de un driver
     *
     * @param int $vehicleId
     * @param int $driverId
     * @return bool
     */
    public function unassignVehicleFromDriver(int $vehicleId, int $driverId): bool
    {
        try {
            return DB::transaction(function () use ($vehicleId, $driverId) {
                // Desactivar asignación
                VehicleDriverAssignment::where('vehicle_id', $vehicleId)
                    ->where('user_driver_detail_id', $driverId)
                    ->where('status', 'active')
                    ->update(['status' => 'inactive', 'end_date' => now()]);

                // Limpiar referencia en el vehículo
                Vehicle::where('id', $vehicleId)
                    ->where('user_driver_detail_id', $driverId)
                    ->update(['user_driver_detail_id' => null]);

                Log::info('Vehicle unassigned from driver', [
                    'vehicle_id' => $vehicleId,
                    'driver_id' => $driverId,
                ]);

                return true;
            });
        } catch (\Exception $e) {
            Log::error('Failed to unassign vehicle', [
                'vehicle_id' => $vehicleId,
                'driver_id' => $driverId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }


    /**
     * Obtener reglas de validación para vehículos
     *
     * @param string $driverType
     * @return array
     */
    public function getVehicleValidationRules(string $driverType = 'company_driver'): array
    {
        $baseRules = [
            'vehicle_make' => 'required|string|max:100',
            'vehicle_model' => 'required|string|max:100',
            'vehicle_year' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'vehicle_vin' => 'required|string|max:17',
            'vehicle_type' => 'required|string',
            'vehicle_fuel_type' => 'required|string',
            'vehicle_registration_state' => 'required|string',
            'vehicle_registration_number' => 'required|string',
            'vehicle_registration_expiration_date' => 'required|date',
        ];

        $optionalRules = [
            'vehicle_company_unit_number' => 'nullable|string|max:50',
            'vehicle_gvwr' => 'nullable|string|max:50',
            'vehicle_tire_size' => 'nullable|string|max:50',
            'vehicle_irp_apportioned_plate' => 'boolean',
            'vehicle_permanent_tag' => 'boolean',
            'vehicle_location' => 'nullable|string|max:255',
            'vehicle_notes' => 'nullable|string',
        ];

        return array_merge($baseRules, $optionalRules);
    }

    /**
     * Obtener mensajes de validación personalizados para vehículos
     *
     * @return array
     */
    public function getVehicleValidationMessages(): array
    {
        return [
            'vehicle_make.required' => 'Vehicle make is required',
            'vehicle_model.required' => 'Vehicle model is required',
            'vehicle_year.required' => 'Vehicle year is required',
            'vehicle_year.min' => 'Vehicle year must be at least 1900',
            'vehicle_year.max' => 'Vehicle year cannot be in the future',
            'vehicle_vin.required' => 'VIN is required',
            'vehicle_vin.max' => 'VIN cannot exceed 17 characters',
            'vehicle_type.required' => 'Vehicle type is required',
            'vehicle_fuel_type.required' => 'Fuel type is required',
            'vehicle_registration_state.required' => 'Registration state is required',
            'vehicle_registration_number.required' => 'Registration number is required',
            'vehicle_registration_expiration_date.required' => 'Registration expiration date is required',
            'vehicle_registration_expiration_date.date' => 'Invalid registration expiration date',
        ];
    }

    /**
     * Seleccionar un vehículo existente y cargar sus datos
     *
     * @param int $vehicleId
     * @return array|null
     */
    public function selectExistingVehicle(int $vehicleId): ?array
    {
        $vehicle = Vehicle::find($vehicleId);

        if (!$vehicle) {
            return null;
        }

        $this->selectedVehicleId = $vehicleId;

        return [
            'vehicle_id' => $vehicle->id,
            'vehicle_make' => $vehicle->make,
            'vehicle_model' => $vehicle->model,
            'vehicle_year' => $vehicle->year,
            'vehicle_vin' => $vehicle->vin,
            'vehicle_type' => $vehicle->type,
            'vehicle_company_unit_number' => $vehicle->company_unit_number,
            'vehicle_gvwr' => $vehicle->gvwr,
            'vehicle_tire_size' => $vehicle->tire_size,
            'vehicle_fuel_type' => $vehicle->fuel_type,
            'vehicle_irp_apportioned_plate' => $vehicle->irp_apportioned_plate,
            'vehicle_registration_state' => $vehicle->registration_state,
            'vehicle_registration_number' => $vehicle->registration_number,
            'vehicle_registration_expiration_date' => $vehicle->registration_expiration_date?->format('Y-m-d'),
            'vehicle_permanent_tag' => $vehicle->permanent_tag,
            'vehicle_location' => $vehicle->location,
            'vehicle_notes' => $vehicle->notes,
        ];
    }

    /**
     * Limpiar selección de vehículo
     *
     * @return void
     */
    public function clearVehicleSelection(): void
    {
        $this->selectedVehicleId = null;
    }

    /**
     * Verificar si está en modo de nuevo vehículo
     *
     * @return bool
     */
    public function isNewVehicleMode(): bool
    {
        return $this->selectedVehicleId === null;
    }

    /**
     * Preparar datos del vehículo para creación
     *
     * @param array $data
     * @param int $carrierId
     * @param int|null $driverId
     * @return array
     */
    protected function prepareVehicleData(array $data, int $carrierId, ?int $driverId = null): array
    {
        return [
            'carrier_id' => $carrierId,
            'user_driver_detail_id' => $driverId,
            'make' => $data['vehicle_make'] ?? $data['make'] ?? null,
            'model' => $data['vehicle_model'] ?? $data['model'] ?? null,
            'year' => $data['vehicle_year'] ?? $data['year'] ?? null,
            'vin' => $data['vehicle_vin'] ?? $data['vin'] ?? null,
            'type' => $data['vehicle_type'] ?? $data['type'] ?? 'truck',
            'company_unit_number' => $data['vehicle_company_unit_number'] ?? $data['company_unit_number'] ?? null,
            'gvwr' => $data['vehicle_gvwr'] ?? $data['gvwr'] ?? null,
            'tire_size' => $data['vehicle_tire_size'] ?? $data['tire_size'] ?? null,
            'fuel_type' => $data['vehicle_fuel_type'] ?? $data['fuel_type'] ?? 'diesel',
            'irp_apportioned_plate' => $data['vehicle_irp_apportioned_plate'] ?? $data['irp_apportioned_plate'] ?? false,
            'registration_state' => $data['vehicle_registration_state'] ?? $data['registration_state'] ?? null,
            'registration_number' => $data['vehicle_registration_number'] ?? $data['registration_number'] ?? null,
            'registration_expiration_date' => $data['vehicle_registration_expiration_date'] ?? $data['registration_expiration_date'] ?? null,
            'permanent_tag' => $data['vehicle_permanent_tag'] ?? $data['permanent_tag'] ?? false,
            'location' => $data['vehicle_location'] ?? $data['location'] ?? null,
            'driver_type' => $data['driver_type'] ?? 'company_driver',
            'notes' => $data['vehicle_notes'] ?? $data['notes'] ?? null,
            'status' => Vehicle::STATUS_PENDING,
        ];
    }

    /**
     * Preparar datos del vehículo para actualización
     *
     * @param array $data
     * @return array
     */
    protected function prepareVehicleUpdateData(array $data): array
    {
        $updateData = [];

        $fieldMappings = [
            'vehicle_make' => 'make',
            'vehicle_model' => 'model',
            'vehicle_year' => 'year',
            'vehicle_vin' => 'vin',
            'vehicle_type' => 'type',
            'vehicle_company_unit_number' => 'company_unit_number',
            'vehicle_gvwr' => 'gvwr',
            'vehicle_tire_size' => 'tire_size',
            'vehicle_fuel_type' => 'fuel_type',
            'vehicle_irp_apportioned_plate' => 'irp_apportioned_plate',
            'vehicle_registration_state' => 'registration_state',
            'vehicle_registration_number' => 'registration_number',
            'vehicle_registration_expiration_date' => 'registration_expiration_date',
            'vehicle_permanent_tag' => 'permanent_tag',
            'vehicle_location' => 'location',
            'vehicle_notes' => 'notes',
        ];

        foreach ($fieldMappings as $inputKey => $dbKey) {
            if (array_key_exists($inputKey, $data)) {
                $updateData[$dbKey] = $data[$inputKey];
            } elseif (array_key_exists($dbKey, $data)) {
                $updateData[$dbKey] = $data[$dbKey];
            }
        }

        return $updateData;
    }

    /**
     * Obtener lista de marcas de vehículos
     *
     * @return array
     */
    public function getVehicleMakesList(): array
    {
        return VehicleMake::orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Obtener lista de tipos de vehículos
     *
     * @return array
     */
    public function getVehicleTypesList(): array
    {
        return VehicleType::orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }
}
