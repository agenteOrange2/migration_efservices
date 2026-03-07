<?php

namespace Tests\Feature\Admin;

use App\Models\Carrier;
use App\Models\Membership;
use App\Models\Admin\Vehicle\Vehicle;

class VehicleControllerTest extends AdminTestCase
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
    public function superadmin_can_access_vehicles_index()
    {
        Vehicle::factory()->count(5)->create(['carrier_id' => $this->carrier->id]);

        $response = $this->actingAsSuperAdmin()
            ->get(route('admin.admin-vehicles.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function superadmin_can_view_vehicle_create_form()
    {
        $response = $this->actingAsSuperAdmin()
            ->get(route('admin.admin-vehicles.create'));

        $response->assertStatus(200);
    }

    /** @test */
    public function superadmin_can_view_vehicle_details()
    {
        $response = $this->actingAsSuperAdmin()
            ->get(route('admin.admin-vehicles.show', $this->vehicle));

        $response->assertStatus(200);
    }

    /** @test */
    public function superadmin_can_view_vehicle_edit_form()
    {
        $response = $this->actingAsSuperAdmin()
            ->get(route('admin.admin-vehicles.edit', $this->vehicle));

        $response->assertStatus(200);
    }

    /** @test */
    public function superadmin_can_delete_vehicle()
    {
        $vehicle = Vehicle::factory()->create(['carrier_id' => $this->carrier->id]);

        $response = $this->actingAsSuperAdmin()
            ->delete(route('admin.admin-vehicles.destroy', $vehicle));

        $response->assertRedirect();
    }

    /** @test */
    public function guest_cannot_access_vehicles()
    {
        $response = $this->get(route('admin.admin-vehicles.index'));

        $response->assertRedirect('/login');
    }
}
