<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Carrier;
use App\Models\UserCarrierDetail;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Admin\Vehicle\VehicleMake;
use App\Models\Admin\Vehicle\VehicleType;
use App\Http\Controllers\Carrier\CarrierVehicleController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mockery;

class CarrierVehicleControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $carrier;
    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a carrier and user
        $this->carrier = Carrier::factory()->create();
        $this->user = User::factory()->create();
        
        // Create carrier detail for the user
        UserCarrierDetail::factory()->create([
            'user_id' => $this->user->id,
            'carrier_id' => $this->carrier->id,
        ]);
        
        // Create vehicle makes and types
        VehicleMake::create(['name' => 'Ford']);
        VehicleType::create(['name' => 'Truck']);
        
        $this->controller = new CarrierVehicleController();
        
        // Mock Auth facade
        Auth::shouldReceive('user')->andReturn($this->user);
    }

    public function test_get_filter_options_returns_correct_structure()
    {
        // Create some test vehicles
        Vehicle::create([
            'carrier_id' => $this->carrier->id,
            'make' => 'Ford',
            'type' => 'Truck',
            'model' => 'F-150',
            'year' => 2020,
            'vin' => '1HGBH41JXMN109186',
            'registration_state' => 'TX',
            'registration_number' => 'ABC123',
            'registration_expiration_date' => now()->addYear(),
            'fuel_type' => 'Diesel'
        ]);

        $request = new Request();
        $response = $this->controller->getFilterOptions($request);
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertArrayHasKey('types', $data);
        $this->assertArrayHasKey('makes', $data);
        $this->assertArrayHasKey('drivers', $data);
        $this->assertArrayHasKey('statuses', $data);
        
        // Check that we have the expected data
        $this->assertCount(1, $data['types']);
        $this->assertEquals('Truck', $data['types'][0]['value']);
        
        $this->assertCount(1, $data['makes']);
        $this->assertEquals('Ford', $data['makes'][0]['value']);
        
        $this->assertCount(3, $data['statuses']);
    }

    public function test_get_statistics_returns_correct_structure()
    {
        // Create some test vehicles
        Vehicle::create([
            'carrier_id' => $this->carrier->id,
            'make' => 'Ford',
            'type' => 'Truck',
            'model' => 'F-150',
            'year' => 2020,
            'vin' => '1HGBH41JXMN109186',
            'registration_state' => 'TX',
            'registration_number' => 'ABC123',
            'registration_expiration_date' => now()->addYear(),
            'fuel_type' => 'Diesel',
            'out_of_service' => false,
            'suspended' => false
        ]);

        $response = $this->controller->getStatistics();
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        
        $this->assertArrayHasKey('total_vehicles', $data);
        $this->assertArrayHasKey('active_vehicles', $data);
        $this->assertArrayHasKey('out_of_service_vehicles', $data);
        $this->assertArrayHasKey('suspended_vehicles', $data);
        $this->assertArrayHasKey('unassigned_vehicles', $data);
        $this->assertArrayHasKey('vehicles_with_expiring_docs', $data);
        $this->assertArrayHasKey('vehicles_with_overdue_maintenance', $data);
        $this->assertArrayHasKey('utilization_rate', $data);
        
        // Check the values
        $this->assertEquals(1, $data['total_vehicles']);
        $this->assertEquals(1, $data['active_vehicles']);
        $this->assertEquals(0, $data['out_of_service_vehicles']);
        $this->assertEquals(0, $data['suspended_vehicles']);
    }

    public function test_apply_filters_method_works_correctly()
    {
        // Create test vehicles
        $activeVehicle = Vehicle::create([
            'carrier_id' => $this->carrier->id,
            'make' => 'Ford',
            'type' => 'Truck',
            'model' => 'F-150',
            'year' => 2020,
            'vin' => '1HGBH41JXMN109186',
            'registration_state' => 'TX',
            'registration_number' => 'ABC123',
            'registration_expiration_date' => now()->addYear(),
            'fuel_type' => 'Diesel',
            'out_of_service' => false,
            'suspended' => false,
            'company_unit_number' => 'UNIT001'
        ]);

        $outOfServiceVehicle = Vehicle::create([
            'carrier_id' => $this->carrier->id,
            'make' => 'Chevrolet',
            'type' => 'Van',
            'model' => 'Express',
            'year' => 2019,
            'vin' => '2HGBH41JXMN109187',
            'registration_state' => 'TX',
            'registration_number' => 'DEF456',
            'registration_expiration_date' => now()->addYear(),
            'fuel_type' => 'Gas',
            'out_of_service' => true,
            'suspended' => false,
            'company_unit_number' => 'UNIT002'
        ]);

        // Test search filter
        $query = Vehicle::where('carrier_id', $this->carrier->id);
        $request = new Request(['search' => 'UNIT001']);
        
        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('applyFilters');
        $method->setAccessible(true);
        
        $method->invoke($this->controller, $query, $request);
        $results = $query->get();
        
        $this->assertCount(1, $results);
        $this->assertEquals('UNIT001', $results->first()->company_unit_number);

        // Test status filter
        $query = Vehicle::where('carrier_id', $this->carrier->id);
        $request = new Request(['status' => 'out_of_service']);
        
        $method->invoke($this->controller, $query, $request);
        $results = $query->get();
        
        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->out_of_service);
    }

    public function test_verify_vehicle_access_helper_exists()
    {
        // Create a vehicle for this carrier
        $vehicle = Vehicle::create([
            'carrier_id' => $this->carrier->id,
            'make' => 'Ford',
            'type' => 'Truck',
            'model' => 'F-150',
            'year' => 2020,
            'vin' => '1HGBH41JXMN109187',
            'registration_state' => 'TX',
            'registration_number' => 'ABC124',
            'registration_expiration_date' => now()->addYear(),
            'fuel_type' => 'Diesel'
        ]);

        // Use reflection to access the private method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('verifyVehicleAccess');
        $method->setAccessible(true);

        // Should not throw exception for own vehicle
        try {
            $method->invoke($this->controller, $vehicle);
            $this->assertTrue(true); // If we get here, no exception was thrown
        } catch (\Exception $e) {
            $this->fail('verifyVehicleAccess should not throw exception for own vehicle');
        }
    }

    public function test_verify_vehicle_access_throws_403_for_other_carrier()
    {
        // Create another carrier
        $otherCarrier = Carrier::factory()->create();
        
        // Create a vehicle for the other carrier
        $vehicle = Vehicle::create([
            'carrier_id' => $otherCarrier->id,
            'make' => 'Ford',
            'type' => 'Truck',
            'model' => 'F-150',
            'year' => 2020,
            'vin' => '1HGBH41JXMN109188',
            'registration_state' => 'TX',
            'registration_number' => 'ABC125',
            'registration_expiration_date' => now()->addYear(),
            'fuel_type' => 'Diesel'
        ]);

        // Use reflection to access the private method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('verifyVehicleAccess');
        $method->setAccessible(true);

        // Should throw 403 exception for other carrier's vehicle
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionCode(403);
        
        $method->invoke($this->controller, $vehicle);
    }
}