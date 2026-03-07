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

class CarrierMaintenanceCreateTest extends TestCase
{
    use RefreshDatabase;

    protected $carrier;
    protected $user;
    protected $vehicle;

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

        // Create banking details for the carrier
        $this->carrier->bankingDetails()->create([
            'status' => 'approved',
            'account_holder_name' => 'Test Holder',
            'account_number' => '1234567890',
            'banking_routing_number' => '123456789'
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
            'status' => 1,
            'phone' => '1234567890'
        ]);

        // Create a vehicle for this carrier
        $this->vehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
        ]);
    }

    /** @test */
    public function carrier_can_access_create_form()
    {
        $response = $this->actingAs($this->user)
            ->get(route('carrier.maintenance.create'));

        $response->assertStatus(200);
        $response->assertViewIs('carrier.maintenance.create');
        $response->assertViewHas('vehicles');
    }

    /** @test */
    public function create_form_only_shows_carrier_vehicles()
    {
        // Create another carrier with a vehicle
        $otherCarrier = Carrier::factory()->create([
            'status' => Carrier::STATUS_ACTIVE,
        ]);
        $otherVehicle = Vehicle::factory()->create([
            'carrier_id' => $otherCarrier->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('carrier.maintenance.create'));

        $response->assertStatus(200);
        
        $vehicles = $response->viewData('vehicles');
        $this->assertTrue($vehicles->contains($this->vehicle));
        $this->assertFalse($vehicles->contains($otherVehicle));
    }

    /** @test */
    public function carrier_can_create_maintenance_record()
    {
        $maintenanceData = [
            'vehicle_id' => $this->vehicle->id,
            'unit' => 'UNIT-001',
            'service_tasks' => 'Oil change and tire rotation',
            'service_date' => now()->subDays(2)->format('Y-m-d'),
            'next_service_date' => now()->addDays(30)->format('Y-m-d'),
            'vendor_mechanic' => 'ABC Auto Shop',
            'cost' => 150.00,
            'odometer' => 50000,
            'description' => 'Regular maintenance',
            'status' => false,
            'is_historical' => false,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('carrier.maintenance.store'), $maintenanceData);

        $response->assertRedirect(route('carrier.maintenance.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('vehicle_maintenances', [
            'vehicle_id' => $this->vehicle->id,
            'unit' => 'UNIT-001',
            'service_tasks' => 'Oil change and tire rotation',
            'vendor_mechanic' => 'ABC Auto Shop',
            'cost' => 150.00,
            'created_by' => $this->user->id,
        ]);
    }

    /** @test */
    public function carrier_cannot_create_maintenance_for_other_carrier_vehicle()
    {
        // Create another carrier with a vehicle
        $otherCarrier = Carrier::factory()->create([
            'status' => Carrier::STATUS_ACTIVE,
        ]);
        $otherVehicle = Vehicle::factory()->create([
            'carrier_id' => $otherCarrier->id,
        ]);

        $maintenanceData = [
            'vehicle_id' => $otherVehicle->id,
            'unit' => 'UNIT-001',
            'service_tasks' => 'Oil change',
            'service_date' => now()->subDays(2)->format('Y-m-d'),
            'next_service_date' => now()->addDays(30)->format('Y-m-d'),
            'vendor_mechanic' => 'ABC Auto Shop',
            'cost' => 150.00,
            'odometer' => 50000,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('carrier.maintenance.store'), $maintenanceData);

        $response->assertStatus(404);
    }

    /** @test */
    public function store_validates_required_fields()
    {
        $response = $this->actingAs($this->user)
            ->post(route('carrier.maintenance.store'), []);

        $response->assertSessionHasErrors([
            'vehicle_id',
            'unit',
            'service_tasks',
            'service_date',
            'vendor_mechanic',
            'cost',
            'odometer',
        ]);
    }

    /** @test */
    public function store_validates_non_historical_dates()
    {
        $maintenanceData = [
            'vehicle_id' => $this->vehicle->id,
            'unit' => 'UNIT-001',
            'service_tasks' => 'Oil change',
            'service_date' => now()->addDays(5)->format('Y-m-d'), // Future date
            'next_service_date' => now()->addDays(30)->format('Y-m-d'),
            'vendor_mechanic' => 'ABC Auto Shop',
            'cost' => 150.00,
            'odometer' => 50000,
            'is_historical' => false,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('carrier.maintenance.store'), $maintenanceData);

        $response->assertSessionHasErrors('service_date');
    }

    /** @test */
    public function store_allows_flexible_dates_for_historical_records()
    {
        $maintenanceData = [
            'vehicle_id' => $this->vehicle->id,
            'unit' => 'UNIT-001',
            'service_tasks' => 'Oil change',
            'service_date' => now()->subYears(2)->format('Y-m-d'),
            'next_service_date' => now()->subYears(2)->addDays(30)->format('Y-m-d'),
            'vendor_mechanic' => 'ABC Auto Shop',
            'cost' => 150.00,
            'odometer' => 30000,
            'is_historical' => true,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('carrier.maintenance.store'), $maintenanceData);

        $response->assertRedirect(route('carrier.maintenance.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('vehicle_maintenances', [
            'vehicle_id' => $this->vehicle->id,
            'is_historical' => true,
        ]);
    }
}
