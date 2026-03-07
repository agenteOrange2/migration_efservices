<?php

namespace Tests\Feature\Admin;

use App\Models\Carrier;
use App\Models\Membership;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Admin\Vehicle\VehicleMaintenance;

class MaintenanceControllerTest extends AdminTestCase
{
    protected $carrier;
    protected $vehicle;

    protected function setUp(): void
    {
        parent::setUp();

        $membership = Membership::factory()->create();
        $this->carrier = Carrier::factory()->create(['id_plan' => $membership->id]);
        $this->vehicle = Vehicle::factory()->create(['carrier_id' => $this->carrier->id]);
    }

    /** @test */
    public function superadmin_can_access_maintenance_index()
    {
        $response = $this->actingAsSuperAdmin()
            ->get(route('admin.maintenance-system.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function superadmin_can_view_maintenance_create_form()
    {
        $response = $this->actingAsSuperAdmin()
            ->get(route('admin.maintenance-system.create'));

        $response->assertStatus(200);
    }

    /** @test */
    public function superadmin_can_view_maintenance_details()
    {
        $maintenance = VehicleMaintenance::factory()->create([
            'vehicle_id' => $this->vehicle->id
        ]);

        $response = $this->actingAsSuperAdmin()
            ->get(route('admin.maintenance-system.show', $maintenance));

        $response->assertStatus(200);
    }

    /** @test */
    public function superadmin_can_edit_maintenance()
    {
        $maintenance = VehicleMaintenance::factory()->create([
            'vehicle_id' => $this->vehicle->id
        ]);

        $response = $this->actingAsSuperAdmin()
            ->get(route('admin.maintenance-system.edit', $maintenance));

        $response->assertStatus(200);
    }

    /** @test */
    public function superadmin_can_delete_maintenance()
    {
        $maintenance = VehicleMaintenance::factory()->create([
            'vehicle_id' => $this->vehicle->id
        ]);

        $response = $this->actingAsSuperAdmin()
            ->delete(route('admin.maintenance-system.destroy', $maintenance));

        $response->assertRedirect();
    }

    /** @test */
    public function guest_cannot_access_maintenance()
    {
        $response = $this->get(route('admin.maintenance-system.index'));

        $response->assertRedirect('/login');
    }
}
