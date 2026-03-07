<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Carrier;
use App\Models\Membership;
use App\Services\Carrier\CarrierRegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;

class CarrierRegistrationServiceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected CarrierRegistrationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed roles and permissions required by tests
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->service = new CarrierRegistrationService();
    }

    /**
     * Test que un usuario carrier puede ser creado
     */
    public function test_carrier_user_can_be_created(): void
    {
        $userData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'Password123!',
        ];

        $user = $this->service->createCarrierUser($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($userData['email'], $user->email);
        $this->assertTrue(Hash::check($userData['password'], $user->password));
        $this->assertTrue($user->hasRole('user_carrier'));
    }

    /**
     * Test que una empresa carrier puede ser creada
     */
    public function test_carrier_company_can_be_created(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user_carrier');

        $companyData = [
            'name' => $this->faker->company,
            'address' => $this->faker->streetAddress,
            'state' => 'CA',
            'zipcode' => '90001',
            'country' => 'USA',
            'dot_number' => $this->faker->numerify('######'),
            'mc_number' => $this->faker->numerify('######'),
            'ein_number' => $this->faker->numerify('##-#######'),
            'business_type' => 'LLC',
            'years_in_business' => 5,
            'fleet_size' => 10,
        ];

        $carrier = $this->service->createCarrierCompany($user, $companyData);

        $this->assertInstanceOf(Carrier::class, $carrier);
        $this->assertEquals($companyData['name'], $carrier->name);
        $this->assertEquals($companyData['dot_number'], $carrier->dot_number);
        $this->assertEquals(Carrier::STATUS_PENDING, $carrier->status);
        $this->assertNotNull($carrier->referrer_token);
    }

    /**
     * Test que se crea UserCarrierDetail al crear la empresa
     */
    public function test_user_carrier_detail_is_created_with_company(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user_carrier');

        $companyData = [
            'name' => $this->faker->company,
            'address' => $this->faker->streetAddress,
            'state' => 'CA',
            'zipcode' => '90001',
            'dot_number' => $this->faker->numerify('######'),
            'ein_number' => $this->faker->numerify('##-#######'),
            'business_type' => 'LLC',
            'years_in_business' => 5,
            'fleet_size' => 10,
        ];

        $carrier = $this->service->createCarrierCompany($user, $companyData);

        $this->assertDatabaseHas('user_carrier_details', [
            'user_id' => $user->id,
            'carrier_id' => $carrier->id,
        ]);
    }

    /**
     * Test que una membresía puede ser asignada al carrier
     */
    public function test_membership_can_be_assigned_to_carrier(): void
    {
        $carrier = Carrier::factory()->create();
        $membership = Membership::factory()->create();

        $updatedCarrier = $this->service->assignMembership($carrier, $membership->id);

        $this->assertEquals($membership->id, $updatedCarrier->id_plan);
        $this->assertEquals($membership->id, $updatedCarrier->membership_id);
    }

    /**
     * Test que falla al asignar membresía inexistente
     */
    public function test_fails_to_assign_nonexistent_membership(): void
    {
        $carrier = Carrier::factory()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Membership not found');

        $this->service->assignMembership($carrier, 99999);
    }

    /**
     * Test que los detalles bancarios pueden ser guardados
     */
    public function test_banking_details_can_be_saved(): void
    {
        $carrier = Carrier::factory()->create();

        $bankingData = [
            'bank_name' => 'Test Bank',
            'account_holder_name' => $this->faker->name,
            'account_number' => $this->faker->bankAccountNumber,
            'routing_number' => $this->faker->numerify('#########'),
            'account_type' => 'checking',
        ];

        $updatedCarrier = $this->service->saveBankingDetails($carrier, $bankingData);

        $this->assertNotNull($updatedCarrier->bankingDetails);
        $this->assertEquals($bankingData['bank_name'], $updatedCarrier->bankingDetails->bank_name);
        $this->assertEquals(Carrier::STATUS_PENDING_VALIDATION, $updatedCarrier->status);
    }

    /**
     * Test que un carrier puede ser aprobado
     */
    public function test_carrier_can_be_approved(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('superadmin');
        $this->actingAs($admin);

        $carrier = Carrier::factory()->create([
            'status' => Carrier::STATUS_PENDING_VALIDATION,
        ]);

        $approvedCarrier = $this->service->approveCarrier($carrier);

        $this->assertEquals(Carrier::STATUS_ACTIVE, $approvedCarrier->status);
    }

    /**
     * Test que un carrier puede ser rechazado
     */
    public function test_carrier_can_be_rejected(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('superadmin');
        $this->actingAs($admin);

        $carrier = Carrier::factory()->create([
            'status' => Carrier::STATUS_PENDING_VALIDATION,
        ]);

        $reason = 'Invalid documentation';
        $rejectedCarrier = $this->service->rejectCarrier($carrier, $reason);

        $this->assertEquals(Carrier::STATUS_REJECTED, $rejectedCarrier->status);
    }

    /**
     * Test que verifica si el carrier puede agregar conductores
     */
    public function test_checks_if_carrier_can_add_drivers(): void
    {
        $membership = Membership::factory()->create(['max_drivers' => 5]);
        $carrier = Carrier::factory()->create(['id_plan' => $membership->id]);

        $this->assertTrue($this->service->canAddDriver($carrier));

        // Crear 5 drivers (límite)
        \App\Models\UserDriverDetail::factory()->count(5)->create([
            'carrier_id' => $carrier->id,
        ]);

        $this->assertFalse($this->service->canAddDriver($carrier));
    }

    /**
     * Test que verifica si el carrier puede agregar vehículos
     */
    public function test_checks_if_carrier_can_add_vehicles(): void
    {
        $membership = Membership::factory()->create(['max_vehicles' => 3]);
        $carrier = Carrier::factory()->create(['id_plan' => $membership->id]);

        $this->assertTrue($this->service->canAddVehicle($carrier));

        // Crear 3 vehículos (límite)
        \App\Models\Admin\Vehicle\Vehicle::factory()->count(3)->create([
            'carrier_id' => $carrier->id,
        ]);

        $this->assertFalse($this->service->canAddVehicle($carrier));
    }

    /**
     * Test que obtiene los límites disponibles del carrier
     */
    public function test_gets_available_limits_for_carrier(): void
    {
        $membership = Membership::factory()->create([
            'max_drivers' => 10,
            'max_vehicles' => 5,
        ]);
        $carrier = Carrier::factory()->create(['id_plan' => $membership->id]);

        // Crear algunos drivers y vehículos
        \App\Models\UserDriverDetail::factory()->count(3)->create([
            'carrier_id' => $carrier->id,
        ]);
        \App\Models\Admin\Vehicle\Vehicle::factory()->count(2)->create([
            'carrier_id' => $carrier->id,
        ]);

        $limits = $this->service->getAvailableLimits($carrier);

        $this->assertEquals(3, $limits['drivers']['current']);
        $this->assertEquals(10, $limits['drivers']['max']);
        $this->assertEquals(7, $limits['drivers']['available']);
        $this->assertTrue($limits['drivers']['can_add']);

        $this->assertEquals(2, $limits['vehicles']['current']);
        $this->assertEquals(5, $limits['vehicles']['max']);
        $this->assertEquals(3, $limits['vehicles']['available']);
        $this->assertTrue($limits['vehicles']['can_add']);
    }

    /**
     * Test que retorna límites vacíos si no hay membresía
     */
    public function test_returns_empty_limits_without_membership(): void
    {
        $carrier = Carrier::factory()->create(['id_plan' => null]);

        $limits = $this->service->getAvailableLimits($carrier);

        $this->assertEquals(0, $limits['drivers']['max']);
        $this->assertEquals(0, $limits['vehicles']['max']);
    }

    /**
     * Test que el referrer token tiene fecha de expiración
     */
    public function test_referrer_token_has_expiration_date(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user_carrier');

        $companyData = [
            'name' => $this->faker->company,
            'address' => $this->faker->streetAddress,
            'state' => 'CA',
            'zipcode' => '90001',
            'dot_number' => $this->faker->numerify('######'),
            'ein_number' => $this->faker->numerify('##-#######'),
            'business_type' => 'LLC',
            'years_in_business' => 5,
            'fleet_size' => 10,
        ];

        $carrier = $this->service->createCarrierCompany($user, $companyData);

        $this->assertNotNull($carrier->referrer_token_expires_at);
        $this->assertTrue($carrier->referrer_token_expires_at->isFuture());
    }
}
