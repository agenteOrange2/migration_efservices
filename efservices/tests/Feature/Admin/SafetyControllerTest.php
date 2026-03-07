<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Carrier;
use App\Models\Membership;
use App\Models\UserDriverDetail;
use App\Models\Admin\Driver\DriverAccident;
use App\Models\Admin\Driver\DriverInspection;
use App\Http\Controllers\Admin\Driver\AccidentsController;

class SafetyControllerTest extends AdminTestCase
{
    protected $carrier;
    protected $driver;

    protected function setUp(): void
    {
        parent::setUp();

        $membership = Membership::factory()->create();
        $this->carrier = Carrier::factory()->create(['id_plan' => $membership->id]);
        $this->driver = UserDriverDetail::factory()->create([
            'carrier_id' => $this->carrier->id
        ]);
    }

    /** @test */
    public function superadmin_can_access_accidents_index()
    {
        $response = $this->actingAsSuperAdmin()
            ->get(route('accidents.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function superadmin_can_create_accident()
    {
        $accidentData = [
            'user_driver_detail_id' => $this->driver->id,
            'accident_date' => now()->format('Y-m-d'),
            'accident_time' => '14:30',
            'location' => 'Highway 101, California',
            'description' => 'Rear-end collision',
            'fatalities' => 0,
            'injuries' => 0,
            'hazmat_involved' => false,
            'police_report_number' => 'PR-2024-001',
            'status' => 1,
        ];

        $response = $this->actingAsSuperAdmin()
            ->post(route('accidents.store'), $accidentData);

        $response->assertRedirect();
        $this->assertDatabaseHas('admin_driver_accidents', [
            'user_driver_detail_id' => $this->driver->id,
        ]);
    }

    /** @test */
    public function accident_creation_requires_driver()
    {
        $accidentData = [
            'accident_date' => now()->format('Y-m-d'),
            'location' => 'Test location',
            'status' => 1,
        ];

        $response = $this->actingAsSuperAdmin()
            ->post(route('accidents.store'), $accidentData);

        $response->assertSessionHasErrors('user_driver_detail_id');
    }

    /** @test */
    public function superadmin_can_view_accident_details()
    {
        $accident = DriverAccident::factory()->create([
            'user_driver_detail_id' => $this->driver->id
        ]);

        $response = $this->actingAsSuperAdmin()
            ->get(route('accidents.show', $accident));

        $response->assertStatus(200);
    }

    /** @test */
    public function superadmin_can_update_accident()
    {
        $accident = DriverAccident::factory()->create([
            'user_driver_detail_id' => $this->driver->id,
            'location' => 'Original Location'
        ]);

        $updateData = [
            'user_driver_detail_id' => $this->driver->id,
            'accident_date' => $accident->accident_date->format('Y-m-d'),
            'location' => 'Updated Location',
            'status' => $accident->status,
        ];

        $response = $this->actingAsSuperAdmin()
            ->put(route('accidents.update', $accident), $updateData);

        $response->assertRedirect();
        $accident->refresh();
        $this->assertEquals('Updated Location', $accident->location);
    }

    /** @test */
    public function superadmin_can_delete_accident()
    {
        $accident = DriverAccident::factory()->create([
            'user_driver_detail_id' => $this->driver->id
        ]);

        $response = $this->actingAsSuperAdmin()
            ->delete(route('accidents.destroy', $accident));

        $response->assertRedirect();
        $this->assertDatabaseMissing('admin_driver_accidents', ['id' => $accident->id]);
    }

    /** @test */
    public function superadmin_can_access_inspections_index()
    {
        $response = $this->actingAsSuperAdmin()
            ->get(route('inspections.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function superadmin_can_create_inspection()
    {
        $inspectionData = [
            'user_driver_detail_id' => $this->driver->id,
            'inspection_date' => now()->format('Y-m-d'),
            'inspection_type' => 'Annual',
            'inspector_name' => 'John Inspector',
            'result' => 'Pass',
            'status' => 1,
        ];

        $response = $this->actingAsSuperAdmin()
            ->post(route('inspections.store'), $inspectionData);

        $response->assertRedirect();
        $this->assertDatabaseHas('admin_driver_inspections', [
            'user_driver_detail_id' => $this->driver->id,
            'result' => 'Pass',
        ]);
    }

    /** @test */
    public function superadmin_can_view_inspection_details()
    {
        $inspection = DriverInspection::factory()->create([
            'user_driver_detail_id' => $this->driver->id
        ]);

        $response = $this->actingAsSuperAdmin()
            ->get(route('inspections.show', $inspection));

        $response->assertStatus(200);
    }

    /** @test */
    public function superadmin_can_edit_inspection()
    {
        $inspection = DriverInspection::factory()->create([
            'user_driver_detail_id' => $this->driver->id,
            'result' => 'Pass'
        ]);

        $updateData = [
            'user_driver_detail_id' => $this->driver->id,
            'inspection_date' => $inspection->inspection_date->format('Y-m-d'),
            'inspection_type' => $inspection->inspection_type,
            'result' => 'Fail',
            'status' => $inspection->status,
        ];

        $response = $this->actingAsSuperAdmin()
            ->put(route('inspections.update', $inspection), $updateData);

        $response->assertRedirect();
        $inspection->refresh();
        $this->assertEquals('Fail', $inspection->result);
    }

    /** @test */
    public function superadmin_can_delete_inspection()
    {
        $inspection = DriverInspection::factory()->create([
            'user_driver_detail_id' => $this->driver->id
        ]);

        $response = $this->actingAsSuperAdmin()
            ->delete(route('inspections.destroy', $inspection));

        $response->assertRedirect();
        $this->assertDatabaseMissing('admin_driver_inspections', ['id' => $inspection->id]);
    }

    /** @test */
    public function accidents_index_filters_by_carrier()
    {
        $otherDriver = UserDriverDetail::factory()->create([
            'carrier_id' => Carrier::factory()->create(['id_plan' => Membership::factory()->create()->id])->id
        ]);
        
        DriverAccident::factory()->count(3)->create([
            'user_driver_detail_id' => $this->driver->id
        ]);
        DriverAccident::factory()->count(2)->create([
            'user_driver_detail_id' => $otherDriver->id
        ]);

        $response = $this->actingAsSuperAdmin()
            ->get(route('accidents.index', ['carrier' => $this->carrier->id]));

        $response->assertStatus(200);
    }

    /** @test */
    public function guest_cannot_access_safety_controllers()
    {
        $response = $this->get(route('accidents.index'));
        $response->assertRedirect('/login');
    }
}
