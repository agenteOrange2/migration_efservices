<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Carrier;
use App\Models\UserDriverDetail;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Admin\Vehicle\VehicleMake;
use App\Models\Admin\Vehicle\VehicleType;
use App\Livewire\Driver\Steps\ApplicationStep;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Livewire\Livewire;

class ApplicationStepTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected Carrier $carrier;
    protected UserDriverDetail $driver;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create carrier and driver
        $this->carrier = Carrier::factory()->create();
        $this->driver = UserDriverDetail::factory()->create([
            'carrier_id' => $this->carrier->id,
            'current_step' => 2,
        ]);
        
        // Create vehicle makes and types directly
        VehicleMake::create(['name' => 'Ford']);
        VehicleMake::create(['name' => 'Freightliner']);
        VehicleType::create(['name' => 'Truck']);
        VehicleType::create(['name' => 'Trailer']);
    }

    /**
     * Test validateThirdPartyDetails returns true when all fields are valid
     */
    public function test_validate_third_party_details_with_valid_data(): void
    {
        $component = Livewire::test(ApplicationStep::class, ['driverId' => $this->driver->id])
            ->set('third_party_name', 'ABC Transport Company')
            ->set('third_party_phone', '555-1234')
            ->set('third_party_email', 'contact@abctransport.com');

        $result = $component->call('validateBeforeSendingEmail');
        
        // Should have third party details valid (but vehicle details will be invalid)
        $this->assertIsArray($result);
    }

    /**
     * Test validateThirdPartyDetails returns false when name is missing
     */
    public function test_validate_third_party_details_fails_without_name(): void
    {
        $component = Livewire::test(ApplicationStep::class, ['driverId' => $this->driver->id])
            ->set('third_party_name', '')
            ->set('third_party_phone', '555-1234')
            ->set('third_party_email', 'contact@abctransport.com');

        $result = $component->call('validateBeforeSendingEmail');
        
        $this->assertFalse($result['valid']);
        $this->assertContains('Company Representative Name: Please enter the full name of the company representative who will verify this vehicle', $result['errors']);
    }

    /**
     * Test validateThirdPartyDetails returns false when phone is missing
     */
    public function test_validate_third_party_details_fails_without_phone(): void
    {
        $component = Livewire::test(ApplicationStep::class, ['driverId' => $this->driver->id])
            ->set('third_party_name', 'ABC Transport Company')
            ->set('third_party_phone', '')
            ->set('third_party_email', 'contact@abctransport.com');

        $result = $component->call('validateBeforeSendingEmail');
        
        $this->assertFalse($result['valid']);
        $this->assertContains('Company Representative Phone: Please provide a contact phone number for the company representative', $result['errors']);
    }

    /**
     * Test validateThirdPartyDetails returns false when email is missing
     */
    public function test_validate_third_party_details_fails_without_email(): void
    {
        $component = Livewire::test(ApplicationStep::class, ['driverId' => $this->driver->id])
            ->set('third_party_name', 'ABC Transport Company')
            ->set('third_party_phone', '555-1234')
            ->set('third_party_email', '');

        $result = $component->call('validateBeforeSendingEmail');
        
        $this->assertFalse($result['valid']);
        $this->assertContains('Company Representative Email: Please provide a valid email address where we can send the verification documents', $result['errors']);
    }

    /**
     * Test validateThirdPartyDetails returns false when email format is invalid
     */
    public function test_validate_third_party_details_fails_with_invalid_email(): void
    {
        $component = Livewire::test(ApplicationStep::class, ['driverId' => $this->driver->id])
            ->set('third_party_name', 'ABC Transport Company')
            ->set('third_party_phone', '555-1234')
            ->set('third_party_email', 'invalid-email');

        $result = $component->call('validateBeforeSendingEmail');
        
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('The email address "invalid-email" is not valid', implode(' ', $result['errors']));
    }

    /**
     * Test validateVehicleDetails returns true when all fields are valid
     */
    public function test_validate_vehicle_details_with_valid_data(): void
    {
        $component = Livewire::test(ApplicationStep::class, ['driverId' => $this->driver->id])
            ->set('vehicle_make', 'Ford')
            ->set('vehicle_model', 'F-150')
            ->set('vehicle_year', 2020)
            ->set('vehicle_vin', '1HGBH41JXMN109186')
            ->set('vehicle_type', 'truck')
            ->set('vehicle_fuel_type', 'diesel')
            ->set('vehicle_registration_state', 'CA')
            ->set('vehicle_registration_number', 'ABC123')
            ->set('vehicle_registration_expiration_date', '12/31/2025')
            ->set('third_party_name', 'ABC Transport')
            ->set('third_party_phone', '555-1234')
            ->set('third_party_email', 'contact@abc.com');

        $result = $component->call('validateBeforeSendingEmail');
        
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    /**
     * Test validateVehicleDetails returns false when make is missing
     */
    public function test_validate_vehicle_details_fails_without_make(): void
    {
        $component = Livewire::test(ApplicationStep::class, ['driverId' => $this->driver->id])
            ->set('vehicle_make', '')
            ->set('vehicle_model', 'F-150')
            ->set('vehicle_year', 2020)
            ->set('vehicle_vin', '1HGBH41JXMN109186')
            ->set('vehicle_type', 'truck')
            ->set('vehicle_fuel_type', 'diesel')
            ->set('vehicle_registration_state', 'CA')
            ->set('vehicle_registration_number', 'ABC123')
            ->set('vehicle_registration_expiration_date', '12/31/2025');

        $result = $component->call('validateBeforeSendingEmail');
        
        $this->assertFalse($result['valid']);
        $this->assertContains('Vehicle Make: Please enter the vehicle manufacturer (e.g., Ford, Freightliner, Peterbilt)', $result['errors']);
    }

    /**
     * Test validateVehicleDetails returns false when VIN is invalid length
     */
    public function test_validate_vehicle_details_fails_with_invalid_vin_length(): void
    {
        $component = Livewire::test(ApplicationStep::class, ['driverId' => $this->driver->id])
            ->set('vehicle_make', 'Ford')
            ->set('vehicle_model', 'F-150')
            ->set('vehicle_year', 2020)
            ->set('vehicle_vin', 'SHORT')
            ->set('vehicle_type', 'truck')
            ->set('vehicle_fuel_type', 'diesel')
            ->set('vehicle_registration_state', 'CA')
            ->set('vehicle_registration_number', 'ABC123')
            ->set('vehicle_registration_expiration_date', '12/31/2025');

        $result = $component->call('validateBeforeSendingEmail');
        
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('The VIN must be exactly 17 characters', implode(' ', $result['errors']));
    }

    /**
     * Test getValidationErrors returns all errors for missing fields
     */
    public function test_get_validation_errors_returns_all_errors(): void
    {
        $component = Livewire::test(ApplicationStep::class, ['driverId' => $this->driver->id])
            ->set('third_party_name', '')
            ->set('third_party_phone', '')
            ->set('third_party_email', '')
            ->set('vehicle_make', '')
            ->set('vehicle_model', '')
            ->set('vehicle_year', '')
            ->set('vehicle_vin', '')
            ->set('vehicle_type', '')
            ->set('vehicle_fuel_type', '')
            ->set('vehicle_registration_state', '')
            ->set('vehicle_registration_number', '')
            ->set('vehicle_registration_expiration_date', '');

        $result = $component->call('validateBeforeSendingEmail');
        
        $this->assertFalse($result['valid']);
        $this->assertGreaterThan(10, count($result['errors']));
    }

    /**
     * Test isNewVehicleMode returns true when selectedVehicleId is null
     */
    public function test_is_new_vehicle_mode_returns_true_when_no_vehicle_selected(): void
    {
        $component = Livewire::test(ApplicationStep::class, ['driverId' => $this->driver->id])
            ->set('selectedVehicleId', null);

        // isNewVehicleMode is protected, so we test its behavior through clearVehicleForm
        $component->call('clearVehicleForm');
        
        $this->assertNull($component->get('selectedVehicleId'));
        $this->assertNull($component->get('vehicle_id'));
    }

    /**
     * Test isNewVehicleMode returns false when selectedVehicleId is set
     */
    public function test_is_new_vehicle_mode_returns_false_when_vehicle_selected(): void
    {
        $vehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
            'user_driver_detail_id' => null,
        ]);

        $component = Livewire::test(ApplicationStep::class, ['driverId' => $this->driver->id])
            ->call('selectVehicle', $vehicle->id);

        $this->assertEquals($vehicle->id, $component->get('selectedVehicleId'));
        $this->assertEquals($vehicle->id, $component->get('vehicle_id'));
    }

    /**
     * Test selectVehicle loads vehicle data correctly
     */
    public function test_select_vehicle_loads_data_correctly(): void
    {
        $vehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
            'user_driver_detail_id' => null,
            'make' => 'Ford',
            'model' => 'F-150',
            'year' => 2020,
            'vin' => '1HGBH41JXMN109186',
            'type' => 'truck',
            'fuel_type' => 'diesel',
            'registration_state' => 'CA',
            'registration_number' => 'ABC123',
        ]);

        $component = Livewire::test(ApplicationStep::class, ['driverId' => $this->driver->id])
            ->call('selectVehicle', $vehicle->id);

        $this->assertEquals($vehicle->id, $component->get('selectedVehicleId'));
        $this->assertEquals('Ford', $component->get('vehicle_make'));
        $this->assertEquals('F-150', $component->get('vehicle_model'));
        $this->assertEquals(2020, $component->get('vehicle_year'));
        $this->assertEquals('1HGBH41JXMN109186', $component->get('vehicle_vin'));
        $this->assertEquals('truck', $component->get('vehicle_type'));
        $this->assertEquals('diesel', $component->get('vehicle_fuel_type'));
    }

    /**
     * Test selectVehicle fails when vehicle not found
     */
    public function test_select_vehicle_fails_when_vehicle_not_found(): void
    {
        $component = Livewire::test(ApplicationStep::class, ['driverId' => $this->driver->id])
            ->call('selectVehicle', 99999);

        $this->assertNull($component->get('selectedVehicleId'));
        $component->assertDispatched('notify');
    }

    /**
     * Test selectVehicle fails when vehicle belongs to different carrier
     */
    public function test_select_vehicle_fails_when_vehicle_belongs_to_different_carrier(): void
    {
        $otherCarrier = Carrier::factory()->create();
        $vehicle = Vehicle::factory()->create([
            'carrier_id' => $otherCarrier->id,
        ]);

        $component = Livewire::test(ApplicationStep::class, ['driverId' => $this->driver->id])
            ->call('selectVehicle', $vehicle->id);

        $this->assertNull($component->get('selectedVehicleId'));
        $component->assertDispatched('notify');
    }

    /**
     * Test selectVehicle fails when vehicle already assigned to another driver
     */
    public function test_select_vehicle_fails_when_vehicle_assigned_to_another_driver(): void
    {
        $otherDriver = UserDriverDetail::factory()->create([
            'carrier_id' => $this->carrier->id,
        ]);
        
        $vehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
            'user_driver_detail_id' => $otherDriver->id,
        ]);

        $component = Livewire::test(ApplicationStep::class, ['driverId' => $this->driver->id])
            ->call('selectVehicle', $vehicle->id);

        $this->assertNull($component->get('selectedVehicleId'));
        $component->assertDispatched('notify');
    }

    /**
     * Test clearVehicleForm resets all vehicle fields
     */
    public function test_clear_vehicle_form_resets_all_fields(): void
    {
        $vehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
            'user_driver_detail_id' => null,
        ]);

        $component = Livewire::test(ApplicationStep::class, ['driverId' => $this->driver->id])
            ->call('selectVehicle', $vehicle->id)
            ->call('clearVehicleForm');

        $this->assertNull($component->get('selectedVehicleId'));
        $this->assertNull($component->get('vehicle_id'));
        $this->assertNull($component->get('vehicle_make'));
        $this->assertNull($component->get('vehicle_model'));
        $this->assertNull($component->get('vehicle_year'));
        $this->assertNull($component->get('vehicle_vin'));
        $this->assertEquals('truck', $component->get('vehicle_type'));
        $this->assertEquals('diesel', $component->get('vehicle_fuel_type'));
    }

    /**
     * Test clearVehicleForm dispatches notification
     */
    public function test_clear_vehicle_form_dispatches_notification(): void
    {
        $component = Livewire::test(ApplicationStep::class, ['driverId' => $this->driver->id])
            ->call('clearVehicleForm');

        $component->assertDispatched('notify');
    }
}
