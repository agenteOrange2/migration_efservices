<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Carrier;
use App\Models\UserDriverDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ActiveDriversByCarrierTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Carrier $carrier;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a user for authentication if needed
        $this->user = User::factory()->create();
    }

    public function test_can_get_active_drivers_by_valid_carrier_id(): void
    {
        // Create a carrier
        $carrier = Carrier::factory()->create(['status' => Carrier::STATUS_ACTIVE]);

        // Create active drivers for this carrier
        $activeDrivers = UserDriverDetail::factory()
            ->count(3)
            ->create([
                'carrier_id' => $carrier->id,
                'status' => 1 // Active status
            ]);

        // Create an inactive driver (should not be returned)
        UserDriverDetail::factory()->create([
            'carrier_id' => $carrier->id,
            'status' => 0 // Inactive status
        ]);

        // Make the API request
        $response = $this->getJson("/api/active-drivers-by-carrier/{$carrier->id}");

        // Assert response is successful
        $response->assertStatus(200)
            ->assertJsonCount(3); // Should only return 3 active drivers

        // Verify response structure
        $responseData = $response->json();
        
        $this->assertIsArray($responseData);
        $this->assertCount(3, $responseData);
        
        // Verify each driver has the expected structure
        foreach ($responseData as $driver) {
            $this->assertArrayHasKey('id', $driver);
            $this->assertArrayHasKey('full_name', $driver);
            $this->assertArrayHasKey('first_name', $driver);
            $this->assertArrayHasKey('middle_name', $driver);
            $this->assertArrayHasKey('last_name', $driver);
            $this->assertArrayHasKey('email', $driver);
            $this->assertArrayHasKey('licenses', $driver);
            $this->assertArrayHasKey('user', $driver);
        }
    }

    public function test_returns_empty_array_for_invalid_carrier_id(): void
    {
        // Use a non-existent carrier ID
        $nonExistentCarrierId = 99999;

        // Make the API request
        $response = $this->getJson("/api/active-drivers-by-carrier/{$nonExistentCarrierId}");

        // Assert response is successful but returns empty array
        $response->assertStatus(200)
            ->assertJson([]);

        $responseData = $response->json();
        $this->assertIsArray($responseData);
        $this->assertEmpty($responseData);
    }

    public function test_returns_empty_array_for_carrier_with_no_active_drivers(): void
    {
        // Create a carrier
        $carrier = Carrier::factory()->create(['status' => Carrier::STATUS_ACTIVE]);

        // Create only inactive drivers for this carrier
        UserDriverDetail::factory()
            ->count(2)
            ->create([
                'carrier_id' => $carrier->id,
                'status' => 0 // Inactive status
            ]);

        // Make the API request
        $response = $this->getJson("/api/active-drivers-by-carrier/{$carrier->id}");

        // Assert response is successful but returns empty array
        $response->assertStatus(200)
            ->assertJson([]);

        $responseData = $response->json();
        $this->assertIsArray($responseData);
        $this->assertEmpty($responseData);
    }

    public function test_response_format_matches_expected_structure(): void
    {
        // Create a carrier
        $carrier = Carrier::factory()->create(['status' => Carrier::STATUS_ACTIVE]);

        // Create a user with specific data
        $user = User::factory()->create([
            'name' => 'John',
            'email' => 'john.doe@example.com'
        ]);

        // Create an active driver with specific data
        $driver = UserDriverDetail::factory()->create([
            'carrier_id' => $carrier->id,
            'status' => 1,
            'middle_name' => 'Michael',
            'last_name' => 'Doe',
            'user_id' => $user->id
        ]);

        // Make the API request
        $response = $this->getJson("/api/active-drivers-by-carrier/{$carrier->id}");

        // Assert response is successful
        $response->assertStatus(200);

        $responseData = $response->json();
        
        // Verify the response structure and data
        $this->assertIsArray($responseData);
        $this->assertCount(1, $responseData);
        
        $driverData = $responseData[0];
        
        // Verify all required fields are present
        $this->assertEquals($driver->id, $driverData['id']);
        $this->assertEquals('John Michael Doe', $driverData['full_name']);
        $this->assertEquals('John', $driverData['first_name']);
        $this->assertEquals('Michael', $driverData['middle_name']);
        $this->assertEquals('Doe', $driverData['last_name']);
        $this->assertEquals('john.doe@example.com', $driverData['email']);
        
        // Verify nested user object
        $this->assertIsArray($driverData['user']);
        $this->assertEquals($user->id, $driverData['user']['id']);
        $this->assertEquals('John', $driverData['user']['name']);
        $this->assertEquals('john.doe@example.com', $driverData['user']['email']);
        
        // Verify licenses array exists (even if empty)
        $this->assertIsArray($driverData['licenses']);
    }

    public function test_only_returns_drivers_for_specified_carrier(): void
    {
        // Create two carriers
        $carrier1 = Carrier::factory()->create(['status' => Carrier::STATUS_ACTIVE]);
        $carrier2 = Carrier::factory()->create(['status' => Carrier::STATUS_ACTIVE]);

        // Create active drivers for carrier 1
        UserDriverDetail::factory()
            ->count(2)
            ->create([
                'carrier_id' => $carrier1->id,
                'status' => 1
            ]);

        // Create active drivers for carrier 2
        UserDriverDetail::factory()
            ->count(3)
            ->create([
                'carrier_id' => $carrier2->id,
                'status' => 1
            ]);

        // Request drivers for carrier 1
        $response = $this->getJson("/api/active-drivers-by-carrier/{$carrier1->id}");

        // Should only return 2 drivers from carrier 1
        $response->assertStatus(200)
            ->assertJsonCount(2);

        // Verify all returned drivers belong to carrier 1
        $responseData = $response->json();
        foreach ($responseData as $driver) {
            $driverDetail = UserDriverDetail::find($driver['id']);
            $this->assertEquals($carrier1->id, $driverDetail->carrier_id);
        }
    }
}
