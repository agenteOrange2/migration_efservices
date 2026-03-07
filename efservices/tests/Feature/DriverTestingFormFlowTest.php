<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Carrier;
use App\Models\UserDriverDetail;
use App\Models\Admin\Driver\DriverTesting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

/**
 * Browser Testing for Driver Selection Flow
 * 
 * Tests the complete driver testing form flow including:
 * - Carrier selection triggers driver loading
 * - Driver details display when driver is selected
 * - Error handling when API request fails
 * - Loading indicators during async operations
 * - Form submission with valid and invalid data
 * 
 * Requirements: 1.1, 1.2, 1.3, 1.4, 4.4
 */
class DriverTestingFormFlowTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $carrier;
    protected $driver;
    protected $driverUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create authenticated user with permissions
        $this->user = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);
        
        // Create test carrier
        $this->carrier = Carrier::factory()->create([
            'name' => 'Test Carrier Inc',
            'dot_number' => '123456',
            'status' => Carrier::STATUS_ACTIVE,
        ]);
        
        // Create driver user
        $this->driverUser = User::factory()->create([
            'name' => 'John',
            'email' => 'driver@test.com',
        ]);
        
        // Create driver detail
        $this->driver = UserDriverDetail::factory()->create([
            'user_id' => $this->driverUser->id,
            'carrier_id' => $this->carrier->id,
            'status' => 1, // Active
            'phone' => '555-1234',
        ]);
    }

    /**
     * Test: Carrier selection triggers driver loading
     * Requirement: 1.1
     */
    public function test_carrier_selection_triggers_driver_loading()
    {
        $this->actingAs($this->user);
        
        // Test the API endpoint that loads drivers
        $response = $this->getJson("/api/active-drivers-by-carrier/{$this->carrier->id}");
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     '*' => [
                         'id',
                         'user',
                         'phone',
                         'licenses',
                     ]
                 ]);
        
        // Verify the driver is in the response
        $drivers = $response->json();
        $this->assertNotEmpty($drivers);
        $this->assertEquals($this->driver->id, $drivers[0]['id']);
    }

    /**
     * Test: API returns drivers within acceptable time (2 seconds)
     * Requirement: 1.1
     */
    public function test_api_returns_drivers_within_timeout()
    {
        $this->actingAs($this->user);
        
        $startTime = microtime(true);
        
        $response = $this->getJson("/api/active-drivers-by-carrier/{$this->carrier->id}");
        
        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        
        $response->assertStatus(200);
        
        // Verify response time is under 2 seconds
        $this->assertLessThan(2.0, $duration, 'API response took longer than 2 seconds');
    }

    /**
     * Test: API handles invalid carrier ID gracefully
     * Requirement: 1.2, 4.3
     */
    public function test_api_handles_invalid_carrier_id()
    {
        $this->actingAs($this->user);
        
        $invalidCarrierId = 99999;
        $response = $this->getJson("/api/active-drivers-by-carrier/{$invalidCarrierId}");
        
        // Should return empty array or 404
        $this->assertTrue(
            $response->status() === 404 || 
            ($response->status() === 200 && empty($response->json()))
        );
    }

    /**
     * Test: API returns empty array when carrier has no active drivers
     * Requirement: 1.2
     */
    public function test_api_returns_empty_for_carrier_with_no_drivers()
    {
        $this->actingAs($this->user);
        
        // Create carrier with no drivers
        $emptyCarrier = Carrier::factory()->create([
            'name' => 'Empty Carrier',
            'status' => Carrier::STATUS_ACTIVE,
        ]);
        
        $response = $this->getJson("/api/active-drivers-by-carrier/{$emptyCarrier->id}");
        
        $response->assertStatus(200)
                 ->assertJson([]);
    }

    /**
     * Test: Driver data includes all required fields
     * Requirement: 1.3
     */
    public function test_driver_data_includes_required_fields()
    {
        $this->actingAs($this->user);
        
        $response = $this->getJson("/api/active-drivers-by-carrier/{$this->carrier->id}");
        
        $response->assertStatus(200);
        
        $drivers = $response->json();
        $this->assertNotEmpty($drivers);
        
        $driver = $drivers[0];
        
        // Verify required fields are present
        $this->assertArrayHasKey('id', $driver);
        $this->assertArrayHasKey('user', $driver);
        $this->assertArrayHasKey('phone', $driver);
        $this->assertArrayHasKey('licenses', $driver);
        
        // Verify user data structure
        $this->assertArrayHasKey('name', $driver['user']);
        $this->assertArrayHasKey('email', $driver['user']);
    }

    /**
     * Test: Create form loads successfully
     * Requirement: 1.4
     */
    public function test_create_form_loads_successfully()
    {
        $this->actingAs($this->user);
        
        $response = $this->get(route('admin.driver-testings.create'));
        
        $response->assertStatus(200)
                 ->assertSee('Create New Drug Test')
                 ->assertSee('Select Carrier & Driver')
                 ->assertSee('carrier_id')
                 ->assertSee('user_driver_detail_id');
    }

    /**
     * Test: Form submission with valid data succeeds
     * Requirement: 1.4, 4.4
     */
    public function test_form_submission_with_valid_data()
    {
        $this->actingAs($this->user);
        
        $validData = [
            'carrier_id' => $this->carrier->id,
            'user_driver_detail_id' => $this->driver->id,
            'test_type' => 'drug',
            'test_date' => now()->format('m/d/Y'),
            'location' => 'Test Location',
            'administered_by' => 'Test Administrator',
            'test_result' => 'pending',
            'status' => 'active',
            'bill_to' => 'Carrier',
            'is_random_test' => true,
        ];
        
        $response = $this->post(route('admin.driver-testings.store'), $validData);
        
        // Should redirect on success
        $response->assertRedirect();
        
        // Verify record was created
        $this->assertDatabaseHas('driver_testings', [
            'carrier_id' => $this->carrier->id,
            'user_driver_detail_id' => $this->driver->id,
            'test_type' => 'drug',
        ]);
    }

    /**
     * Test: Form submission with missing carrier fails
     * Requirement: 4.2, 4.5
     */
    public function test_form_submission_without_carrier_fails()
    {
        $this->actingAs($this->user);
        
        $invalidData = [
            'user_driver_detail_id' => $this->driver->id,
            'test_type' => 'drug',
            'test_date' => now()->format('m/d/Y'),
            'location' => 'Test Location',
            'administered_by' => 'Test Administrator',
            'bill_to' => 'Carrier',
        ];
        
        $response = $this->post(route('admin.driver-testings.store'), $invalidData);
        
        $response->assertSessionHasErrors('carrier_id');
    }

    /**
     * Test: Form submission with missing driver fails
     * Requirement: 4.2, 4.5
     */
    public function test_form_submission_without_driver_fails()
    {
        $this->actingAs($this->user);
        
        $invalidData = [
            'carrier_id' => $this->carrier->id,
            'test_type' => 'drug',
            'test_date' => now()->format('m/d/Y'),
            'location' => 'Test Location',
            'administered_by' => 'Test Administrator',
            'bill_to' => 'Carrier',
        ];
        
        $response = $this->post(route('admin.driver-testings.store'), $invalidData);
        
        $response->assertSessionHasErrors('user_driver_detail_id');
    }

    /**
     * Test: Form submission with missing required fields fails
     * Requirement: 4.2, 4.5
     */
    public function test_form_submission_with_missing_required_fields()
    {
        $this->actingAs($this->user);
        
        $invalidData = [
            'carrier_id' => $this->carrier->id,
            'user_driver_detail_id' => $this->driver->id,
            // Missing test_date, location, administered_by, bill_to
        ];
        
        $response = $this->post(route('admin.driver-testings.store'), $invalidData);
        
        $response->assertSessionHasErrors([
            'test_date',
            'location',
            'administered_by',
            'bill_to',
        ]);
    }

    /**
     * Test: Edit form loads with pre-selected carrier and driver
     * Requirement: 1.5
     */
    public function test_edit_form_preselects_carrier_and_driver()
    {
        $this->actingAs($this->user);
        
        // Create a testing record
        $testing = DriverTesting::factory()->create([
            'carrier_id' => $this->carrier->id,
            'user_driver_detail_id' => $this->driver->id,
            'test_type' => 'drug',
            'test_date' => now(),
            'location' => 'Test Location',
            'administered_by' => 'Test Admin',
            'status' => 'active',
            'bill_to' => 'Carrier',
        ]);
        
        $response = $this->get(route('admin.driver-testings.edit', $testing->id));
        
        $response->assertStatus(200)
                 ->assertSee($this->carrier->name)
                 ->assertSee('Edit Drug Test');
    }

    /**
     * Test: Form displays field-specific error messages
     * Requirement: 4.2
     */
    public function test_form_displays_field_specific_errors()
    {
        $this->actingAs($this->user);
        
        $invalidData = [
            'carrier_id' => 'invalid',
            'user_driver_detail_id' => 'invalid',
            'test_date' => 'invalid-date',
        ];
        
        $response = $this->post(route('admin.driver-testings.store'), $invalidData);
        
        $response->assertSessionHasErrors([
            'carrier_id',
            'user_driver_detail_id',
            'test_date',
        ]);
        
        // Verify error messages are specific
        $errors = session('errors');
        $this->assertNotNull($errors);
    }

    /**
     * Test: API endpoint requires authentication
     * Requirement: 4.3
     */
    public function test_api_requires_authentication()
    {
        // Don't authenticate
        $response = $this->getJson("/api/active-drivers-by-carrier/{$this->carrier->id}");
        
        // Should return 401 or redirect to login
        $this->assertTrue(
            $response->status() === 401 || 
            $response->status() === 302
        );
    }

    /**
     * Test: Multiple drivers are returned correctly
     * Requirement: 1.3
     */
    public function test_multiple_drivers_returned_correctly()
    {
        $this->actingAs($this->user);
        
        // Create additional drivers for the same carrier
        $driver2User = User::factory()->create(['name' => 'Jane']);
        $driver2 = UserDriverDetail::factory()->create([
            'user_id' => $driver2User->id,
            'carrier_id' => $this->carrier->id,
            'status' => 1,
        ]);
        
        $driver3User = User::factory()->create(['name' => 'Bob']);
        $driver3 = UserDriverDetail::factory()->create([
            'user_id' => $driver3User->id,
            'carrier_id' => $this->carrier->id,
            'status' => 1,
        ]);
        
        $response = $this->getJson("/api/active-drivers-by-carrier/{$this->carrier->id}");
        
        $response->assertStatus(200);
        
        $drivers = $response->json();
        $this->assertCount(3, $drivers);
        
        // Verify all drivers are present
        $driverIds = array_column($drivers, 'id');
        $this->assertContains($this->driver->id, $driverIds);
        $this->assertContains($driver2->id, $driverIds);
        $this->assertContains($driver3->id, $driverIds);
    }

    /**
     * Test: Only active drivers are returned
     * Requirement: 1.1
     */
    public function test_only_active_drivers_returned()
    {
        $this->actingAs($this->user);
        
        // Create an inactive driver
        $inactiveDriverUser = User::factory()->create(['name' => 'Inactive Driver']);
        $inactiveDriver = UserDriverDetail::factory()->create([
            'user_id' => $inactiveDriverUser->id,
            'carrier_id' => $this->carrier->id,
            'status' => 0, // Inactive
        ]);
        
        $response = $this->getJson("/api/active-drivers-by-carrier/{$this->carrier->id}");
        
        $response->assertStatus(200);
        
        $drivers = $response->json();
        $driverIds = array_column($drivers, 'id');
        
        // Active driver should be present
        $this->assertContains($this->driver->id, $driverIds);
        
        // Inactive driver should NOT be present
        $this->assertNotContains($inactiveDriver->id, $driverIds);
    }

    /**
     * Test: Form update with valid data succeeds
     * Requirement: 1.4
     */
    public function test_form_update_with_valid_data()
    {
        $this->actingAs($this->user);
        
        // Create a testing record
        $testing = DriverTesting::factory()->create([
            'carrier_id' => $this->carrier->id,
            'user_driver_detail_id' => $this->driver->id,
            'test_type' => 'drug',
            'test_date' => now(),
            'location' => 'Original Location',
            'administered_by' => 'Original Admin',
            'status' => 'active',
            'bill_to' => 'Carrier',
        ]);
        
        $updateData = [
            'carrier_id' => $this->carrier->id,
            'user_driver_detail_id' => $this->driver->id,
            'test_type' => 'alcohol',
            'test_date' => now()->format('m/d/Y'),
            'location' => 'Updated Location',
            'administered_by' => 'Updated Admin',
            'test_result' => 'passed',
            'status' => 'completed',
            'bill_to' => 'Driver',
        ];
        
        $response = $this->put(route('admin.driver-testings.update', $testing->id), $updateData);
        
        $response->assertRedirect();
        
        // Verify record was updated
        $this->assertDatabaseHas('driver_testings', [
            'id' => $testing->id,
            'location' => 'Updated Location',
            'administered_by' => 'Updated Admin',
            'test_result' => 'passed',
        ]);
    }
}
