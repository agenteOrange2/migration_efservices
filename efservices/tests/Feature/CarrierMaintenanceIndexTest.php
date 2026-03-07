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

class CarrierMaintenanceIndexTest extends TestCase
{
    use RefreshDatabase;

    protected $carrier;
    protected $user;

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
    }

    /** @test */
    public function carrier_can_access_maintenance_index()
    {
        $response = $this->actingAs($this->user)
            ->get(route('carrier.maintenance.index'));

        $response->assertStatus(200);
        $response->assertViewIs('carrier.maintenance.index');
        $response->assertViewHas('upcomingMaintenance');
        $response->assertViewHas('currentMonthCount');
    }

    /** @test */
    public function maintenance_index_shows_upcoming_maintenance_for_carrier_vehicles()
    {
        // Create a vehicle for this carrier
        $vehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
        ]);

        // Create upcoming maintenance
        $maintenance = VehicleMaintenance::factory()->create([
            'vehicle_id' => $vehicle->id,
            'status' => false, // pending
            'next_service_date' => now()->addDays(5),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('carrier.maintenance.index'));

        $response->assertStatus(200);
        $response->assertSee($maintenance->service_tasks);
        $response->assertSee($vehicle->unit);
    }

    /** @test */
    public function maintenance_index_does_not_show_other_carrier_maintenance()
    {
        // Create another carrier
        $otherCarrier = Carrier::factory()->create([
            'status' => Carrier::STATUS_ACTIVE,
        ]);

        // Create a vehicle for the other carrier
        $otherVehicle = Vehicle::factory()->create([
            'carrier_id' => $otherCarrier->id,
        ]);

        // Create maintenance for other carrier's vehicle
        $otherMaintenance = VehicleMaintenance::factory()->create([
            'vehicle_id' => $otherVehicle->id,
            'status' => false,
            'next_service_date' => now()->addDays(5),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('carrier.maintenance.index'));

        $response->assertStatus(200);
        $response->assertDontSee($otherMaintenance->service_tasks);
    }

    /** @test */
    public function maintenance_index_limits_to_5_upcoming_records()
    {
        // Create a vehicle for this carrier
        $vehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
        ]);

        // Create 7 upcoming maintenance records
        for ($i = 1; $i <= 7; $i++) {
            VehicleMaintenance::factory()->create([
                'vehicle_id' => $vehicle->id,
                'status' => false,
                'next_service_date' => now()->addDays($i),
            ]);
        }

        $response = $this->actingAs($this->user)
            ->get(route('carrier.maintenance.index'));

        $response->assertStatus(200);
        
        // Should only have 5 records
        $upcomingMaintenance = $response->viewData('upcomingMaintenance');
        $this->assertCount(5, $upcomingMaintenance);
    }

    /** @test */
    public function maintenance_index_calculates_current_month_count()
    {
        // Create a vehicle for this carrier
        $vehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
        ]);

        // Create 3 maintenance records for current month
        for ($i = 1; $i <= 3; $i++) {
            VehicleMaintenance::factory()->create([
                'vehicle_id' => $vehicle->id,
                'next_service_date' => now()->addDays($i),
            ]);
        }

        // Create 1 maintenance record for next month
        VehicleMaintenance::factory()->create([
            'vehicle_id' => $vehicle->id,
            'next_service_date' => now()->addMonth(),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('carrier.maintenance.index'));

        $response->assertStatus(200);
        
        $currentMonthCount = $response->viewData('currentMonthCount');
        $this->assertEquals(3, $currentMonthCount);
    }
}
