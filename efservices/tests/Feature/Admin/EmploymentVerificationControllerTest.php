<?php

namespace Tests\Feature\Admin;

use App\Models\Carrier;
use App\Models\Membership;
use App\Models\UserDriverDetail;

class EmploymentVerificationControllerTest extends AdminTestCase
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
    public function superadmin_can_access_employment_verification_index()
    {
        $response = $this->actingAsSuperAdmin()
            ->get(route('admin.drivers.employment-verification.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function superadmin_can_create_new_verification()
    {
        $response = $this->actingAsSuperAdmin()
            ->get(route('admin.drivers.employment-verification.new'));

        $response->assertStatus(200);
    }

    /** @test */
    public function guest_cannot_access_employment_verification()
    {
        $response = $this->get(route('admin.drivers.employment-verification.index'));

        $response->assertRedirect('/login');
    }
}
