<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Carrier;
use App\Models\Membership;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CarrierApiControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->user->assignRole('superadmin');
    }

    public function test_can_get_paginated_carriers(): void
    {
        Carrier::factory()->count(20)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/carriers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data',
                    'current_page',
                    'per_page',
                    'total',
                ],
            ]);
    }

    public function test_can_get_active_carriers(): void
    {
        Carrier::factory()->count(3)->create(['status' => Carrier::STATUS_ACTIVE]);
        Carrier::factory()->count(2)->create(['status' => Carrier::STATUS_INACTIVE]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/carriers/active');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(3, 'data');
    }

    public function test_can_get_carriers_pending_validation(): void
    {
        Carrier::factory()->count(2)->create(['status' => Carrier::STATUS_PENDING_VALIDATION]);
        Carrier::factory()->create(['status' => Carrier::STATUS_ACTIVE]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/carriers/pending-validation');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(2, 'data');
    }

    public function test_can_get_carrier_by_slug(): void
    {
        $carrier = Carrier::factory()->create(['slug' => 'test-carrier']);

        $response = $this->actingAs($this->user)
            ->getJson("/api/carriers/{$carrier->slug}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $carrier->id,
                    'slug' => 'test-carrier',
                ],
            ]);
    }

    public function test_returns_404_when_carrier_not_found(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/carriers/non-existent');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Carrier not found',
            ]);
    }

    public function test_can_search_carriers(): void
    {
        Carrier::factory()->create(['name' => 'ABC Transport']);
        Carrier::factory()->create(['name' => 'XYZ Logistics']);
        Carrier::factory()->create(['name' => 'ABC Freight']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/carriers/search?q=ABC');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(2, 'data');
    }

    public function test_can_get_carrier_limits(): void
    {
        $membership = Membership::factory()->create([
            'max_drivers' => 10,
            'max_vehicles' => 5,
        ]);
        
        $carrier = Carrier::factory()->create([
            'id_plan' => $membership->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/carriers/{$carrier->slug}/limits");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'drivers' => ['current', 'max', 'available', 'can_add'],
                    'vehicles' => ['current', 'max', 'available', 'can_add'],
                ],
            ]);
    }

    public function test_can_check_if_carrier_can_add_driver(): void
    {
        $membership = Membership::factory()->create(['max_drivers' => 5]);
        $carrier = Carrier::factory()->create(['id_plan' => $membership->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/carriers/{$carrier->slug}/can-add-driver");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'can_add' => true,
                    'current_drivers' => 0,
                    'max_drivers' => 5,
                ],
            ]);
    }

    public function test_can_check_if_carrier_can_add_vehicle(): void
    {
        $membership = Membership::factory()->create(['max_vehicles' => 3]);
        $carrier = Carrier::factory()->create(['id_plan' => $membership->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/carriers/{$carrier->slug}/can-add-vehicle");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'can_add' => true,
                    'current_vehicles' => 0,
                    'max_vehicles' => 3,
                ],
            ]);
    }
}
