<?php

namespace App\Services\Vehicle;

use App\Models\UserDriverDetail;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Admin\Vehicle\VehicleDriverAssignment;
use App\Models\OwnerOperatorDetail;
use App\Models\ThirdPartyDetail;
use App\Services\BaseService;

/**
 * Vehicle Assignment Service
 * 
 * Maneja toda la lógica de negocio relacionada con la asignación de vehículos a conductores.
 */
class VehicleAssignmentService extends BaseService
{
    /**
     * Tipos de asignación válidos
     */
    const TYPE_COMPANY_DRIVER = 'company_driver';
    const TYPE_OWNER_OPERATOR = 'owner_operator';
    const TYPE_THIRD_PARTY = 'third_party';

    /**
     * Asignar vehículo a conductor (Company Driver)
     *
     * @param Vehicle $vehicle
     * @param UserDriverDetail $driver
     * @param array $data
     * @return VehicleDriverAssignment
     * @throws \Exception
     */
    public function assignAsCompanyDriver(Vehicle $vehicle, UserDriverDetail $driver, array $data = []): VehicleDriverAssignment
    {
        // Verificar que el vehículo no esté asignado
        if ($this->hasActiveAssignment($vehicle)) {
            throw new \Exception('Vehicle is already assigned to another driver');
        }

        // Verificar que el driver no tenga asignación activa
        if ($this->driverHasActiveAssignment($driver)) {
            throw new \Exception('Driver already has an active vehicle assignment');
        }

        return $this->executeInTransaction(function () use ($vehicle, $driver, $data) {
            // Crear asignación
            $assignment = VehicleDriverAssignment::create([
                'vehicle_id' => $vehicle->id,
                'user_driver_detail_id' => $driver->id,
                'assignment_type' => self::TYPE_COMPANY_DRIVER,
                'start_date' => $data['start_date'] ?? now(),
                'status' => 'active',
                'notes' => $data['notes'] ?? null,
            ]);

            // Disparar evento
            event(new \App\Events\VehicleAssigned($assignment));

            $this->logAction('Vehicle assigned as company driver', [
                'assignment_id' => $assignment->id,
                'vehicle_id' => $vehicle->id,
                'driver_id' => $driver->id,
            ]);

            return $assignment;
        });
    }

    /**
     * Asignar vehículo a conductor (Owner Operator)
     *
     * @param Vehicle $vehicle
     * @param UserDriverDetail $driver
     * @param array $ownerOperatorData
     * @return VehicleDriverAssignment
     * @throws \Exception
     */
    public function assignAsOwnerOperator(Vehicle $vehicle, UserDriverDetail $driver, array $ownerOperatorData): VehicleDriverAssignment
    {
        // Verificar que el vehículo no esté asignado
        if ($this->hasActiveAssignment($vehicle)) {
            throw new \Exception('Vehicle is already assigned to another driver');
        }

        // Verificar que el driver no tenga asignación activa
        if ($this->driverHasActiveAssignment($driver)) {
            throw new \Exception('Driver already has an active vehicle assignment');
        }

        return $this->executeInTransaction(function () use ($vehicle, $driver, $ownerOperatorData) {
            // Crear asignación
            $assignment = VehicleDriverAssignment::create([
                'vehicle_id' => $vehicle->id,
                'user_driver_detail_id' => $driver->id,
                'assignment_type' => self::TYPE_OWNER_OPERATOR,
                'start_date' => $ownerOperatorData['start_date'] ?? now(),
                'status' => 'active',
                'notes' => $ownerOperatorData['notes'] ?? null,
            ]);

            // Crear detalles de owner operator
            OwnerOperatorDetail::create([
                'vehicle_driver_assignment_id' => $assignment->id,
                'ownership_percentage' => $ownerOperatorData['ownership_percentage'] ?? 100,
                'lease_agreement_number' => $ownerOperatorData['lease_agreement_number'] ?? null,
                'insurance_policy_number' => $ownerOperatorData['insurance_policy_number'] ?? null,
                'insurance_expiry_date' => $ownerOperatorData['insurance_expiry_date'] ?? null,
            ]);

            $this->logAction('Vehicle assigned as owner operator', [
                'assignment_id' => $assignment->id,
                'vehicle_id' => $vehicle->id,
                'driver_id' => $driver->id,
            ]);

            return $assignment;
        });
    }

    /**
     * Asignar vehículo a conductor (Third Party)
     *
     * @param Vehicle $vehicle
     * @param UserDriverDetail $driver
     * @param array $thirdPartyData
     * @return VehicleDriverAssignment
     * @throws \Exception
     */
    public function assignAsThirdParty(Vehicle $vehicle, UserDriverDetail $driver, array $thirdPartyData): VehicleDriverAssignment
    {
        // Verificar que el vehículo no esté asignado
        if ($this->hasActiveAssignment($vehicle)) {
            throw new \Exception('Vehicle is already assigned to another driver');
        }

        // Verificar que el driver no tenga asignación activa
        if ($this->driverHasActiveAssignment($driver)) {
            throw new \Exception('Driver already has an active vehicle assignment');
        }

        return $this->executeInTransaction(function () use ($vehicle, $driver, $thirdPartyData) {
            // Crear asignación
            $assignment = VehicleDriverAssignment::create([
                'vehicle_id' => $vehicle->id,
                'user_driver_detail_id' => $driver->id,
                'assignment_type' => self::TYPE_THIRD_PARTY,
                'start_date' => $thirdPartyData['start_date'] ?? now(),
                'status' => 'pending_verification',
                'notes' => $thirdPartyData['notes'] ?? null,
            ]);

            // Crear detalles de third party
            ThirdPartyDetail::create([
                'vehicle_driver_assignment_id' => $assignment->id,
                'owner_name' => $thirdPartyData['owner_name'],
                'owner_email' => $thirdPartyData['owner_email'],
                'owner_phone' => $thirdPartyData['owner_phone'],
                'verification_status' => 'pending',
            ]);

            // TODO: Enviar email de verificación al propietario

            $this->logAction('Vehicle assigned as third party', [
                'assignment_id' => $assignment->id,
                'vehicle_id' => $vehicle->id,
                'driver_id' => $driver->id,
                'owner_email' => $thirdPartyData['owner_email'],
            ]);

            return $assignment;
        });
    }

