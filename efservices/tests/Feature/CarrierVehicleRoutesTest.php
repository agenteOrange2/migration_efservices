<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Carrier;
use App\Models\Membership;
use App\Models\UserCarrierDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class CarrierVehicleRoutesTest extends TestCase
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
            'bank_name' => 'Test Bank',
            'account_number' => '1234567890',
            'routing_number' => '123456789'
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
    }

    /** @test */
    public function carrier_vehicle_routes_are_accessible_with_authentication()
    {
        $response = $this->actingAs($this->user)
            ->get(route('carrier.vehicles.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function carrier_vehicle_routes_require_authentication()
    {
        $response = $this->get(route('carrier.vehicles.index'));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function carrier_vehicle_routes_require_carrier_role()
    {
        // Create a user without carrier role
        $nonCarrierUser = User::factory()->create(['status' => 1]);
        
        $response = $this->actingAs($nonCarrierUser)
            ->get(route('carrier.vehicles.index'));

        // Should be redirected or forbidden
        $this->assertTrue(
            $response->isRedirect() || $response->status() === 403,
            'Non-carrier user should not access carrier vehicle routes'
        );
    }

    /** @test */
    public function carrier_middleware_verifies_carrier_association()
    {
        // Create a user with carrier role but no carrier association
        $userWithoutCarrier = User::factory()->create(['status' => 1]);
        $userWithoutCarrier->assignRole('user_carrier');

        $response = $this->actingAs($userWithoutCarrier)
            ->get(route('carrier.vehicles.index'));

        // Should be redirected to complete registration
        $this->assertTrue(
            $response->isRedirect(),
            'User without carrier association should be redirected'
        );
    }

    /** @test */
    public function all_vehicle_crud_routes_exist()
    {
        $routes = [
            'carrier.vehicles.index',
            'carrier.vehicles.create',
            'carrier.vehicles.store',
            'carrier.vehicles.filter-options',
        ];

        foreach ($routes as $routeName) {
            $this->assertTrue(
                \Illuminate\Support\Facades\Route::has($routeName),
                "Route {$routeName} should exist"
            );
        }
    }
}
