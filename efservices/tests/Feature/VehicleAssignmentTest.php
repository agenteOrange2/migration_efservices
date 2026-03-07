<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Carrier;
use App\Models\UserDriverDetail;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Admin\Vehicle\VehicleDriverAssignment;
use App\Services\Vehicle\VehicleAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class VehicleAssignmentTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected VehicleAssignmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new VehicleAssignmentService();
    }

    /**
     * Test que un vehículo puede ser asignado como company driver
     */
    public function test_vehicle_can_be_assigned_as_company_driver(): void
    {
        $carrier = Carrier::factory()->create();
        $vehicle = Vehicle::factory()->create(['carrier_id' => $carrier->id]);
        $driver = UserDriverDetail::factory()->create([
            'carrier_id' => $carrier->id,
            'status' => UserDriverDetail::STATUS_ACTIVE,
            'application_completed' => true,
        ]);

        $assignment = $this->service->assignAsCompanyDriver($vehicle, $driver);

        $this->assertInstanceOf(VehicleDriverAssignment::class, $assignment);
        $this->assertEquals('company_driver', $assignment->assignment_type);
        $this->assertEquals('active', $assignment->status);
        $this->assertEquals($vehicle->id, $assignment->vehicle_id);
        $this->assertEquals($driver->id, $assignment->user_driver_detail_id);
    }

    /**
     * Test que un vehículo puede ser asignado como owner operator
     */
    public function test_vehicle_can_be_assigned_as_owner_operator(): void
    {
        $carrier = Carrier::factory()->create();
        $vehicle = Vehicle::factory()->create(['carrier_id' => $carrier->id]);
        $driver = UserDriverDetail::factory()->create([
            'carrier_id' => $carrier->id,
            'status' => UserDriverDetail::STATUS_ACTIVE,
            'application_completed' => true,
        ]);

        $ownerOperatorData = [
            'ownership_percentage' => 100,
            'insurance_policy_number' => 'INS-123456',
            'insurance_expiry_date' => now()->addYear(),
        ];

        $assignment = $this->service->assignAsOwnerOperator($vehicle, $driver, $ownerOperatorData);

        $this->assertInstanceOf(VehicleDriverAssignment::class, $assignment);
        $this->assertEquals('owner_operator', $assignment->assignment_type);
        $this->assertEquals('active', $assignment->status);
        $this->assertNotNull($assignment->ownerOperatorDetail);
        $this->assertEquals(100, $assignment->ownerOperatorDetail->ownership_percentage);
    }

    /**
     * Test que un vehículo puede ser asignado como third party
     */
    public function test_vehicle_can_be_assigned_as_third_party(): void
    {
        $carrier = Carrier::factory()->create();
        $vehicle = Vehicle::factory()->create(['carrier_id' => $carrier->id]);
        $driver = UserDriverDetail::factory()->create([
            'carrier_id' => $carrier->id,
            'status' => UserDriverDetail::STATUS_ACTIVE,
            'application_completed' => true,
        ]);

        $thirdPartyData = [
            'owner_name' => $this->faker->name,
            'owner_email' => $this->faker->email,
            'owner_phone' => $this->faker->phoneNumber,
        ];

        $assignment = $this->service->assignAsThirdParty($vehicle, $driver, $thirdPartyData);

        $this->assertInstanceOf(VehicleDriverAssignment::class, $assignment);
        $this->assertEquals('third_party', $assignment->assignment_type);
        $this->assertEquals('pending_verification', $assignment->status);
        $this->assertNotNull($assignment->thirdPartyDetail);
        $this->assertEquals($thirdPartyData['owner_email'], $assignment->thirdPartyDetail->owner_email);
    }

    /**
     * Test que no se puede asignar un vehículo ya asignado
     */
    public function test_cannot_assign_already_assigned_vehicle(): void
    {
        $carrier = Carrier::factory()->create();
        $vehicle = Vehicle::factory()->create(['carrier_id' => $carrier->id]);
        $driver1 = UserDriverDetail::factory()->create([
            'carrier_id' => $carrier->id,
            'status' => UserDriverDetail::STATUS_ACTIVE,
            'application_completed' => true,
        ]);
        $driver2 = UserDriverDetail::factory()->create([
            'carrier_id' => $carrier->id,
            'status' => UserDriverDetail::STATUS_ACTIVE,
            'application_completed' => true,
        ]);

        // Primera asignación
        $this->service->assignAsCompanyDriver($vehicle, $driver1);

        // Intentar segunda asignación
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Vehicle is already assigned');

        $this->service->assignAsCompanyDriver($vehicle, $driver2);
    }

    /**
     * Test que un conductor no puede tener múltiples asignaciones activas
     */
    public function test_driver_cannot_have_multiple_active_assignments(): void
    {
        $carrier = Carrier::factory()->create();
        $vehicle1 = Vehicle::factory()->create(['carrier_id' => $carrier->id]);
        $vehicle2 = Vehicle::factory()->create(['carrier_id' => $carrier->id]);
        $driver = UserDriverDetail::factory()->create([
            'carrier_id' => $carrier->id,
            'status' => UserDriverDetail::STATUS_ACTIVE,
            'application_completed' => true,
        ]);

        // Primera asignación
        $this->service->assignAsCompanyDriver($vehicle1, $driver);

        // Intentar segunda asignación
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Driver already has an active vehicle assignment');

        $this->service->assignAsCompanyDriver($vehicle2, $driver);
    }

    /**
     * Test que una asignación puede ser terminada
     */
    public function test_assignment_can_be_terminated(): void
    {
        $carrier = Carrier::factory()->create();
        $vehicle = Vehicle::factory()->create(['carrier_id' => $carrier->id]);
        $driver = UserDriverDetail::factory()->create([
            'carrier_id' => $carrier->id,
            'status' => UserDriverDetail::STATUS_ACTIVE,
            'application_completed' => true,
        ]);

        $assignment = $this->service->assignAsCompanyDriver($vehicle, $driver);

        $terminatedAssignment = $this->service->terminateAssignment($assignment, [
            'termination_reason' => 'Driver resigned',
        ]);

        $this->assertEquals('terminated', $terminatedAssignment->status);
        $this->assertNotNull($terminatedAssignment->end_date);
        $this->assertEquals('Driver resigned', $terminatedAssignment->termination_reason);
    }

    /**
     * Test que verifica si un vehículo tiene asignación activa
     */
    public function test_checks_if_vehicle_has_active_assignment(): void
    {
        $carrier = Carrier::factory()->create();
        $vehicle = Vehicle::factory()->create(['carrier_id' => $carrier->id]);
        $driver = UserDriverDetail::factory()->create([
            'carrier_id' => $carrier->id,
            'status' => UserDriverDetail::STATUS_ACTIVE,
            'application_completed' => true,
        ]);

        $this->assertFalse($this->service->hasActiveAssignment($vehicle));

        $this->service->assignAsCompanyDriver($vehicle, $driver);

        $this->assertTrue($this->service->hasActiveAssignment($vehicle));
    }

    /**
     * Test que verifica si un conductor tiene asignación activa
     */
    public function test_checks_if_driver_has_active_assignment(): void
    {
        $carrier = Carrier::factory()->create();
        $vehicle = Vehicle::factory()->create(['carrier_id' => $carrier->id]);
        $driver = UserDriverDetail::factory()->create([
            'carrier_id' => $carrier->id,
            'status' => UserDriverDetail::STATUS_ACTIVE,
            'application_completed' => true,
        ]);

        $this->assertFalse($this->service->driverHasActiveAssignment($driver));

        $this->service->assignAsCompanyDriver($vehicle, $driver);

        $this->assertTrue($this->service->driverHasActiveAssignment($driver));
    }

    /**
     * Test que obtiene la asignación activa de un vehículo
     */
    public function test_gets_active_assignment_for_vehicle(): void
    {
        $carrier = Carrier::factory()->create();
        $vehicle = Vehicle::factory()->create(['carrier_id' => $carrier->id]);
        $driver = UserDriverDetail::factory()->create([
            'carrier_id' => $carrier->id,
            'status' => UserDriverDetail::STATUS_ACTIVE,
            'application_completed' => true,
        ]);

        $this->assertNull($this->service->getActiveAssignment($vehicle));

        $assignment = $this->service->assignAsCompanyDriver($vehicle, $driver);

        $activeAssignment = $this->service->getActiveAssignment($vehicle);

        $this->assertNotNull($activeAssignment);
        $this->assertEquals($assignment->id, $activeAssignment->id);
    }

    /**
     * Test que obtiene el historial de asignaciones de un vehículo
     */
    public function test_gets_vehicle_assignment_history(): void
    {
        $carrier = Carrier::factory()->create();
        $vehicle = Vehicle::factory()->create(['carrier_id' => $carrier->id]);
        $driver = UserDriverDetail::factory()->create([
            'carrier_id' => $carrier->id,
            'status' => UserDriverDetail::STATUS_ACTIVE,
            'application_completed' => true,
        ]);

        $assignment = $this->service->assignAsCompanyDriver($vehicle, $driver);
        $this->service->terminateAssignment($assignment);

        $history = $this->service->getVehicleAssignmentHistory($vehicle);

        $this->assertCount(1, $history);
        $this->assertEquals($assignment->id, $history->first()->id);
    }

    /**
     * Test que verifica si un conductor puede ser asignado
     */
    public function test_checks_if_driver_can_be_assigned(): void
    {
        $carrier = Carrier::factory()->create();
        $vehicle = Vehicle::factory()->create(['carrier_id' => $carrier->id]);
        
        // Driver inactivo
        $inactiveDriver = UserDriverDetail::factory()->create([
            'carrier_id' => $carrier->id,
            'status' => UserDriverDetail::STATUS_INACTIVE,
        ]);

        $result = $this->service->canAssignDriver($inactiveDriver, $vehicle);
        $this->assertFalse($result['can_assign']);
        $this->assertEquals('Driver is not active', $result['reason']);

        // Driver activo pero sin aplicación completa
        $incompleteDriver = UserDriverDetail::factory()->create([
            'carrier_id' => $carrier->id,
            'status' => UserDriverDetail::STATUS_ACTIVE,
            'application_completed' => false,
        ]);

        $result = $this->service->canAssignDriver($incompleteDriver, $vehicle);
        $this->assertFalse($result['can_assign']);
        $this->assertEquals('Driver application is not completed', $result['reason']);

        // Driver válido
        $validDriver = UserDriverDetail::factory()->create([
            'carrier_id' => $carrier->id,
            'status' => UserDriverDetail::STATUS_ACTIVE,
            'application_completed' => true,
        ]);

        $result = $this->service->canAssignDriver($validDriver, $vehicle);
        $this->assertTrue($result['can_assign']);
        $this->assertNull($result['reason']);
    }

    /**
     * Test que no se puede asignar driver de diferente carrier
     */
    public function test_cannot_assign_driver_from_different_carrier(): void
    {
        $carrier1 = Carrier::factory()->create();
        $carrier2 = Carrier::factory()->create();
        
        $vehicle = Vehicle::factory()->create(['carrier_id' => $carrier1->id]);
        $driver = UserDriverDetail::factory()->create([
            'carrier_id' => $carrier2->id,
            'status' => UserDriverDetail::STATUS_ACTIVE,
            'application_completed' => true,
        ]);

        $result = $this->service->canAssignDriver($driver, $vehicle);
        
        $this->assertFalse($result['can_assign']);
        $this->assertEquals('Driver and vehicle belong to different carriers', $result['reason']);
    }
}
