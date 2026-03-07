<?php

namespace Tests\Feature\Admin;

use App\Models\Carrier;
use App\Models\Membership;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Admin\Vehicle\VehicleMaintenance;

class VehicleMaintenanceControllerTest extends AdminTestCase
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
    public function superadmin_can_access_vehicle_maintenance_index()
    {
        $response = $this->actingAsSuperAdmin()
            ->get(route('admin.admin-vehicles.maintenance.index', $this->vehicle));

        $response->assertStatus(200);
    }

    /** @test */
    public function superadmin_can_view_maintenance_create_form()
    {
        $response = $this->actingAsSuperAdmin()
            ->get(route('admin.admin-vehicles.maintenance.create', $this->vehicle));

        $response->assertStatus(200);
    }

    /** @test */
    public function superadmin_can_view_maintenance_details()
    {
        $maintenance = VehicleMaintenance::factory()->create([
            'vehicle_id' => $this->vehicle->id
        ]);

        $response = $this->actingAsSuperAdmin()
            ->get(route('admin.admin-vehicles.maintenance.show', [$this->vehicle, $maintenance->id]));

        $response->assertStatus(200);
    }

    /** @test */
    public function superadmin_can_view_maintenance_edit_form()
    {
        $maintenance = VehicleMaintenance::factory()->create([
            'vehicle_id' => $this->vehicle->id
        ]);

        $response = $this->actingAsSuperAdmin()
            ->get(route('admin.admin-vehicles.maintenance.edit', [$this->vehicle, $maintenance->id]));

        $response->assertStatus(200);
    }

    /** @test */
    public function superadmin_can_delete_maintenance()
    {
        $maintenance = VehicleMaintenance::factory()->create([
            'vehicle_id' => $this->vehicle->id
        ]);

        $response = $this->actingAsSuperAdmin()
            ->delete(route('admin.admin-vehicles.maintenance.destroy', [$this->vehicle, $maintenance->id]));

        $response->assertRedirect();
    }

    /** @test */
    public function guest_cannot_access_vehicle_maintenance()
    {
        $response = $this->get(route('admin.admin-vehicles.maintenance.index', $this->vehicle));

        $response->assertRedirect('/login');
    }
}
