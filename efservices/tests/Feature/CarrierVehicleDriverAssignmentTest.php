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
use Spatie\Permission\Models\Role;

class CarrierVehicleDriverAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected $carrier;
    protected $user;
    protected $driver;
    protected $vehicle;

    protected function setUp(): void
    {
        parent::setUp();

        // Create carrier role
        Role::firstOrCreate(['name' => 'user_carrier', 'guard_name' => 'web']);

        // Create a membership directly
        $membership = Membership::create([
            'name' => 'Basic Plan',
            'max_drivers' => 10,
            'max_vehicles' => 10,
            'price' => 99.99
        ]);

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
        UserCarrierDetail::create([
            'user_id' => $this->user->id,
            'carrier_id' => $this->carrier->id,
            'status' => 1
        ]);

        // Create a driver for this carrier
        $driverUser = User::factory()->create(['status' => 1]);
        $this->driver = UserDriverDetail::factory()->create([
            'user_id' => $driverUser->id,
            'carrier_id' => $this->carrier->id,
            'status' => 1
        ]);

        // Create a vehicle for this carrier
        $this->vehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
            'user_driver_detail_id' => null
        ]);
    }

    /** @test */
    public function carrier_can_view_assignment_history()
    {
        // Create an assignment
        VehicleDriverAssignment::create([
            'vehicle_id' => $this->vehicle->id,
            'user_driver_detail_id' => $this->driver->id,
            'driver_type' => 'company_driver',
            'start_date' => now()->subDays(10),
            'status' => 'active',
            'assigned_by' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('carrier.vehicles.assignments.history', $this->vehicle));

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'driver_name',
                    'driver_type',
                    'start_date',
                    'end_date',
                    'status',
                    'notes',
                    'assigned_by',
                    'duration_days',
                    'is_active'
                ]
            ]);
    }

    /** @test */
    public function carrier_can_update_driver_assignment()
    {
        $response = $this->actingAs($this->user)
            ->putJson(route('carrier.vehicles.assignments.update', $this->vehicle), [
                'user_driver_detail_id' => $this->driver->id,
                'driver_type' => 'company_driver',
                'start_date' => now()->format('Y-m-d'),
                'notes' => 'Test assignment'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Asignación actualizada exitosamente'
            ]);

        // Verify the vehicle's driver was updated
        $this->vehicle->refresh();
        $this->assertEquals($this->driver->id, $this->vehicle->user_driver_detail_id);
    }

    /** @test */
    public function updating_assignment_ends_current_active_assignment()
    {
        // Create an active assignment
        $oldAssignment = VehicleDriverAssignment::create([
            'vehicle_id' => $this->vehicle->id,
            'user_driver_detail_id' => $this->driver->id,
            'driver_type' => 'company_driver',
            'start_date' => now()->subDays(10),
            'status' => 'active',
            'assigned_by' => $this->user->id
        ]);

        // Create a new driver
        $newDriverUser = User::factory()->create(['status' => 1]);
        $newDriver = UserDriverDetail::factory()->create([
            'user_id' => $newDriverUser->id,
            'carrier_id' => $this->carrier->id,
            'status' => 1
        ]);

        // Update assignment
        $response = $this->actingAs($this->user)
            ->putJson(route('carrier.vehicles.assignments.update', $this->vehicle), [
                'user_driver_detail_id' => $newDriver->id,
                'driver_type' => 'company_driver',
                'start_date' => now()->format('Y-m-d'),
            ]);

        $response->assertStatus(200);

        // Verify old assignment was ended
        $oldAssignment->refresh();
        $this->assertNotNull($oldAssignment->end_date);
        $this->assertEquals('inactive', $oldAssignment->status);
    }

    /** @test */
    public function carrier_cannot_assign_driver_from_another_carrier()
    {
        // Create another carrier and driver
        $otherCarrier = Carrier::factory()->create([
            'status' => Carrier::STATUS_ACTIVE,
            'documents_completed' => true,
            'id_plan' => Membership::first()->id
        ]);

        $otherDriverUser = User::factory()->create(['status' => 1]);
        $otherDriver = UserDriverDetail::factory()->create([
            'user_id' => $otherDriverUser->id,
            'carrier_id' => $otherCarrier->id,
            'status' => 1
        ]);

        $response = $this->actingAs($this->user)
            ->putJson(route('carrier.vehicles.assignments.update', $this->vehicle), [
                'user_driver_detail_id' => $otherDriver->id,
                'driver_type' => 'company_driver',
                'start_date' => now()->format('Y-m-d'),
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'error' => 'El conductor seleccionado no pertenece a tu carrier.'
            ]);
    }

    /** @test */
    public function carrier_cannot_view_assignment_history_of_another_carriers_vehicle()
    {
        // Create another carrier and vehicle
        $otherCarrier = Carrier::factory()->create([
            'status' => Carrier::STATUS_ACTIVE,
            'documents_completed' => true,
            'id_plan' => Membership::first()->id
        ]);

        $otherVehicle = Vehicle::factory()->create([
            'carrier_id' => $otherCarrier->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('carrier.vehicles.assignments.history', $otherVehicle));

        $response->assertStatus(403)
            ->assertJson([
                'error' => 'No tienes acceso a este vehículo.'
            ]);
    }

    /** @test */
    public function assignment_history_is_ordered_by_start_date_descending()
    {
        // Create multiple assignments with different start dates
        $assignment1 = VehicleDriverAssignment::create([
            'vehicle_id' => $this->vehicle->id,
            'user_driver_detail_id' => $this->driver->id,
            'driver_type' => 'company_driver',
            'start_date' => now()->subDays(30),
            'end_date' => now()->subDays(20),
            'status' => 'inactive',
            'assigned_by' => $this->user->id
        ]);

        $assignment2 = VehicleDriverAssignment::create([
            'vehicle_id' => $this->vehicle->id,
            'user_driver_detail_id' => $this->driver->id,
            'driver_type' => 'company_driver',
            'start_date' => now()->subDays(10),
            'status' => 'active',
            'assigned_by' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('carrier.vehicles.assignments.history', $this->vehicle));

        $response->assertStatus(200);
        
        $assignments = $response->json();
        
        // Verify the most recent assignment is first
        $this->assertEquals($assignment2->id, $assignments[0]['id']);
        $this->assertEquals($assignment1->id, $assignments[1]['id']);
    }

    /** @test */
    public function assignment_requires_valid_driver_type()
    {
        $response = $this->actingAs($this->user)
            ->putJson(route('carrier.vehicles.assignments.update', $this->vehicle), [
                'user_driver_detail_id' => $this->driver->id,
                'driver_type' => 'invalid_type',
                'start_date' => now()->format('Y-m-d'),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['driver_type']);
    }

    /** @test */
    public function assignment_requires_start_date()
    {
        $response = $this->actingAs($this->user)
            ->putJson(route('carrier.vehicles.assignments.update', $this->vehicle), [
                'user_driver_detail_id' => $this->driver->id,
                'driver_type' => 'company_driver',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_date']);
    }

    /** @test */
    public function carrier_can_view_driver_assignment_history_page()
    {
        // Create multiple assignments
        VehicleDriverAssignment::create([
            'vehicle_id' => $this->vehicle->id,
            'user_driver_detail_id' => $this->driver->id,
            'driver_type' => 'company_driver',
            'start_date' => now()->subDays(30),
            'end_date' => now()->subDays(20),
            'status' => 'inactive',
            'assigned_by' => $this->user->id
        ]);

        VehicleDriverAssignment::create([
            'vehicle_id' => $this->vehicle->id,
            'user_driver_detail_id' => $this->driver->id,
            'driver_type' => 'company_driver',
            'start_date' => now()->subDays(10),
            'status' => 'active',
            'assigned_by' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('carrier.vehicles.driver-assignment-history', $this->vehicle));

        $response->assertStatus(200)
            ->assertViewIs('carrier.vehicles.driver-assignment-history')
            ->assertViewHas('vehicle')
            ->assertViewHas('assignmentHistory');
    }

    /** @test */
    public function carrier_cannot_view_driver_assignment_history_page_of_another_carriers_vehicle()
    {
        // Create another carrier and vehicle
        $otherCarrier = Carrier::factory()->create([
            'status' => Carrier::STATUS_ACTIVE,
            'documents_completed' => true,
            'id_plan' => Membership::first()->id
        ]);

        $otherVehicle = Vehicle::factory()->create([
            'carrier_id' => $otherCarrier->id
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('carrier.vehicles.driver-assignment-history', $otherVehicle));

        $response->assertStatus(403);
    }

    /** @test */
    public function driver_assignment_history_page_displays_paginated_results()
    {
        // Create 20 assignments to test pagination (15 per page)
        for ($i = 0; $i < 20; $i++) {
            VehicleDriverAssignment::create([
                'vehicle_id' => $this->vehicle->id,
                'user_driver_detail_id' => $this->driver->id,
                'driver_type' => 'company_driver',
                'start_date' => now()->subDays(100 - $i),
                'end_date' => now()->subDays(90 - $i),
                'status' => 'inactive',
                'assigned_by' => $this->user->id
            ]);
        }

        $response = $this->actingAs($this->user)
            ->get(route('carrier.vehicles.driver-assignment-history', $this->vehicle));

        $response->assertStatus(200);
        
        // Verify pagination
        $assignmentHistory = $response->viewData('assignmentHistory');
        $this->assertEquals(15, $assignmentHistory->perPage());
        $this->assertEquals(20, $assignmentHistory->total());
    }
}
