<?php

namespace Tests\Unit\Models\Admin;

use Tests\TestCase;
use App\Models\Carrier;
use App\Models\Membership;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Admin\Vehicle\VehicleMaintenance;
use App\Models\Admin\Vehicle\VehicleDocument;
use App\Models\Admin\Vehicle\VehicleDriverAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VehicleModelTest extends TestCase
{
    use RefreshDatabase;

    protected $carrier;
    protected $vehicle;

    protected function setUp(): void
    {
        parent::setUp();
        
        $membership = Membership::factory()->create();
        $this->carrier = Carrier::factory()->create(['id_plan' => $membership->id]);
        $this->vehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id
        ]);
    }

    /** @test */
    public function vehicle_has_required_fillable_attributes()
    {
        $vehicle = new Vehicle();
        $fillable = $vehicle->getFillable();
        
        $this->assertContains('carrier_id', $fillable);
        $this->assertContains('vin', $fillable);
        $this->assertContains('make', $fillable);
        $this->assertContains('model', $fillable);
        $this->assertContains('year', $fillable);
        $this->assertContains('status', $fillable);
    }

    /** @test */
    public function vehicle_belongs_to_carrier()
    {
        $this->assertInstanceOf(Carrier::class, $this->vehicle->carrier);
    }

    /** @test */
    public function vehicle_has_correct_status_constants()
    {
        $this->assertEquals(1, Vehicle::STATUS_ACTIVE);
        $this->assertEquals(0, Vehicle::STATUS_INACTIVE);
    }

    /** @test */
    public function vehicle_has_many_maintenances()
    {
        VehicleMaintenance::factory()->count(3)->create([
            'vehicle_id' => $this->vehicle->id
        ]);

        $this->assertCount(3, $this->vehicle->maintenances);
        $this->assertInstanceOf(VehicleMaintenance::class, $this->vehicle->maintenances->first());
    }

    /** @test */
    public function vehicle_has_many_documents()
    {
        VehicleDocument::factory()->count(5)->create([
            'vehicle_id' => $this->vehicle->id
        ]);

        $this->assertCount(5, $this->vehicle->documents);
    }

    /** @test */
    public function vehicle_has_many_driver_assignments()
    {
        VehicleDriverAssignment::factory()->count(2)->create([
            'vehicle_id' => $this->vehicle->id
        ]);

        $this->assertCount(2, $this->vehicle->driverAssignments);
    }

    /** @test */
    public function vehicle_can_be_created_with_factory()
    {
        $newVehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id
        ]);

        $this->assertInstanceOf(Vehicle::class, $newVehicle);
        $this->assertDatabaseHas('vehicles', [
            'id' => $newVehicle->id,
            'carrier_id' => $this->carrier->id
        ]);
    }

    /** @test */
    public function vehicle_full_name_accessor_works()
    {
        $vehicle = Vehicle::factory()->create([
            'make' => 'Ford',
            'model' => 'F-150',
            'year' => 2023,
            'carrier_id' => $this->carrier->id
        ]);

        $this->assertEquals('2023 Ford F-150', $vehicle->full_name);
    }

    /** @test */
    public function vehicle_status_text_accessor_works()
    {
        $activeVehicle = Vehicle::factory()->create([
            'status' => Vehicle::STATUS_ACTIVE,
            'carrier_id' => $this->carrier->id
        ]);

        $inactiveVehicle = Vehicle::factory()->create([
            'status' => Vehicle::STATUS_INACTIVE,
            'carrier_id' => $this->carrier->id
        ]);

        $this->assertEquals('Active', $activeVehicle->status_text);
        $this->assertEquals('Inactive', $inactiveVehicle->status_text);
    }

    /** @test */
    public function vehicle_scope_active_works()
    {
        Vehicle::factory()->count(4)->create([
            'status' => Vehicle::STATUS_ACTIVE,
            'carrier_id' => $this->carrier->id
        ]);
        
        Vehicle::factory()->count(3)->create([
            'status' => Vehicle::STATUS_INACTIVE,
            'carrier_id' => $this->carrier->id
        ]);

        $activeVehicles = Vehicle::active()->get();
        
        $this->assertCount(4, $activeVehicles);
        $this->assertTrue($activeVehicles->every(function ($vehicle) {
            return $vehicle->status === Vehicle::STATUS_ACTIVE;
        }));
    }

    /** @test */
    public function vehicle_scope_by_carrier_works()
    {
        $otherCarrier = Carrier::factory()->create(['id_plan' => Membership::factory()->create()->id]);
        
        Vehicle::factory()->count(3)->create([
            'carrier_id' => $this->carrier->id
        ]);
        
        Vehicle::factory()->count(2)->create([
            'carrier_id' => $otherCarrier->id
        ]);

        $carrierVehicles = Vehicle::byCarrier($this->carrier->id)->get();
        
        $this->assertCount(3, $carrierVehicles);
        $this->assertTrue($carrierVehicles->every(function ($vehicle) {
            return $vehicle->carrier_id === $this->carrier->id;
        }));
    }
}
