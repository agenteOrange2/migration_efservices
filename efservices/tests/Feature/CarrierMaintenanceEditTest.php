<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Carrier;
use App\Models\Membership;
use App\Models\UserCarrierDetail;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Admin\Vehicle\VehicleMaintenance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class CarrierMaintenanceEditTest extends TestCase
{
    use RefreshDatabase;

    protected $carrier;
    protected $user;
    protected $vehicle;
    protected $maintenance;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed necessary data
        $this->artisan('db:seed', ['--class' => 'MembershipSeeder']);

        // Create carrier role
        Role::create(['name' => 'user_carrier', 'guard_name' => 'web']);

        // Get a membership
        $membership = Membership::first();

        // Create a carrier
        $this->carrier = Carrier::factory()->create([
            'status' => Carrier::STATUS_ACTIVE,
            'documents_completed' => true,
            'id_plan' => $membership->id
        ]);

        // Create a user with carrier association
        $this->user = User::factory()->create([
            'status' => 1
        ]);
        
        $this->user->assignRole('user_carrier');

        // Create carrier details
        UserCarrierDetail::factory()->create([
            'user_id' => $this->user->id,
            'carrier_id' => $this->carrier->id,
            'status' => 1
        ]);

        // Create a vehicle for the carrier
        $this->vehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
        ]);

        // Create a maintenance record
        $this->maintenance = VehicleMaintenance::create([
            'vehicle_id' => $this->vehicle->id,
            'unit' => 'UNIT-001',
            'service_tasks' => 'Oil change and filter replacement',
            'service_date' => '2024-01-15',
            'next_service_date' => '2024-04-15',
            'vendor_mechanic' => 'ABC Auto Shop',
            'cost' => 150.00,
            'odometer' => 50000,
            'description' => 'Regular maintenance',
            'status' => false,
            'is_historical' => false,
            'created_by' => $this->user->id,
        ]);
    }

    /** @test */
    public function carrier_can_access_edit_page_for_their_maintenance()
    {
        $response = $this->actingAs($this->user)
            ->get(route('carrier.maintenance.edit', $this->maintenance->id));

        $response->assertStatus(200);
        $response->assertViewIs('carrier.maintenance.edit');
        $response->assertViewHas('maintenance', $this->maintenance);
        $response->assertViewHas('vehicles');
    }

    /** @test */
    public function carrier_can_update_maintenance_record()
    {
        $updateData = [
            'vehicle_id' => $this->vehicle->id,
            'unit' => 'UNIT-002',
            'service_tasks' => 'Brake service and inspection',
            'service_date' => '2024-02-01',
            'next_service_date' => '2024-05-01',
            'vendor_mechanic' => 'XYZ Mechanics',
            'cost' => 250.00,
            'odometer' => 52000,
            'description' => 'Updated maintenance description',
            'status' => 1,
            'is_historical' => 0,
        ];

        $response = $this->actingAs($this->user)
            ->put(route('carrier.maintenance.update', $this->maintenance->id), $updateData);

        $response->assertRedirect(route('carrier.maintenance.index'));
        $response->assertSessionHas('success', 'Maintenance record updated successfully.');

        // Verify the maintenance was updated in the database
        $this->assertDatabaseHas('vehicle_maintenances', [
            'id' => $this->maintenance->id,
            'unit' => 'UNIT-002',
            'service_tasks' => 'Brake service and inspection',
            'vendor_mechanic' => 'XYZ Mechanics',
            'cost' => 250.00,
            'odometer' => 52000,
            'updated_by' => $this->user->id,
        ]);
    }

    /** @test */
    public function carrier_cannot_access_edit_page_for_other_carriers_maintenance()
    {
        // Create another carrier and vehicle
        $otherCarrier = Carrier::factory()->create([
            'status' => Carrier::STATUS_ACTIVE,
            'documents_completed' => true,
            'id_plan' => Membership::first()->id
        ]);

        $otherVehicle = Vehicle::factory()->create([
            'carrier_id' => $otherCarrier->id,
        ]);

        $otherMaintenance = VehicleMaintenance::create([
            'vehicle_id' => $otherVehicle->id,
            'unit' => 'OTHER-001',
            'service_tasks' => 'Oil change',
            'service_date' => '2024-01-15',
            'vendor_mechanic' => 'ABC Auto Shop',
            'cost' => 150.00,
            'odometer' => 50000,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('carrier.maintenance.edit', $otherMaintenance->id));

        $response->assertStatus(404);
    }

    /** @test */
    public function carrier_cannot_update_other_carriers_maintenance()
    {
        // Create another carrier and vehicle
        $otherCarrier = Carrier::factory()->create([
            'status' => Carrier::STATUS_ACTIVE,
            'documents_completed' => true,
            'id_plan' => Membership::first()->id
        ]);

        $otherVehicle = Vehicle::factory()->create([
            'carrier_id' => $otherCarrier->id,
        ]);

        $otherMaintenance = VehicleMaintenance::create([
            'vehicle_id' => $otherVehicle->id,
            'unit' => 'OTHER-001',
            'service_tasks' => 'Oil change',
            'service_date' => '2024-01-15',
            'vendor_mechanic' => 'ABC Auto Shop',
            'cost' => 150.00,
            'odometer' => 50000,
            'created_by' => $this->user->id,
        ]);

        $updateData = [
            'vehicle_id' => $otherVehicle->id,
            'unit' => 'HACKED',
            'service_tasks' => 'Hacked service',
            'service_date' => '2024-02-01',
            'vendor_mechanic' => 'Hacker',
            'cost' => 999.00,
            'odometer' => 99999,
        ];

        $response = $this->actingAs($this->user)
            ->put(route('carrier.maintenance.update', $otherMaintenance->id), $updateData);

        $response->assertStatus(404);

        // Verify the maintenance was NOT updated
        $this->assertDatabaseMissing('vehicle_maintenances', [
            'id' => $otherMaintenance->id,
            'unit' => 'HACKED',
        ]);
    }

    /** @test */
    public function update_requires_authentication()
    {
        $updateData = [
            'vehicle_id' => $this->vehicle->id,
            'unit' => 'UNIT-002',
            'service_tasks' => 'Brake service',
            'service_date' => '2024-02-01',
            'vendor_mechanic' => 'XYZ Mechanics',
            'cost' => 250.00,
            'odometer' => 52000,
        ];

        $response = $this->put(route('carrier.maintenance.update', $this->maintenance->id), $updateData);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function update_validates_required_fields()
    {
        $response = $this->actingAs($this->user)
            ->put(route('carrier.maintenance.update', $this->maintenance->id), []);

        $response->assertSessionHasErrors(['vehicle_id', 'unit', 'service_tasks', 'service_date', 'vendor_mechanic', 'cost', 'odometer']);
    }

    /** @test */
    public function update_sets_updated_by_field()
    {
        $updateData = [
            'vehicle_id' => $this->vehicle->id,
            'unit' => 'UNIT-002',
            'service_tasks' => 'Brake service',
            'service_date' => '2024-02-01',
            'vendor_mechanic' => 'XYZ Mechanics',
            'cost' => 250.00,
            'odometer' => 52000,
        ];

        $this->actingAs($this->user)
            ->put(route('carrier.maintenance.update', $this->maintenance->id), $updateData);

        $this->maintenance->refresh();
        $this->assertEquals($this->user->id, $this->maintenance->updated_by);
    }
}
