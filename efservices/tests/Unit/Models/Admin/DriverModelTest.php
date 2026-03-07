<?php

namespace Tests\Unit\Models\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Carrier;
use App\Models\Membership;
use App\Models\UserDriverDetail;
use App\Models\Admin\Driver\DriverLicense;
use App\Models\Admin\Driver\DriverAccident;
use App\Models\Admin\Driver\DriverTesting;
use App\Models\Admin\Driver\DriverInspection;
use App\Models\Admin\Driver\DriverTrafficConviction;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DriverModelTest extends TestCase
{
    use RefreshDatabase;

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
    public function driver_has_required_fillable_attributes()
    {
        $driver = new UserDriverDetail();
        $fillable = $driver->getFillable();
        
        $this->assertContains('user_id', $fillable);
        $this->assertContains('carrier_id', $fillable);
        $this->assertContains('first_name', $fillable);
        $this->assertContains('last_name', $fillable);
        $this->assertContains('status', $fillable);
    }

    /** @test */
    public function driver_belongs_to_carrier()
    {
        $this->assertInstanceOf(Carrier::class, $this->driver->carrier);
    }

    /** @test */
    public function driver_has_correct_status_constants()
    {
        $this->assertEquals(1, UserDriverDetail::STATUS_ACTIVE);
        $this->assertEquals(0, UserDriverDetail::STATUS_INACTIVE);
    }

    /** @test */
    public function driver_has_many_licenses()
    {
        DriverLicense::factory()->count(3)->create([
            'user_driver_detail_id' => $this->driver->id
        ]);

        $this->assertCount(3, $this->driver->licenses);
        $this->assertInstanceOf(DriverLicense::class, $this->driver->licenses->first());
    }

    /** @test */
    public function driver_has_many_accidents()
    {
        DriverAccident::factory()->count(2)->create([
            'user_driver_detail_id' => $this->driver->id
        ]);

        $this->assertCount(2, $this->driver->accidents);
    }

    /** @test */
    public function driver_has_many_testings()
    {
        DriverTesting::factory()->count(5)->create([
            'user_driver_detail_id' => $this->driver->id
        ]);

        $this->assertCount(5, $this->driver->testings);
    }

    /** @test */
    public function driver_has_many_inspections()
    {
        DriverInspection::factory()->count(4)->create([
            'user_driver_detail_id' => $this->driver->id
        ]);

        $this->assertCount(4, $this->driver->inspections);
    }

    /** @test */
    public function driver_has_many_traffic_convictions()
    {
        DriverTrafficConviction::factory()->count(2)->create([
            'user_driver_detail_id' => $this->driver->id
        ]);

        $this->assertCount(2, $this->driver->trafficConvictions);
    }

    /** @test */
    public function driver_can_be_created_with_factory()
    {
        $newDriver = UserDriverDetail::factory()->create([
            'carrier_id' => $this->carrier->id
        ]);

        $this->assertInstanceOf(UserDriverDetail::class, $newDriver);
        $this->assertDatabaseHas('user_driver_details', [
            'id' => $newDriver->id,
            'carrier_id' => $this->carrier->id
        ]);
    }

    /** @test */
    public function driver_full_name_accessor_works()
    {
        $driver = UserDriverDetail::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'carrier_id' => $this->carrier->id
        ]);

        $this->assertEquals('John Doe', $driver->full_name);
    }

    /** @test */
    public function driver_status_text_accessor_works()
    {
        $activeDriver = UserDriverDetail::factory()->create([
            'status' => UserDriverDetail::STATUS_ACTIVE,
            'carrier_id' => $this->carrier->id
        ]);

        $inactiveDriver = UserDriverDetail::factory()->create([
            'status' => UserDriverDetail::STATUS_INACTIVE,
            'carrier_id' => $this->carrier->id
        ]);

        $this->assertEquals('Active', $activeDriver->status_text);
        $this->assertEquals('Inactive', $inactiveDriver->status_text);
    }

    /** @test */
    public function driver_scope_active_works()
    {
        UserDriverDetail::factory()->count(3)->create([
            'status' => UserDriverDetail::STATUS_ACTIVE,
            'carrier_id' => $this->carrier->id
        ]);
        
        UserDriverDetail::factory()->count(2)->create([
            'status' => UserDriverDetail::STATUS_INACTIVE,
            'carrier_id' => $this->carrier->id
        ]);

        $activeDrivers = UserDriverDetail::active()->get();
        
        $this->assertCount(3, $activeDrivers);
        $this->assertTrue($activeDrivers->every(function ($driver) {
            return $driver->status === UserDriverDetail::STATUS_ACTIVE;
        }));
    }
}
