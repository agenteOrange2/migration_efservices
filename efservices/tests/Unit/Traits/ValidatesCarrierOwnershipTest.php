<?php

namespace Tests\Unit\Traits;

use Tests\TestCase;
use App\Models\User;
use App\Models\Carrier;
use App\Models\Membership;
use App\Models\UserCarrierDetail;
use App\Models\UserDriverDetail;
use App\Traits\ValidatesCarrierOwnership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ValidatesCarrierOwnershipTest extends TestCase
{
    use RefreshDatabase;

    protected $carrier;
    protected $carrierUser;
    protected $driver;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

        // Create membership
        $membership = Membership::factory()->create([
            'name' => 'Basic',
            'max_drivers' => 10,
        ]);

        // Create carrier
        $this->carrier = Carrier::factory()->create([
            'membership_id' => $membership->id,
            'status' => 'active',
        ]);

        // Create carrier user
        $this->carrierUser = User::factory()->create([
            'status' => 1,
        ]);
        $this->carrierUser->assignRole('user_carrier');

        UserCarrierDetail::factory()->create([
            'user_id' => $this->carrierUser->id,
            'carrier_id' => $this->carrier->id,
        ]);

        // Create driver
        $driverUser = User::factory()->create();
        $driverUser->assignRole('user_driver');

        $this->driver = UserDriverDetail::factory()->create([
            'user_id' => $driverUser->id,
            'carrier_id' => $this->carrier->id,
            'status' => 1,
        ]);
    }

    /** @test */
    public function get_authenticated_carrier_returns_carrier_for_authenticated_user()
    {
        Auth::login($this->carrierUser);

        $testClass = new class {
            use ValidatesCarrierOwnership;

            public function testGetCarrier()
            {
                return $this->getAuthenticatedCarrier();
            }
        };

        $carrier = $testClass->testGetCarrier();

        $this->assertInstanceOf(Carrier::class, $carrier);
        $this->assertEquals($this->carrier->id, $carrier->id);
    }

    /** @test */
    public function validate_carrier_ownership_passes_for_owned_resource()
    {
        Auth::login($this->carrierUser);

        $testClass = new class {
            use ValidatesCarrierOwnership;

            public function testValidate($resource)
            {
                $this->validateCarrierOwnership($resource);
                return true;
            }
        };

        $result = $testClass->testValidate($this->driver);

        $this->assertTrue($result);
    }

    /** @test */
    public function validate_carrier_ownership_aborts_for_non_owned_resource()
    {
        Auth::login($this->carrierUser);

        // Create another carrier and driver
        $otherMembership = Membership::factory()->create([
            'name' => 'Standard-' . uniqid(),
        ]);
        $otherCarrier = Carrier::factory()->create([
            'membership_id' => $otherMembership->id,
        ]);

        $otherDriverUser = User::factory()->create();
        $otherDriverUser->assignRole('user_driver');

        $otherDriver = UserDriverDetail::factory()->create([
            'user_id' => $otherDriverUser->id,
            'carrier_id' => $otherCarrier->id,
        ]);

        $testClass = new class {
            use ValidatesCarrierOwnership;

            public function testValidate($resource)
            {
                $this->validateCarrierOwnership($resource);
            }
        };

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Unauthorized access to resource.');

        $testClass->testValidate($otherDriver);
    }

    /** @test */
    public function validate_carrier_ownership_uses_custom_error_message()
    {
        Auth::login($this->carrierUser);

        // Create another carrier and driver
        $otherMembership = Membership::factory()->create([
            'name' => 'Premium-' . uniqid(),
        ]);
        $otherCarrier = Carrier::factory()->create([
            'membership_id' => $otherMembership->id,
        ]);

        $otherDriverUser = User::factory()->create();
        $otherDriverUser->assignRole('user_driver');

        $otherDriver = UserDriverDetail::factory()->create([
            'user_id' => $otherDriverUser->id,
            'carrier_id' => $otherCarrier->id,
        ]);

        $testClass = new class {
            use ValidatesCarrierOwnership;

            public function testValidate($resource, $message)
            {
                $this->validateCarrierOwnership($resource, $message);
            }
        };

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Custom error message');

        $testClass->testValidate($otherDriver, 'Custom error message');
    }

    /** @test */
    public function belongs_to_authenticated_carrier_returns_true_for_owned_resource()
    {
        Auth::login($this->carrierUser);

        $testClass = new class {
            use ValidatesCarrierOwnership;

            public function testBelongs($resource)
            {
                return $this->belongsToAuthenticatedCarrier($resource);
            }
        };

        $result = $testClass->testBelongs($this->driver);

        $this->assertTrue($result);
    }

    /** @test */
    public function belongs_to_authenticated_carrier_returns_false_for_non_owned_resource()
    {
        Auth::login($this->carrierUser);

        // Create another carrier and driver
        $otherMembership = Membership::factory()->create([
            'name' => 'Professional-' . uniqid(),
        ]);
        $otherCarrier = Carrier::factory()->create([
            'membership_id' => $otherMembership->id,
        ]);

        $otherDriverUser = User::factory()->create();
        $otherDriverUser->assignRole('user_driver');

        $otherDriver = UserDriverDetail::factory()->create([
            'user_id' => $otherDriverUser->id,
            'carrier_id' => $otherCarrier->id,
        ]);

        $testClass = new class {
            use ValidatesCarrierOwnership;

            public function testBelongs($resource)
            {
                return $this->belongsToAuthenticatedCarrier($resource);
            }
        };

        $result = $testClass->testBelongs($otherDriver);

        $this->assertFalse($result);
    }

    /** @test */
    public function validate_carrier_ownership_bulk_passes_for_all_owned_resources()
    {
        Auth::login($this->carrierUser);

        // Create multiple drivers for the same carrier
        $driverUser2 = User::factory()->create();
        $driverUser2->assignRole('user_driver');

        $driver2 = UserDriverDetail::factory()->create([
            'user_id' => $driverUser2->id,
            'carrier_id' => $this->carrier->id,
        ]);

        $testClass = new class {
            use ValidatesCarrierOwnership;

            public function testValidateBulk($resources)
            {
                $this->validateCarrierOwnershipBulk($resources);
                return true;
            }
        };

        $result = $testClass->testValidateBulk([$this->driver, $driver2]);

        $this->assertTrue($result);
    }

    /** @test */
    public function validate_carrier_ownership_bulk_aborts_if_any_resource_not_owned()
    {
        Auth::login($this->carrierUser);

        // Create another carrier and driver
        $otherMembership = Membership::factory()->create([
            'name' => 'Enterprise-' . uniqid(),
        ]);
        $otherCarrier = Carrier::factory()->create([
            'membership_id' => $otherMembership->id,
        ]);

        $otherDriverUser = User::factory()->create();
        $otherDriverUser->assignRole('user_driver');

        $otherDriver = UserDriverDetail::factory()->create([
            'user_id' => $otherDriverUser->id,
            'carrier_id' => $otherCarrier->id,
        ]);

        $testClass = new class {
            use ValidatesCarrierOwnership;

            public function testValidateBulk($resources)
            {
                $this->validateCarrierOwnershipBulk($resources);
            }
        };

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Unauthorized access to one or more resources.');

        $testClass->testValidateBulk([$this->driver, $otherDriver]);
    }
}
