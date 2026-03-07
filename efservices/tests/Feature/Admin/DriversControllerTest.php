<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Carrier;
use App\Models\Membership;
use App\Models\UserDriverDetail;

class DriversControllerTest extends AdminTestCase
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
    public function superadmin_can_access_drivers_index()
    {
        $response = $this->actingAsSuperAdmin()
            ->get(route('drivers.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.drivers.index');
    }

    /** @test */
    public function authenticated_user_with_permission_can_access_drivers_index()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $response = $this->actingAs($user)
            ->get(route('drivers.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function guest_cannot_access_drivers_index()
    {
        $response = $this->get(route('drivers.index'));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function drivers_index_displays_drivers()
    {
        UserDriverDetail::factory()->count(5)->create([
            'carrier_id' => $this->carrier->id
        ]);

        $response = $this->actingAsSuperAdmin()
            ->get(route('drivers.index'));

        $response->assertStatus(200);
        $response->assertViewHas('drivers');
    }

    /** @test */
    public function drivers_index_displays_statistics()
    {
        UserDriverDetail::factory()->count(3)->create([
            'carrier_id' => $this->carrier->id,
            'status' => UserDriverDetail::STATUS_ACTIVE
        ]);
        
        UserDriverDetail::factory()->count(2)->create([
            'carrier_id' => $this->carrier->id,
            'status' => UserDriverDetail::STATUS_INACTIVE
        ]);

        $response = $this->actingAsSuperAdmin()
            ->get(route('drivers.index'));

        $response->assertStatus(200);
        $response->assertViewHas('stats');
    }

    /** @test */
    public function drivers_controller_index_works()
    {
        $response = $this->actingAsSuperAdmin()
            ->get(route('drivers.index'));

        $response->assertStatus(200);
    }
}
