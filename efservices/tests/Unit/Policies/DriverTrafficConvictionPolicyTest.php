<?php

namespace Tests\Unit\Policies;

use Tests\TestCase;
use App\Models\User;
use App\Models\Carrier;
use App\Models\Membership;
use App\Models\UserCarrierDetail;
use App\Models\UserDriverDetail;
use App\Models\Admin\Driver\DriverTrafficConviction;
use App\Policies\DriverTrafficConvictionPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class DriverTrafficConvictionPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected $policy;
    protected $carrier;
    protected $driver;
    protected $conviction;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->policy = new DriverTrafficConvictionPolicy();
        
        $membership = Membership::factory()->create();
        $this->carrier = Carrier::factory()->create(['id_plan' => $membership->id]);
        $this->driver = UserDriverDetail::factory()->create([
            'carrier_id' => $this->carrier->id
        ]);
        $this->conviction = DriverTrafficConviction::factory()->create([
            'user_driver_detail_id' => $this->driver->id
        ]);
    }

    /** @test */
    public function carrier_can_view_any_traffic_convictions()
    {
        $user = User::factory()->create();
        Role::create(['name' => 'user_carrier']);
        $user->assignRole('user_carrier');
        
        $user->carrierDetails()->create([
            'carrier_id' => $this->carrier->id,
            'status' => 1,
        ]);

        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function admin_cannot_view_any_traffic_convictions()
    {
        $user = User::factory()->create();
        Role::create(['name' => 'admin']);
        $user->assignRole('admin');

        $this->assertFalse($this->policy->viewAny($user));
    }

    /** @test */
    public function carrier_can_view_own_driver_conviction()
    {
        $user = User::factory()->create();
        Role::create(['name' => 'user_carrier']);
        $user->assignRole('user_carrier');
        
        $user->carrierDetails()->create([
            'carrier_id' => $this->carrier->id,
            'status' => 1,
        ]);

        $this->assertTrue($this->policy->view($user, $this->conviction));
    }

    /** @test */
    public function carrier_cannot_view_other_carrier_driver_conviction()
    {
        $otherCarrier = Carrier::factory()->create(['id_plan' => Membership::factory()->create()->id]);
        
        $user = User::factory()->create();
        Role::create(['name' => 'user_carrier']);
        $user->assignRole('user_carrier');
        
        $user->carrierDetails()->create([
            'carrier_id' => $otherCarrier->id,
            'status' => 1,
        ]);

        $this->assertFalse($this->policy->view($user, $this->conviction));
    }

    /** @test */
    public function carrier_without_details_cannot_view()
    {
        $user = User::factory()->create();
        Role::create(['name' => 'user_carrier']);
        $user->assignRole('user_carrier');

        $this->assertFalse($this->policy->view($user, $this->conviction));
    }

    /** @test */
    public function carrier_can_create_traffic_convictions()
    {
        $user = User::factory()->create();
        Role::create(['name' => 'user_carrier']);
        $user->assignRole('user_carrier');
        
        $user->carrierDetails()->create([
            'carrier_id' => $this->carrier->id,
            'status' => 1,
        ]);

        $this->assertTrue($this->policy->create($user));
    }

    /** @test */
    public function carrier_can_update_own_driver_conviction()
    {
        $user = User::factory()->create();
        Role::create(['name' => 'user_carrier']);
        $user->assignRole('user_carrier');
        
        $user->carrierDetails()->create([
            'carrier_id' => $this->carrier->id,
            'status' => 1,
        ]);

        $this->assertTrue($this->policy->update($user, $this->conviction));
    }

    /** @test */
    public function carrier_can_delete_own_driver_conviction()
    {
        $user = User::factory()->create();
        Role::create(['name' => 'user_carrier']);
        $user->assignRole('user_carrier');
        
        $user->carrierDetails()->create([
            'carrier_id' => $this->carrier->id,
            'status' => 1,
        ]);

        $this->assertTrue($this->policy->delete($user, $this->conviction));
    }

    /** @test */
    public function guest_cannot_perform_any_actions()
    {
        $this->assertFalse($this->policy->viewAny(null));
        $this->assertFalse($this->policy->view(null, $this->conviction));
        $this->assertFalse($this->policy->create(null));
        $this->assertFalse($this->policy->update(null, $this->conviction));
        $this->assertFalse($this->policy->delete(null, $this->conviction));
    }
}
