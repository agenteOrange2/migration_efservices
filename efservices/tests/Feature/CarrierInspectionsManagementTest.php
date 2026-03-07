<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Carrier;
use App\Models\UserCarrierDetail;
use App\Models\UserDriverDetail;
use App\Models\Admin\Driver\DriverInspection;
use App\Models\Admin\Vehicle\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CarrierInspectionsManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $carrier;
    protected $carrierUser;
    protected $driver;
    protected $vehicle;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a carrier
        $this->carrier = Carrier::factory()->create();

        // Create a carrier user with proper role
        $this->carrierUser = User::factory()->create([
            'role' => 'carrier',
        ]);
        
        $carrierDetail = UserCarrierDetail::factory()->create([
            'user_id' => $this->carrierUser->id,
            'carrier_id' => $this->carrier->id,
        ]);
        
        // Ensure the relationship is loaded
        $this->carrierUser->setRelation('carrierDetails', $carrierDetail);

        // Create a driver for this carrier
        $this->driver = UserDriverDetail::factory()->create([
            'carrier_id' => $this->carrier->id,
        ]);

        // Create a vehicle for this carrier
        $this->vehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
            'user_driver_detail_id' => null,
        ]);
    }

    /** @test */
    public function carrier_can_create_inspection_with_new_fields()
    {
        $this->actingAs($this->carrierUser);

        $inspectionData = [
            'user_driver_detail_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'inspection_date' => '2024-01-15',
            'inspection_type' => 'DOT Roadside',
            'inspection_level' => 'Level I',
            'inspector_name' => 'John Inspector',
            'inspector_number' => 'INS-12345',
            'location' => 'Highway 101',
            'status' => 'Pass',
            'defects_found' => 'Minor tire wear',
            'corrective_actions' => 'Scheduled tire replacement',
            'is_defects_corrected' => true,
            'defects_corrected_date' => '2024-01-16',
            'corrected_by' => 'Mechanic Smith',
            'is_vehicle_safe_to_operate' => true,
            'notes' => 'Routine inspection passed',
        ];

        $response = $this->post(route('carrier.drivers.inspections.store'), $inspectionData);

        $response->assertRedirect(route('carrier.drivers.inspections.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('driver_inspections', [
            'user_driver_detail_id' => $this->driver->id,
            'inspection_level' => 'Level I',
            'inspector_number' => 'INS-12345',
            'inspector_name' => 'John Inspector',
        ]);
    }

    /** @test */
    public function carrier_can_update_inspection_with_new_fields()
    {
        $this->actingAs($this->carrierUser);

        $inspection = DriverInspection::factory()->create([
            'user_driver_detail_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'inspection_level' => 'Level II',
            'inspector_number' => 'OLD-123',
        ]);

        $updateData = [
            'user_driver_detail_id' => $this->driver->id,
            'vehicle_id' => $this->vehicle->id,
            'inspection_date' => '2024-01-20',
            'inspection_type' => 'Annual DOT',
            'inspection_level' => 'Level I',
            'inspector_name' => 'Jane Inspector',
            'inspector_number' => 'NEW-456',
            'location' => 'Main Terminal',
            'status' => 'Pass',
            'is_vehicle_safe_to_operate' => true,
        ];

        $response = $this->put(route('carrier.drivers.inspections.update', $inspection), $updateData);

        $response->assertRedirect(route('carrier.drivers.inspections.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('driver_inspections', [
            'id' => $inspection->id,
            'inspection_level' => 'Level I',
            'inspector_number' => 'NEW-456',
        ]);
    }

    /** @test */
    public function validation_works_correctly_for_new_fields()
    {
        $this->actingAs($this->carrierUser);

        // Test with inspection_level exceeding max length
        $inspectionData = [
            'user_driver_detail_id' => $this->driver->id,
            'inspection_date' => '2024-01-15',
            'inspection_type' => 'DOT Roadside',
            'inspection_level' => str_repeat('a', 51), // Exceeds 50 char limit
            'inspector_name' => 'John Inspector',
            'inspector_number' => str_repeat('b', 51), // Exceeds 50 char limit
            'status' => 'Pass',
            'is_vehicle_safe_to_operate' => true,
        ];

        $response = $this->post(route('carrier.drivers.inspections.store'), $inspectionData);

        $response->assertSessionHasErrors(['inspection_level', 'inspector_number']);
    }

    /** @test */
    public function nullable_fields_work_correctly()
    {
        $this->actingAs($this->carrierUser);

        $inspectionData = [
            'user_driver_detail_id' => $this->driver->id,
            'inspection_date' => '2024-01-15',
            'inspection_type' => 'Pre-trip',
            'inspection_level' => null, // Nullable
            'inspector_name' => 'John Inspector',
            'inspector_number' => null, // Nullable
            'status' => 'Pass',
            'is_vehicle_safe_to_operate' => true,
        ];

        $response = $this->post(route('carrier.drivers.inspections.store'), $inspectionData);

        $response->assertRedirect(route('carrier.drivers.inspections.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('driver_inspections', [
            'user_driver_detail_id' => $this->driver->id,
            'inspection_level' => null,
            'inspector_number' => null,
        ]);
    }

    /** @test */
    public function existing_functionality_still_works_with_file_uploads()
    {
        Storage::fake('public');
        $this->actingAs($this->carrierUser);

        $file = UploadedFile::fake()->create('inspection_report.pdf', 100);

        $inspectionData = [
            'user_driver_detail_id' => $this->driver->id,
            'inspection_date' => '2024-01-15',
            'inspection_type' => 'DOT Roadside',
            'inspection_level' => 'Level I',
            'inspector_name' => 'John Inspector',
            'inspector_number' => 'INS-12345',
            'status' => 'Pass',
            'is_vehicle_safe_to_operate' => true,
            'inspection_reports' => [$file],
        ];

        $response = $this->post(route('carrier.drivers.inspections.store'), $inspectionData);

        $response->assertRedirect(route('carrier.drivers.inspections.index'));
        
        $inspection = DriverInspection::latest()->first();
        $this->assertNotNull($inspection);
        $this->assertEquals('Level I', $inspection->inspection_level);
        $this->assertEquals('INS-12345', $inspection->inspector_number);
    }

    /** @test */
    public function carrier_cannot_access_other_carrier_inspections()
    {
        // Create another carrier and driver
        $otherCarrier = Carrier::factory()->create();
        $otherDriver = UserDriverDetail::factory()->create([
            'carrier_id' => $otherCarrier->id,
        ]);

        $otherInspection = DriverInspection::factory()->create([
            'user_driver_detail_id' => $otherDriver->id,
        ]);

        $this->actingAs($this->carrierUser);

        // Try to edit another carrier's inspection
        $response = $this->get(route('carrier.drivers.inspections.edit', $otherInspection));
        $response->assertRedirect(route('carrier.drivers.inspections.index'));
        $response->assertSessionHas('error');

        // Try to update another carrier's inspection
        $response = $this->put(route('carrier.drivers.inspections.update', $otherInspection), [
            'user_driver_detail_id' => $otherDriver->id,
            'inspection_date' => '2024-01-15',
            'inspection_type' => 'DOT Roadside',
            'inspector_name' => 'Hacker',
            'status' => 'Pass',
            'is_vehicle_safe_to_operate' => true,
        ]);
        $response->assertRedirect(route('carrier.drivers.inspections.index'));
        $response->assertSessionHas('error');
    }

    /** @test */
    public function defects_correction_logic_works_correctly()
    {
        $this->actingAs($this->carrierUser);

        // Test: defects corrected without date should auto-set date
        $inspectionData = [
            'user_driver_detail_id' => $this->driver->id,
            'inspection_date' => '2024-01-15',
            'inspection_type' => 'DOT Roadside',
            'inspector_name' => 'John Inspector',
            'status' => 'Pass',
            'is_defects_corrected' => true,
            'defects_corrected_date' => null, // Should be auto-set
            'is_vehicle_safe_to_operate' => true,
        ];

        $response = $this->post(route('carrier.drivers.inspections.store'), $inspectionData);
        $response->assertRedirect(route('carrier.drivers.inspections.index'));

        $inspection = DriverInspection::latest()->first();
        $this->assertNotNull($inspection->defects_corrected_date);
    }

    /** @test */
    public function vehicle_loading_works_for_driver()
    {
        $this->actingAs($this->carrierUser);

        // Assign vehicle to driver
        $assignedVehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
            'user_driver_detail_id' => $this->driver->id,
        ]);

        // Create unassigned vehicle
        $unassignedVehicle = Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id,
            'user_driver_detail_id' => null,
        ]);

        $response = $this->get(route('carrier.drivers.inspections.vehicles.by.driver', $this->driver));

        $response->assertStatus(200);
        $vehicles = $response->json();

        // Should include both assigned and unassigned vehicles
        $vehicleIds = collect($vehicles)->pluck('id')->toArray();
        $this->assertContains($assignedVehicle->id, $vehicleIds);
        $this->assertContains($unassignedVehicle->id, $vehicleIds);
    }

    /** @test */
    public function create_form_displays_new_fields()
    {
        $this->actingAs($this->carrierUser);

        $response = $this->get(route('carrier.drivers.inspections.create'));

        $response->assertStatus(200);
        $response->assertSee('Inspection Level');
        $response->assertSee('Inspector Number/Badge');
        $response->assertSee('inspection_level');
        $response->assertSee('inspector_number');
    }

    /** @test */
    public function edit_form_displays_new_fields_with_values()
    {
        $this->actingAs($this->carrierUser);

        $inspection = DriverInspection::factory()->create([
            'user_driver_detail_id' => $this->driver->id,
            'inspection_level' => 'Level III',
            'inspector_number' => 'TEST-789',
        ]);

        $response = $this->get(route('carrier.drivers.inspections.edit', $inspection));

        $response->assertStatus(200);
        $response->assertSee('Inspection Level');
        $response->assertSee('Inspector Number/Badge');
        $response->assertSee('Level III');
        $response->assertSee('TEST-789');
    }
}