    /**
     * Terminar asignación de vehículo
     *
     * @param VehicleDriverAssignment $assignment
     * @param array $data
     * @return VehicleDriverAssignment
     * @throws \Exception
     */
    public function terminateAssignment(VehicleDriverAssignment $assignment, array $data = []): VehicleDriverAssignment
    {
        return $this->executeInTransaction(function () use ($assignment, $data) {
            $assignment->update([
                'status' => 'terminated',
                'end_date' => $data['end_date'] ?? now(),
                'termination_reason' => $data['termination_reason'] ?? null,
                'terminated_by' => auth()->id(),
            ]);

            $this->logAction('Vehicle assignment terminated', [
                'assignment_id' => $assignment->id,
                'vehicle_id' => $assignment->vehicle_id,
                'driver_id' => $assignment->user_driver_detail_id,
                'terminated_by' => auth()->id(),
            ]);

            return $assignment->fresh();
        });
    }

    /**
     * Verificar si un vehículo tiene asignación activa
     *
     * @param Vehicle $vehicle
     * @return bool
     */
    public function hasActiveAssignment(Vehicle $vehicle): bool
    {
        return $vehicle->vehicleAssignments()
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Verificar si un conductor tiene asignación activa
     *
     * @param UserDriverDetail $driver
     * @return bool
     */
    public function driverHasActiveAssignment(UserDriverDetail $driver): bool
    {
        return $driver->vehicleAssignments()
            ->where('status', 'active')
            ->exists();
    }

    /**
     * Obtener asignación activa de un vehículo
     *
     * @param Vehicle $vehicle
     * @return VehicleDriverAssignment|null
     */
    public function getActiveAssignment(Vehicle $vehicle): ?VehicleDriverAssignment
    {
        return $vehicle->vehicleAssignments()
            ->where('status', 'active')
            ->with(['driverDetail.user', 'ownerOperatorDetail', 'thirdPartyDetail'])
            ->first();
    }

    /**
     * Obtener asignación activa de un conductor
     *
     * @param UserDriverDetail $driver
     * @return VehicleDriverAssignment|null
     */
    public function getDriverActiveAssignment(UserDriverDetail $driver): ?VehicleDriverAssignment
    {
        return $driver->vehicleAssignments()
            ->where('status', 'active')
            ->with(['vehicle', 'ownerOperatorDetail', 'thirdPartyDetail'])
            ->first();
    }

    /**
     * Obtener historial de asignaciones de un vehículo
     *
     * @param Vehicle $vehicle
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getVehicleAssignmentHistory(Vehicle $vehicle)
    {
        return $vehicle->vehicleAssignments()
            ->with(['driverDetail.user'])
            ->orderBy('start_date', 'desc')
            ->get();
    }

    /**
     * Obtener historial de asignaciones de un conductor
     *
     * @param UserDriverDetail $driver
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getDriverAssignmentHistory(UserDriverDetail $driver)
    {
        return $driver->vehicleAssignments()
            ->with(['vehicle'])
            ->orderBy('start_date', 'desc')
            ->get();
    }

    /**
     * Verificar si un conductor puede ser asignado a un vehículo
     *
     * @param UserDriverDetail $driver
     * @param Vehicle $vehicle
     * @return array ['can_assign' => bool, 'reason' => string|null]
     */
    public function canAssignDriver(UserDriverDetail $driver, Vehicle $vehicle): array
    {
        // Verificar que el driver esté activo
        if ($driver->status !== UserDriverDetail::STATUS_ACTIVE) {
            return ['can_assign' => false, 'reason' => 'Driver is not active'];
        }

        // Verificar que el driver tenga aplicación completa
        if (!$driver->application_completed) {
            return ['can_assign' => false, 'reason' => 'Driver application is not completed'];
        }

        // Verificar que el vehículo no esté asignado
        if ($this->hasActiveAssignment($vehicle)) {
            return ['can_assign' => false, 'reason' => 'Vehicle is already assigned'];
        }

        // Verificar que el driver no tenga asignación activa
        if ($this->driverHasActiveAssignment($driver)) {
            return ['can_assign' => false, 'reason' => 'Driver already has an active assignment'];
        }

        // Verificar que pertenezcan al mismo carrier
        if ($driver->carrier_id !== $vehicle->carrier_id) {
            return ['can_assign' => false, 'reason' => 'Driver and vehicle belong to different carriers'];
        }

        return ['can_assign' => true, 'reason' => null];
    }
}
