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

class CarrierMaintenanceShowTest extends TestCase
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
            'phone' => '1234567890',
            'job_position' => 'Manager'
        ]);

        // Create a vehicle for this carrier
        $this->vehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
        ]);
    }

    /** @test */
    public function carrier_can_view_their_maintenance_details()
    {
        $maintenance = VehicleMaintenance::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'service_tasks' => 'Oil change and filter replacement',
            'vendor_mechanic' => 'ABC Auto Shop',
            'cost' => 150.00,
            'odometer' => 50000,
            'service_date' => now()->subDays(5),
            'next_service_date' => now()->addDays(90),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('carrier.maintenance.show', $maintenance->id));

        $response->assertStatus(200);
        $response->assertViewIs('carrier.maintenance.show');
        $response->assertViewHas('maintenance');
        $response->assertSee('Oil change and filter replacement');
        $response->assertSee('ABC Auto Shop');
        $response->assertSee('150.00');
        $response->assertSee('50,000');
    }

    /** @test */
    public function maintenance_show_displays_all_required_fields()
    {
        $maintenance = VehicleMaintenance::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'unit' => 'TRUCK-001',
            'service_tasks' => 'Brake inspection and repair',
            'vendor_mechanic' => 'XYZ Mechanics',
            'cost' => 500.00,
            'odometer' => 75000,
            'description' => 'Replaced front brake pads and rotors',
            'service_date' => now()->subDays(10),
            'next_service_date' => now()->addDays(180),
            'status' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('carrier.maintenance.show', $maintenance->id));

        $response->assertStatus(200);
        // Check all required fields are displayed
        $response->assertSee('TRUCK-001');
        $response->assertSee('Brake inspection and repair');
        $response->assertSee('XYZ Mechanics');
        $response->assertSee('500.00');
        $response->assertSee('75,000');
        $response->assertSee('Replaced front brake pads and rotors');
        $response->assertSee($this->vehicle->make);
        $response->assertSee($this->vehicle->model);
    }

    /** @test */
    public function maintenance_show_displays_dates_in_correct_format()
    {
        $serviceDate = now()->subDays(30);
        $nextServiceDate = now()->addDays(60);

        $maintenance = VehicleMaintenance::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'service_date' => $serviceDate,
            'next_service_date' => $nextServiceDate,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('carrier.maintenance.show', $maintenance->id));

        $response->assertStatus(200);
        // Dates should be in m/d/Y format
        $response->assertSee($serviceDate->format('m/d/Y'));
        $response->assertSee($nextServiceDate->format('m/d/Y'));
    }

    /** @test */
    public function carrier_cannot_view_other_carrier_maintenance()
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
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('carrier.maintenance.show', $otherMaintenance->id));

        // Should return 404 to prevent information disclosure
        $response->assertStatus(404);
    }

    /** @test */
    public function maintenance_show_displays_vehicle_information()
    {
        $maintenance = VehicleMaintenance::factory()->create([
            'vehicle_id' => $this->vehicle->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('carrier.maintenance.show', $maintenance->id));

        $response->assertStatus(200);
        // Should display vehicle information
        $response->assertSee($this->vehicle->make);
        $response->assertSee($this->vehicle->model);
        $response->assertSee((string)$this->vehicle->year);
        
        if ($this->vehicle->vin) {
            $response->assertSee($this->vehicle->vin);
        }
    }

    /** @test */
    public function maintenance_show_displays_reschedule_button_for_pending_maintenance()
    {
        $maintenance = VehicleMaintenance::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'status' => false, // pending
            'next_service_date' => now()->addDays(30),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('carrier.maintenance.show', $maintenance->id));

        $response->assertStatus(200);
        $response->assertSee('Reschedule');
        $response->assertSee('reschedule-modal');
    }

    /** @test */
    public function maintenance_show_does_not_display_reschedule_button_for_completed_maintenance()
    {
        $maintenance = VehicleMaintenance::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'status' => true, // completed
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('carrier.maintenance.show', $maintenance->id));

        $response->assertStatus(200);
        // Check that the reschedule button is not present (button with id="open-reschedule-modal")
        $response->assertDontSee('id="open-reschedule-modal"', false);
    }

    /** @test */
    public function maintenance_show_displays_status_correctly()
    {
        // Test completed status
        $completedMaintenance = VehicleMaintenance::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'status' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('carrier.maintenance.show', $completedMaintenance->id));

        $response->assertStatus(200);
        $response->assertSee('Completed');

        // Test pending status
        $pendingMaintenance = VehicleMaintenance::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'status' => false,
            'next_service_date' => now()->addDays(30),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('carrier.maintenance.show', $pendingMaintenance->id));

        $response->assertStatus(200);
        // Pending maintenance should show either "Scheduled", "Upcoming", or "Overdue" depending on date
        $this->assertTrue(
            str_contains($response->getContent(), 'Scheduled') ||
            str_contains($response->getContent(), 'Upcoming') ||
            str_contains($response->getContent(), 'Overdue')
        );
    }
}
