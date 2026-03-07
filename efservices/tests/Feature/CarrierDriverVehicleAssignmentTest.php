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

class CarrierDriverVehicleAssignmentTest extends TestCase
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

        // Create carrier with active status
        $this->carrier = Carrier::factory()->create([
            'membership_id' => $membership->id,
            'status' => 'active',
            'documents_completed' => true,
            'document_status' => 'skipped',
        ]);

        // Create approved banking details for the carrier
        \App\Models\CarrierBankingDetail::create([
            'carrier_id' => $this->carrier->id,
            'account_holder_name' => 'Test Holder',
            'account_number' => '1234567890',
            'banking_routing_number' => '123456789',
            'status' => 'approved',
        ]);

        // Create carrier user
        $this->carrierUser = User::factory()->create([
            'status' => 1, // Active status
        ]);
        $this->carrierUser->assignRole('user_carrier');

        UserCarrierDetail::factory()->create([
            'user_id' => $this->carrierUser->id,
            'carrier_id' => $this->carrier->id,
            'status' => 1, // Active status
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
    public function carrier_can_access_assign_vehicle_form()
    {
        $response = $this->actingAs($this->carrierUser)
            ->get(route('carrier.driver-vehicle-management.assign-vehicle', $this->driver->id));

        $response->assertStatus(200);
        $response->assertSee('Assign Vehicle to Driver');
        $response->assertSee($this->driver->user->name);
    }

    /** @test */
    public function carrier_cannot_access_assign_vehicle_form_for_driver_from_different_carrier()
    {
        // Create another carrier
        $otherMembership = Membership::factory()->create([
            'name' => 'Premium',
        ]);
        $otherCarrier = Carrier::factory()->create([
            'membership_id' => $otherMembership->id,
            'status' => 'active',
            'documents_completed' => true,
        ]);
        
        // Create approved banking details for the other carrier
        \App\Models\CarrierBankingDetail::create([
            'carrier_id' => $otherCarrier->id,
            'account_holder_name' => 'Other Holder',
            'account_number' => '9876543210',
            'banking_routing_number' => '987654321',
            'status' => 'approved',
        ]);

        // Create driver for other carrier
        $otherDriverUser = User::factory()->create();
        $otherDriverUser->assignRole('user_driver');

        $otherDriver = UserDriverDetail::factory()->create([
            'user_id' => $otherDriverUser->id,
            'carrier_id' => $otherCarrier->id,
        ]);

        $response = $this->actingAs($this->carrierUser)
            ->get(route('carrier.driver-vehicle-management.assign-vehicle', $otherDriver->id));

        $response->assertStatus(403);
    }

    /** @test */
    public function assign_vehicle_form_shows_only_available_vehicles()
    {
        // Create available vehicle
        $availableVehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
            'company_unit_number' => 'AVAILABLE-001',
        ]);

        // Create assigned vehicle
        $assignedVehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
            'company_unit_number' => 'ASSIGNED-001',
        ]);

        // Create another driver and assign the vehicle
        $otherDriverUser = User::factory()->create();
        $otherDriverUser->assignRole('user_driver');
        $otherDriver = UserDriverDetail::factory()->create([
            'user_id' => $otherDriverUser->id,
            'carrier_id' => $this->carrier->id,
        ]);

        VehicleDriverAssignment::factory()->create([
            'user_driver_detail_id' => $otherDriver->id,
            'vehicle_id' => $assignedVehicle->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->carrierUser)
            ->get(route('carrier.driver-vehicle-management.assign-vehicle', $this->driver->id));

        $response->assertStatus(200);
        $response->assertSee('AVAILABLE-001');
        $response->assertDontSee('ASSIGNED-001');
    }

    /** @test */
    public function carrier_can_successfully_assign_vehicle_to_driver()
    {
        $vehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
            'company_unit_number' => 'TRUCK-001',
        ]);

        $response = $this->actingAs($this->carrierUser)
            ->post(route('carrier.driver-vehicle-management.store-vehicle-assignment', $this->driver->id), [
                'vehicle_id' => $vehicle->id,
                'driver_type' => 'company_driver',
                'start_date' => now()->format('Y-m-d'),
                'notes' => 'Test assignment',
            ]);

        $response->assertRedirect(route('carrier.driver-vehicle-management.show', $this->driver->id));
        $response->assertSessionHas('success', 'Vehicle assigned successfully to driver.');

        $this->assertDatabaseHas('vehicle_driver_assignments', [
            'vehicle_id' => $vehicle->id,
            'user_driver_detail_id' => $this->driver->id,
            'driver_type' => 'company_driver',
            'status' => 'active',
            'notes' => 'Test assignment',
        ]);
    }

    /** @test */
    public function assignment_creation_validates_required_fields()
    {
        $response = $this->actingAs($this->carrierUser)
            ->post(route('carrier.driver-vehicle-management.store-vehicle-assignment', $this->driver->id), [
                // Missing required fields
            ]);

        $response->assertSessionHasErrors(['vehicle_id', 'driver_type', 'start_date']);
    }

    /** @test */
    public function assignment_creation_validates_driver_type()
    {
        $vehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
        ]);

        $response = $this->actingAs($this->carrierUser)
            ->post(route('carrier.driver-vehicle-management.store-vehicle-assignment', $this->driver->id), [
                'vehicle_id' => $vehicle->id,
                'driver_type' => 'invalid_type',
                'start_date' => now()->format('Y-m-d'),
            ]);

        $response->assertSessionHasErrors(['driver_type']);
    }

    /** @test */
    public function cannot_assign_vehicle_that_already_has_active_assignment()
    {
        $vehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
        ]);

        // Create another driver and assign the vehicle
        $otherDriverUser = User::factory()->create();
        $otherDriverUser->assignRole('user_driver');
        $otherDriver = UserDriverDetail::factory()->create([
            'user_id' => $otherDriverUser->id,
            'carrier_id' => $this->carrier->id,
        ]);

        VehicleDriverAssignment::factory()->create([
            'user_driver_detail_id' => $otherDriver->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->carrierUser)
            ->post(route('carrier.driver-vehicle-management.store-vehicle-assignment', $this->driver->id), [
                'vehicle_id' => $vehicle->id,
                'driver_type' => 'company_driver',
                'start_date' => now()->format('Y-m-d'),
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Vehicle is already assigned to another driver. Please select a different vehicle.');
    }

    /** @test */
    public function cannot_assign_vehicle_from_different_carrier()
    {
        // Create another carrier
        $otherMembership = Membership::factory()->create([
            'name' => 'Premium',
        ]);
        $otherCarrier = Carrier::factory()->create([
            'membership_id' => $otherMembership->id,
            'status' => 'active',
            'documents_completed' => true,
        ]);
        
        // Create approved banking details for the other carrier
        \App\Models\CarrierBankingDetail::create([
            'carrier_id' => $otherCarrier->id,
            'account_holder_name' => 'Other Holder 2',
            'account_number' => '1111111111',
            'banking_routing_number' => '111111111',
            'status' => 'approved',
        ]);

        // Create vehicle for other carrier
        $otherVehicle = Vehicle::factory()->create([
            'carrier_id' => $otherCarrier->id,
        ]);

        $response = $this->actingAs($this->carrierUser)
            ->post(route('carrier.driver-vehicle-management.store-vehicle-assignment', $this->driver->id), [
                'vehicle_id' => $otherVehicle->id,
                'driver_type' => 'company_driver',
                'start_date' => now()->format('Y-m-d'),
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function assignment_records_who_created_it()
    {
        $vehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
        ]);

        $this->actingAs($this->carrierUser)
            ->post(route('carrier.driver-vehicle-management.store-vehicle-assignment', $this->driver->id), [
                'vehicle_id' => $vehicle->id,
                'driver_type' => 'company_driver',
                'start_date' => now()->format('Y-m-d'),
            ]);

        $this->assertDatabaseHas('vehicle_driver_assignments', [
            'vehicle_id' => $vehicle->id,
            'user_driver_detail_id' => $this->driver->id,
            'assigned_by' => $this->carrierUser->id,
        ]);
    }

    /** @test */
    public function assignment_creation_accepts_optional_notes()
    {
        $vehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
        ]);

        $notes = 'This is a test assignment with detailed notes about the assignment.';

        $this->actingAs($this->carrierUser)
            ->post(route('carrier.driver-vehicle-management.store-vehicle-assignment', $this->driver->id), [
                'vehicle_id' => $vehicle->id,
                'driver_type' => 'owner_operator',
                'start_date' => now()->format('Y-m-d'),
                'notes' => $notes,
            ]);

        $this->assertDatabaseHas('vehicle_driver_assignments', [
            'vehicle_id' => $vehicle->id,
            'user_driver_detail_id' => $this->driver->id,
            'notes' => $notes,
        ]);
    }

    /** @test */
    public function assignment_creation_works_with_all_driver_types()
    {
        $driverTypes = ['company_driver', 'owner_operator', 'third_party'];

        foreach ($driverTypes as $driverType) {
            $vehicle = Vehicle::factory()->create([
                'carrier_id' => $this->carrier->id,
            ]);

            $this->actingAs($this->carrierUser)
                ->post(route('carrier.driver-vehicle-management.store-vehicle-assignment', $this->driver->id), [
                    'vehicle_id' => $vehicle->id,
                    'driver_type' => $driverType,
                    'start_date' => now()->format('Y-m-d'),
                ]);

            $this->assertDatabaseHas('vehicle_driver_assignments', [
                'vehicle_id' => $vehicle->id,
                'user_driver_detail_id' => $this->driver->id,
                'driver_type' => $driverType,
                'status' => 'active',
            ]);
        }
    }

    /** @test */
    public function carrier_can_access_edit_assignment_form()
    {
        $vehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
            'company_unit_number' => 'TRUCK-001',
        ]);

        $assignment = VehicleDriverAssignment::factory()->create([
            'user_driver_detail_id' => $this->driver->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'active',
            'driver_type' => 'company_driver',
        ]);

        $response = $this->actingAs($this->carrierUser)
            ->get(route('carrier.driver-vehicle-management.edit-assignment', $this->driver->id));

        $response->assertStatus(200);
        $response->assertSee('Edit Vehicle Assignment');
        $response->assertSee($this->driver->user->name);
        $response->assertSee('TRUCK-001');
    }

    /** @test */
    public function edit_assignment_form_redirects_if_no_active_assignment()
    {
        $response = $this->actingAs($this->carrierUser)
            ->get(route('carrier.driver-vehicle-management.edit-assignment', $this->driver->id));

        $response->assertRedirect(route('carrier.driver-vehicle-management.show', $this->driver->id));
        $response->assertSessionHas('error', 'No active assignment found to edit.');
    }

    /** @test */
    public function carrier_cannot_access_edit_assignment_form_for_driver_from_different_carrier()
    {
        // Create another carrier
        $otherMembership = Membership::factory()->create([
            'name' => 'Premium',
        ]);
        $otherCarrier = Carrier::factory()->create([
            'membership_id' => $otherMembership->id,
            'status' => 'active',
            'documents_completed' => true,
        ]);
        
        // Create approved banking details for the other carrier
        \App\Models\CarrierBankingDetail::create([
            'carrier_id' => $otherCarrier->id,
            'account_holder_name' => 'Other Holder 3',
            'account_number' => '2222222222',
            'banking_routing_number' => '222222222',
            'status' => 'approved',
        ]);

        // Create driver for other carrier
        $otherDriverUser = User::factory()->create();
        $otherDriverUser->assignRole('user_driver');

        $otherDriver = UserDriverDetail::factory()->create([
            'user_id' => $otherDriverUser->id,
            'carrier_id' => $otherCarrier->id,
        ]);

        $response = $this->actingAs($this->carrierUser)
            ->get(route('carrier.driver-vehicle-management.edit-assignment', $otherDriver->id));

        $response->assertStatus(403);
    }

    /** @test */
    public function edit_assignment_form_shows_current_vehicle_and_available_vehicles()
    {
        // Create current vehicle
        $currentVehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
            'company_unit_number' => 'CURRENT-001',
        ]);

        // Create available vehicle
        $availableVehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
            'company_unit_number' => 'AVAILABLE-001',
        ]);

        // Create assigned vehicle (to another driver)
        $assignedVehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
            'company_unit_number' => 'ASSIGNED-001',
        ]);

        // Create current assignment
        VehicleDriverAssignment::factory()->create([
            'user_driver_detail_id' => $this->driver->id,
            'vehicle_id' => $currentVehicle->id,
            'status' => 'active',
        ]);

        // Create another driver and assign the vehicle
        $otherDriverUser = User::factory()->create();
        $otherDriverUser->assignRole('user_driver');
        $otherDriver = UserDriverDetail::factory()->create([
            'user_id' => $otherDriverUser->id,
            'carrier_id' => $this->carrier->id,
        ]);

        VehicleDriverAssignment::factory()->create([
            'user_driver_detail_id' => $otherDriver->id,
            'vehicle_id' => $assignedVehicle->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->carrierUser)
            ->get(route('carrier.driver-vehicle-management.edit-assignment', $this->driver->id));

        $response->assertStatus(200);
        $response->assertSee('CURRENT-001');
        $response->assertSee('AVAILABLE-001');
        $response->assertDontSee('ASSIGNED-001');
    }

    /** @test */
    public function carrier_can_successfully_update_assignment_with_new_vehicle()
    {
        $oldVehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
            'company_unit_number' => 'OLD-TRUCK',
        ]);

        $newVehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
            'company_unit_number' => 'NEW-TRUCK',
        ]);

        $oldAssignment = VehicleDriverAssignment::factory()->create([
            'user_driver_detail_id' => $this->driver->id,
            'vehicle_id' => $oldVehicle->id,
            'status' => 'active',
            'driver_type' => 'company_driver',
            'start_date' => now()->subDays(30)->format('Y-m-d'),
        ]);

        $response = $this->actingAs($this->carrierUser)
            ->put(route('carrier.driver-vehicle-management.update-assignment', $this->driver->id), [
                'vehicle_id' => $newVehicle->id,
                'driver_type' => 'owner_operator',
                'start_date' => now()->format('Y-m-d'),
                'notes' => 'Updated assignment',
            ]);

        $response->assertRedirect(route('carrier.driver-vehicle-management.show', $this->driver->id));
        $response->assertSessionHas('success', 'Vehicle assignment updated successfully.');

        // Check old assignment is marked inactive
        $this->assertDatabaseHas('vehicle_driver_assignments', [
            'id' => $oldAssignment->id,
            'status' => 'inactive',
        ]);

        // Check new assignment is created
        $this->assertDatabaseHas('vehicle_driver_assignments', [
            'vehicle_id' => $newVehicle->id,
            'user_driver_detail_id' => $this->driver->id,
            'driver_type' => 'owner_operator',
            'status' => 'active',
            'notes' => 'Updated assignment',
        ]);
    }

    /** @test */
    public function carrier_can_update_assignment_keeping_same_vehicle()
    {
        $vehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
        ]);

        $oldAssignment = VehicleDriverAssignment::factory()->create([
            'user_driver_detail_id' => $this->driver->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'active',
            'driver_type' => 'company_driver',
        ]);

        $response = $this->actingAs($this->carrierUser)
            ->put(route('carrier.driver-vehicle-management.update-assignment', $this->driver->id), [
                'vehicle_id' => $vehicle->id,
                'driver_type' => 'owner_operator',
                'start_date' => now()->format('Y-m-d'),
                'notes' => 'Changed driver type',
            ]);

        $response->assertRedirect(route('carrier.driver-vehicle-management.show', $this->driver->id));
        $response->assertSessionHas('success', 'Vehicle assignment updated successfully.');

        // Check old assignment is marked inactive
        $this->assertDatabaseHas('vehicle_driver_assignments', [
            'id' => $oldAssignment->id,
            'status' => 'inactive',
        ]);

        // Check new assignment is created with same vehicle
        $this->assertDatabaseHas('vehicle_driver_assignments', [
            'vehicle_id' => $vehicle->id,
            'user_driver_detail_id' => $this->driver->id,
            'driver_type' => 'owner_operator',
            'status' => 'active',
        ]);
    }

    /** @test */
    public function update_assignment_validates_required_fields()
    {
        $vehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
        ]);

        VehicleDriverAssignment::factory()->create([
            'user_driver_detail_id' => $this->driver->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->carrierUser)
            ->put(route('carrier.driver-vehicle-management.update-assignment', $this->driver->id), [
                // Missing required fields
            ]);

        $response->assertSessionHasErrors(['vehicle_id', 'driver_type', 'start_date']);
    }

    /** @test */
    public function update_assignment_fails_if_no_active_assignment()
    {
        $vehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
        ]);

        $response = $this->actingAs($this->carrierUser)
            ->put(route('carrier.driver-vehicle-management.update-assignment', $this->driver->id), [
                'vehicle_id' => $vehicle->id,
                'driver_type' => 'company_driver',
                'start_date' => now()->format('Y-m-d'),
            ]);

        $response->assertRedirect(route('carrier.driver-vehicle-management.show', $this->driver->id));
        $response->assertSessionHas('error', 'No active assignment found to update.');
    }

    /** @test */
    public function cannot_update_assignment_with_vehicle_already_assigned_to_another_driver()
    {
        $currentVehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
        ]);

        $assignedVehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
        ]);

        // Create current assignment
        VehicleDriverAssignment::factory()->create([
            'user_driver_detail_id' => $this->driver->id,
            'vehicle_id' => $currentVehicle->id,
            'status' => 'active',
        ]);

        // Create another driver and assign the vehicle
        $otherDriverUser = User::factory()->create();
        $otherDriverUser->assignRole('user_driver');
        $otherDriver = UserDriverDetail::factory()->create([
            'user_id' => $otherDriverUser->id,
            'carrier_id' => $this->carrier->id,
        ]);

        VehicleDriverAssignment::factory()->create([
            'user_driver_detail_id' => $otherDriver->id,
            'vehicle_id' => $assignedVehicle->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->carrierUser)
            ->put(route('carrier.driver-vehicle-management.update-assignment', $this->driver->id), [
                'vehicle_id' => $assignedVehicle->id,
                'driver_type' => 'company_driver',
                'start_date' => now()->format('Y-m-d'),
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Vehicle is already assigned to another driver. Please select a different vehicle.');
    }

    /** @test */
    public function cannot_update_assignment_with_vehicle_from_different_carrier()
    {
        $currentVehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
        ]);

        VehicleDriverAssignment::factory()->create([
            'user_driver_detail_id' => $this->driver->id,
            'vehicle_id' => $currentVehicle->id,
            'status' => 'active',
        ]);

        // Create another carrier
        $otherMembership = Membership::factory()->create([
            'name' => 'Premium',
        ]);
        $otherCarrier = Carrier::factory()->create([
            'membership_id' => $otherMembership->id,
            'status' => 'active',
            'documents_completed' => true,
        ]);
        
        // Create approved banking details for the other carrier
        \App\Models\CarrierBankingDetail::create([
            'carrier_id' => $otherCarrier->id,
            'account_holder_name' => 'Other Holder 4',
            'account_number' => '3333333333',
            'banking_routing_number' => '333333333',
            'status' => 'approved',
        ]);

        // Create vehicle for other carrier
        $otherVehicle = Vehicle::factory()->create([
            'carrier_id' => $otherCarrier->id,
        ]);

        $response = $this->actingAs($this->carrierUser)
            ->put(route('carrier.driver-vehicle-management.update-assignment', $this->driver->id), [
                'vehicle_id' => $otherVehicle->id,
                'driver_type' => 'company_driver',
                'start_date' => now()->format('Y-m-d'),
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function old_assignment_gets_end_date_when_updated()
    {
        $vehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
        ]);

        $oldAssignment = VehicleDriverAssignment::factory()->create([
            'user_driver_detail_id' => $this->driver->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'active',
            'start_date' => now()->subDays(30)->format('Y-m-d'),
            'end_date' => null,
        ]);

        $newVehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
        ]);

        $this->actingAs($this->carrierUser)
            ->put(route('carrier.driver-vehicle-management.update-assignment', $this->driver->id), [
                'vehicle_id' => $newVehicle->id,
                'driver_type' => 'company_driver',
                'start_date' => now()->format('Y-m-d'),
            ]);

        $oldAssignment->refresh();

        $this->assertEquals('inactive', $oldAssignment->status);
        $this->assertNotNull($oldAssignment->end_date);
        $this->assertEquals(now()->format('Y-m-d'), $oldAssignment->end_date);
    }

    /** @test */
    public function update_assignment_records_who_created_new_assignment()
    {
        $oldVehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
        ]);

        VehicleDriverAssignment::factory()->create([
            'user_driver_detail_id' => $this->driver->id,
            'vehicle_id' => $oldVehicle->id,
            'status' => 'active',
        ]);

        $newVehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
        ]);

        $this->actingAs($this->carrierUser)
            ->put(route('carrier.driver-vehicle-management.update-assignment', $this->driver->id), [
                'vehicle_id' => $newVehicle->id,
                'driver_type' => 'company_driver',
                'start_date' => now()->format('Y-m-d'),
            ]);

        $this->assertDatabaseHas('vehicle_driver_assignments', [
            'vehicle_id' => $newVehicle->id,
            'user_driver_detail_id' => $this->driver->id,
            'assigned_by' => $this->carrierUser->id,
            'status' => 'active',
        ]);
    }

    /** @test */
    public function carrier_can_successfully_cancel_assignment()
    {
        $vehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
            'status' => 'active',
        ]);

        $assignment = VehicleDriverAssignment::factory()->create([
            'user_driver_detail_id' => $this->driver->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'active',
            'start_date' => now()->subDays(30)->format('Y-m-d'),
            'notes' => 'Original notes',
        ]);

        $terminationDate = now()->subDays(5)->format('Y-m-d');
        $terminationReason = 'Driver requested termination';

        $response = $this->actingAs($this->carrierUser)
            ->post(route('carrier.driver-vehicle-management.cancel-assignment', $this->driver->id), [
                'termination_date' => $terminationDate,
                'termination_reason' => $terminationReason,
            ]);

        $response->assertRedirect(route('carrier.driver-vehicle-management.show', $this->driver->id));
        $response->assertSessionHas('success', 'Vehicle assignment cancelled successfully.');

        // Check assignment is marked inactive
        $assignment->refresh();
        $this->assertEquals('inactive', $assignment->status);
        $this->assertEquals($terminationDate, $assignment->end_date);
        $this->assertStringContainsString('Termination Reason: ' . $terminationReason, $assignment->notes);

        // Check vehicle status is updated to pending (available)
        $vehicle->refresh();
        $this->assertEquals(Vehicle::STATUS_PENDING, $vehicle->status);
    }

    /** @test */
    public function cancel_assignment_validates_required_fields()
    {
        $vehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
        ]);

        VehicleDriverAssignment::factory()->create([
            'user_driver_detail_id' => $this->driver->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->carrierUser)
            ->post(route('carrier.driver-vehicle-management.cancel-assignment', $this->driver->id), [
                // Missing required fields
            ]);

        $response->assertSessionHasErrors(['termination_date', 'termination_reason']);
    }

    /** @test */
    public function cancel_assignment_validates_termination_date_is_not_in_future()
    {
        $vehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
        ]);

        VehicleDriverAssignment::factory()->create([
            'user_driver_detail_id' => $this->driver->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->carrierUser)
            ->post(route('carrier.driver-vehicle-management.cancel-assignment', $this->driver->id), [
                'termination_date' => now()->addDays(5)->format('Y-m-d'),
                'termination_reason' => 'Test reason',
            ]);

        $response->assertSessionHasErrors(['termination_date']);
    }

    /** @test */
    public function cancel_assignment_fails_if_no_active_assignment()
    {
        $response = $this->actingAs($this->carrierUser)
            ->post(route('carrier.driver-vehicle-management.cancel-assignment', $this->driver->id), [
                'termination_date' => now()->format('Y-m-d'),
                'termination_reason' => 'Test reason',
            ]);

        $response->assertRedirect(route('carrier.driver-vehicle-management.show', $this->driver->id));
        $response->assertSessionHas('error', 'No active assignment found to cancel.');
    }

    /** @test */
    public function cancel_assignment_preserves_record_in_history()
    {
        $vehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
        ]);

        $assignment = VehicleDriverAssignment::factory()->create([
            'user_driver_detail_id' => $this->driver->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'active',
            'start_date' => now()->subDays(30)->format('Y-m-d'),
        ]);

        $assignmentId = $assignment->id;

        $this->actingAs($this->carrierUser)
            ->post(route('carrier.driver-vehicle-management.cancel-assignment', $this->driver->id), [
                'termination_date' => now()->format('Y-m-d'),
                'termination_reason' => 'Test termination',
            ]);

        // Verify the assignment still exists in the database
        $this->assertDatabaseHas('vehicle_driver_assignments', [
            'id' => $assignmentId,
            'status' => 'inactive',
        ]);

        // Verify it wasn't deleted
        $this->assertNotNull(VehicleDriverAssignment::find($assignmentId));
    }

    /** @test */
    public function cancel_assignment_appends_termination_reason_to_existing_notes()
    {
        $vehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
        ]);

        $originalNotes = 'These are the original assignment notes';
        $assignment = VehicleDriverAssignment::factory()->create([
            'user_driver_detail_id' => $this->driver->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'active',
            'notes' => $originalNotes,
        ]);

        $terminationReason = 'Driver resigned';

        $this->actingAs($this->carrierUser)
            ->post(route('carrier.driver-vehicle-management.cancel-assignment', $this->driver->id), [
                'termination_date' => now()->format('Y-m-d'),
                'termination_reason' => $terminationReason,
            ]);

        $assignment->refresh();
        $this->assertStringContainsString($originalNotes, $assignment->notes);
        $this->assertStringContainsString('Termination Reason: ' . $terminationReason, $assignment->notes);
    }

    /** @test */
    public function cancel_assignment_works_when_no_existing_notes()
    {
        $vehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
        ]);

        $assignment = VehicleDriverAssignment::factory()->create([
            'user_driver_detail_id' => $this->driver->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'active',
            'notes' => null,
        ]);

        $terminationReason = 'End of contract';

        $this->actingAs($this->carrierUser)
            ->post(route('carrier.driver-vehicle-management.cancel-assignment', $this->driver->id), [
                'termination_date' => now()->format('Y-m-d'),
                'termination_reason' => $terminationReason,
            ]);

        $assignment->refresh();
        $this->assertStringContainsString('Termination Reason: ' . $terminationReason, $assignment->notes);
    }

    /** @test */
    public function cannot_cancel_assignment_for_driver_from_different_carrier()
    {
        // Create another carrier
        $otherMembership = Membership::factory()->create([
            'name' => 'Premium',
        ]);
        $otherCarrier = Carrier::factory()->create([
            'membership_id' => $otherMembership->id,
            'status' => 'active',
            'documents_completed' => true,
        ]);
        
        // Create approved banking details for the other carrier
        \App\Models\CarrierBankingDetail::create([
            'carrier_id' => $otherCarrier->id,
            'account_holder_name' => 'Other Holder 5',
            'account_number' => '4444444444',
            'banking_routing_number' => '444444444',
            'status' => 'approved',
        ]);

        // Create driver for other carrier
        $otherDriverUser = User::factory()->create();
        $otherDriverUser->assignRole('user_driver');

        $otherDriver = UserDriverDetail::factory()->create([
            'user_id' => $otherDriverUser->id,
            'carrier_id' => $otherCarrier->id,
        ]);

        $vehicle = Vehicle::factory()->create([
            'carrier_id' => $otherCarrier->id,
        ]);

        VehicleDriverAssignment::factory()->create([
            'user_driver_detail_id' => $otherDriver->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->carrierUser)
            ->post(route('carrier.driver-vehicle-management.cancel-assignment', $otherDriver->id), [
                'termination_date' => now()->format('Y-m-d'),
                'termination_reason' => 'Test reason',
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function cancel_assignment_validates_vehicle_belongs_to_carrier()
    {
        // Create another carrier
        $otherMembership = Membership::factory()->create([
            'name' => 'Premium',
        ]);
        $otherCarrier = Carrier::factory()->create([
            'membership_id' => $otherMembership->id,
            'status' => 'active',
            'documents_completed' => true,
        ]);
        
        // Create approved banking details for the other carrier
        \App\Models\CarrierBankingDetail::create([
            'carrier_id' => $otherCarrier->id,
            'account_holder_name' => 'Other Holder 6',
            'account_number' => '5555555555',
            'banking_routing_number' => '555555555',
            'status' => 'approved',
        ]);

        // Create vehicle for other carrier
        $otherVehicle = Vehicle::factory()->create([
            'carrier_id' => $otherCarrier->id,
        ]);

        // Somehow create an assignment with mismatched carriers (edge case)
        // This shouldn't happen in normal operation, but we test the validation
        $assignment = VehicleDriverAssignment::factory()->create([
            'user_driver_detail_id' => $this->driver->id,
            'vehicle_id' => $otherVehicle->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->carrierUser)
            ->post(route('carrier.driver-vehicle-management.cancel-assignment', $this->driver->id), [
                'termination_date' => now()->format('Y-m-d'),
                'termination_reason' => 'Test reason',
            ]);

        $response->assertStatus(403);
    }
}
