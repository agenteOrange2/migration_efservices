<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Carrier;
use App\Models\Membership;
use App\Models\UserCarrierDetail;
use App\Models\UserDriverDetail;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Admin\Vehicle\VehicleDriverAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class CarrierDriverVehicleManagementShowTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $carrier;
    protected $carrierUser;
    protected $driver;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

        // Create membership
        $membership = Membership::factory()->create([
            'name' => 'Basic',
            'max_drivers' => 10,
        ]);

        // Create carrier
        $this->carrier = Carrier::factory()->create([
            'membership_id' => $membership->id,
            'status' => 'active',
        ]);

        // Create carrier user
        $this->carrierUser = User::factory()->create([
            'status' => 1, // Active status
        ]);
        $this->carrierUser->assignRole('user_carrier');

        UserCarrierDetail::factory()->create([
            'user_id' => $this->carrierUser->id,
            'carrier_id' => $this->carrier->id,
        ]);

        // Create driver
        $driverUser = User::factory()->create();
        $driverUser->assignRole('user_driver');

        $this->driver = UserDriverDetail::factory()->create([
            'user_id' => $driverUser->id,
            'carrier_id' => $this->carrier->id,
            'status' => 1,
        ]);
    }

    /** @test */
    public function carrier_can_view_their_own_driver_details()
    {
        $response = $this->actingAs($this->carrierUser)
            ->get(route('carrier.driver-vehicle-management.show', $this->driver->id));

        $response->assertStatus(200);
        $response->assertSee($this->driver->user->name);
        $response->assertSee($this->driver->user->email);
    }

    /** @test */
    public function carrier_cannot_view_driver_from_different_carrier()
    {
        // Create another carrier
        $otherMembership = Membership::factory()->create();
        $otherCarrier = Carrier::factory()->create([
            'membership_id' => $otherMembership->id,
        ]);

        // Create driver for other carrier
        $otherDriverUser = User::factory()->create();
        $otherDriverUser->assignRole('user_driver');

        $otherDriver = UserDriverDetail::factory()->create([
            'user_id' => $otherDriverUser->id,
            'carrier_id' => $otherCarrier->id,
        ]);

        $response = $this->actingAs($this->carrierUser)
            ->get(route('carrier.driver-vehicle-management.show', $otherDriver->id));

        $response->assertStatus(403);
    }

    /** @test */
    public function show_view_displays_active_vehicle_assignment()
    {
        // Create vehicle
        $vehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
            'company_unit_number' => 'TRUCK-001',
            'make' => 'Freightliner',
            'model' => 'Cascadia',
            'year' => 2022,
        ]);

        // Create active assignment
        $assignment = VehicleDriverAssignment::factory()->create([
            'user_driver_detail_id' => $this->driver->id,
            'vehicle_id' => $vehicle->id,
            'driver_type' => 'company_driver',
            'status' => 'active',
            'start_date' => now()->subDays(30),
            'notes' => 'Test assignment notes',
        ]);

        $response = $this->actingAs($this->carrierUser)
            ->get(route('carrier.driver-vehicle-management.show', $this->driver->id));

        $response->assertStatus(200);
        $response->assertSee('TRUCK-001');
        $response->assertSee('Freightliner');
        $response->assertSee('Cascadia');
        $response->assertSee('Company Driver');
        $response->assertSee('Test assignment notes');
    }

    /** @test */
    public function show_view_displays_no_assignment_message_when_driver_has_no_active_assignment()
    {
        $response = $this->actingAs($this->carrierUser)
            ->get(route('carrier.driver-vehicle-management.show', $this->driver->id));

        $response->assertStatus(200);
        $response->assertSee('No Active Assignment');
        $response->assertSee('This driver does not currently have a vehicle assigned');
    }

    /** @test */
    public function show_view_displays_assignment_history_summary()
    {
        // Create vehicles
        $vehicle1 = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
            'company_unit_number' => 'TRUCK-001',
        ]);

        $vehicle2 = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
            'company_unit_number' => 'TRUCK-002',
        ]);

        // Create multiple assignments
        VehicleDriverAssignment::factory()->create([
            'user_driver_detail_id' => $this->driver->id,
            'vehicle_id' => $vehicle1->id,
            'driver_type' => 'company_driver',
            'status' => 'inactive',
            'start_date' => now()->subDays(90),
            'end_date' => now()->subDays(60),
        ]);

        VehicleDriverAssignment::factory()->create([
            'user_driver_detail_id' => $this->driver->id,
            'vehicle_id' => $vehicle2->id,
            'driver_type' => 'company_driver',
            'status' => 'active',
            'start_date' => now()->subDays(30),
        ]);

        $response = $this->actingAs($this->carrierUser)
            ->get(route('carrier.driver-vehicle-management.show', $this->driver->id));

        $response->assertStatus(200);
        $response->assertSee('Recent Assignment History');
        $response->assertSee('TRUCK-001');
        $response->assertSee('TRUCK-002');
    }

    /** @test */
    public function show_view_displays_action_buttons()
    {
        $response = $this->actingAs($this->carrierUser)
            ->get(route('carrier.driver-vehicle-management.show', $this->driver->id));

        $response->assertStatus(200);
        $response->assertSee('Assign Vehicle');
        $response->assertSee('View Full History');
        $response->assertSee('Contact Driver');
    }
}
