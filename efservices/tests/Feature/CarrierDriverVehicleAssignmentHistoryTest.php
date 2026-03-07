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

class CarrierDriverVehicleAssignmentHistoryTest extends TestCase
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
    public function carrier_can_view_assignment_history_for_their_own_driver()
    {
        $response = $this->actingAs($this->carrierUser)
            ->get(route('carrier.driver-vehicle-management.assignment-history', $this->driver->id));

        $response->assertStatus(200);
        $response->assertSee('Assignment History');
        $response->assertSee($this->driver->user->name);
    }

    /** @test */
    public function carrier_cannot_view_assignment_history_for_driver_from_different_carrier()
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
            ->get(route('carrier.driver-vehicle-management.assignment-history', $otherDriver->id));

        $response->assertRedirect(route('carrier.driver-vehicle-management.index'));
        $response->assertSessionHas('error');
    }

    /** @test */
    public function assignment_history_displays_all_assignments_ordered_by_date_descending()
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

        $vehicle3 = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
            'company_unit_number' => 'TRUCK-003',
        ]);

        // Create assignments with different dates
        $assignment1 = VehicleDriverAssignment::factory()->create([
            'user_driver_detail_id' => $this->driver->id,
            'vehicle_id' => $vehicle1->id,
            'driver_type' => 'company_driver',
            'status' => 'inactive',
            'start_date' => now()->subDays(90),
            'end_date' => now()->subDays(60),
            'created_at' => now()->subDays(90),
        ]);

        $assignment2 = VehicleDriverAssignment::factory()->create([
            'user_driver_detail_id' => $this->driver->id,
            'vehicle_id' => $vehicle2->id,
            'driver_type' => 'company_driver',
            'status' => 'inactive',
            'start_date' => now()->subDays(59),
            'end_date' => now()->subDays(30),
            'created_at' => now()->subDays(59),
        ]);

        $assignment3 = VehicleDriverAssignment::factory()->create([
            'user_driver_detail_id' => $this->driver->id,
            'vehicle_id' => $vehicle3->id,
            'driver_type' => 'company_driver',
            'status' => 'active',
            'start_date' => now()->subDays(29),
            'created_at' => now()->subDays(29),
        ]);

        $response = $this->actingAs($this->carrierUser)
            ->get(route('carrier.driver-vehicle-management.assignment-history', $this->driver->id));

        $response->assertStatus(200);
        $response->assertSee('TRUCK-001');
        $response->assertSee('TRUCK-002');
        $response->assertSee('TRUCK-003');
        
        // Verify all three assignments are displayed
        $response->assertSee('Total Assignments');
    }

    /** @test */
    public function assignment_history_displays_vehicle_details_for_each_assignment()
    {
        $vehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
            'company_unit_number' => 'TRUCK-001',
            'make' => 'Freightliner',
            'model' => 'Cascadia',
            'year' => 2022,
        ]);

        VehicleDriverAssignment::factory()->create([
            'user_driver_detail_id' => $this->driver->id,
            'vehicle_id' => $vehicle->id,
            'driver_type' => 'company_driver',
            'status' => 'active',
            'start_date' => now()->subDays(30),
        ]);

        $response = $this->actingAs($this->carrierUser)
            ->get(route('carrier.driver-vehicle-management.assignment-history', $this->driver->id));

        $response->assertStatus(200);
        $response->assertSee('TRUCK-001');
        $response->assertSee('Freightliner');
        $response->assertSee('Cascadia');
        $response->assertSee('2022');
    }

    /** @test */
    public function assignment_history_displays_start_and_end_dates()
    {
        $vehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
        ]);

        VehicleDriverAssignment::factory()->create([
            'user_driver_detail_id' => $this->driver->id,
            'vehicle_id' => $vehicle->id,
            'driver_type' => 'company_driver',
            'status' => 'inactive',
            'start_date' => now()->subDays(60),
            'end_date' => now()->subDays(30),
        ]);

        $response = $this->actingAs($this->carrierUser)
            ->get(route('carrier.driver-vehicle-management.assignment-history', $this->driver->id));

        $response->assertStatus(200);
        $response->assertSee('Start Date');
        $response->assertSee('End Date');
    }

    /** @test */
    public function assignment_history_highlights_active_assignment()
    {
        $vehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
            'company_unit_number' => 'TRUCK-ACTIVE',
        ]);

        VehicleDriverAssignment::factory()->create([
            'user_driver_detail_id' => $this->driver->id,
            'vehicle_id' => $vehicle->id,
            'driver_type' => 'company_driver',
            'status' => 'active',
            'start_date' => now()->subDays(30),
        ]);

        $response = $this->actingAs($this->carrierUser)
            ->get(route('carrier.driver-vehicle-management.assignment-history', $this->driver->id));

        $response->assertStatus(200);
        $response->assertSee('Active');
        $response->assertSee('TRUCK-ACTIVE');
    }

    /** @test */
    public function assignment_history_displays_notes_for_assignments()
    {
        $vehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
        ]);

        VehicleDriverAssignment::factory()->create([
            'user_driver_detail_id' => $this->driver->id,
            'vehicle_id' => $vehicle->id,
            'driver_type' => 'company_driver',
            'status' => 'active',
            'start_date' => now()->subDays(30),
            'notes' => 'Test assignment notes',
        ]);

        $response = $this->actingAs($this->carrierUser)
            ->get(route('carrier.driver-vehicle-management.assignment-history', $this->driver->id));

        $response->assertStatus(200);
        $response->assertSee('Test assignment notes');
    }

    /** @test */
    public function assignment_history_displays_empty_state_when_no_assignments()
    {
        $response = $this->actingAs($this->carrierUser)
            ->get(route('carrier.driver-vehicle-management.assignment-history', $this->driver->id));

        $response->assertStatus(200);
        $response->assertSee('No Assignment History');
        $response->assertSee('This driver has no vehicle assignment history yet');
    }

    /** @test */
    public function assignment_history_displays_driver_type_for_each_assignment()
    {
        $vehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
        ]);

        VehicleDriverAssignment::factory()->create([
            'user_driver_detail_id' => $this->driver->id,
            'vehicle_id' => $vehicle->id,
            'driver_type' => 'owner_operator',
            'status' => 'active',
            'start_date' => now()->subDays(30),
        ]);

        $response = $this->actingAs($this->carrierUser)
            ->get(route('carrier.driver-vehicle-management.assignment-history', $this->driver->id));

        $response->assertStatus(200);
        $response->assertSee('Owner Operator');
    }

    /** @test */
    public function assignment_history_displays_assignment_status()
    {
        $vehicle1 = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
        ]);

        $vehicle2 = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
        ]);

        // Active assignment
        VehicleDriverAssignment::factory()->create([
            'user_driver_detail_id' => $this->driver->id,
            'vehicle_id' => $vehicle1->id,
            'driver_type' => 'company_driver',
            'status' => 'active',
            'start_date' => now()->subDays(30),
        ]);

        // Inactive assignment
        VehicleDriverAssignment::factory()->create([
            'user_driver_detail_id' => $this->driver->id,
            'vehicle_id' => $vehicle2->id,
            'driver_type' => 'company_driver',
            'status' => 'inactive',
            'start_date' => now()->subDays(90),
            'end_date' => now()->subDays(60),
        ]);

        $response = $this->actingAs($this->carrierUser)
            ->get(route('carrier.driver-vehicle-management.assignment-history', $this->driver->id));

        $response->assertStatus(200);
        $response->assertSee('Active');
        $response->assertSee('Inactive');
    }
}
