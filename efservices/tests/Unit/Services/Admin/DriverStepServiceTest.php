<?php

namespace Tests\Unit\Services\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Carrier;
use App\Models\Membership;
use App\Models\UserDriverDetail;
use App\Services\Admin\DriverStepService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class DriverStepServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $driverStepService;
    protected $carrier;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->driverStepService = new DriverStepService();
        
        $membership = Membership::factory()->create();
        $this->carrier = Carrier::factory()->create(['id_plan' => $membership->id]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function service_can_be_instantiated()
    {
        $this->assertInstanceOf(DriverStepService::class, $this->driverStepService);
    }

    /** @test */
    public function service_has_required_methods()
    {
        $this->assertTrue(method_exists($this->driverStepService, 'getStepData'));
        $this->assertTrue(method_exists($this->driverStepService, 'validateStep'));
        $this->assertTrue(method_exists($this->driverStepService, 'saveStep'));
    }
}
