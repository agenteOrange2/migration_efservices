<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Carrier;
use App\Models\Membership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class CarrierRegistrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test que un carrier puede acceder al formulario de registro
     */
    public function test_carrier_can_access_registration_form(): void
    {
        $response = $this->get(route('carrier.wizard.step1'));

        $response->assertStatus(200);
        $response->assertViewIs('auth.user_carrier.wizard.step1');
    }

    /**
     * Test que un carrier puede registrarse con datos válidos
     */
    public function test_carrier_can_register_with_valid_data(): void
    {
        $userData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => true,
        ];

        $response = $this->post(route('carrier.wizard.step1.process'), $userData);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => $userData['email'],
        ]);
    }

    /**
     * Test que el registro falla con email duplicado
     */
    public function test_carrier_registration_fails_with_duplicate_email(): void
    {
        $existingUser = User::factory()->create();

        $userData = [
            'name' => $this->faker->name,
            'email' => $existingUser->email,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => true,
        ];

        $response = $this->post(route('carrier.wizard.step1.process'), $userData);

        $response->assertSessionHasErrors('email');
    }

    /**
     * Test que el registro falla sin aceptar términos
     */
    public function test_carrier_registration_fails_without_terms(): void
    {
        $userData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => false,
        ];

        $response = $this->post(route('carrier.wizard.step1.process'), $userData);

        $response->assertSessionHasErrors('terms');
    }

    /**
     * Test que un carrier autenticado puede acceder al paso 2
     */
    public function test_authenticated_carrier_can_access_step_2(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user_carrier');

        $response = $this->actingAs($user)->get(route('carrier.wizard.step2'));

        $response->assertStatus(200);
    }

    /**
     * Test que un carrier puede completar el paso 2 con información de empresa
     */
    public function test_carrier_can_complete_step_2_with_company_info(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user_carrier');

        $carrierData = [
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

        $response = $this->actingAs($user)->post(route('carrier.wizard.step2.process'), $carrierData);

        $response->assertRedirect();
        $this->assertDatabaseHas('carriers', [
            'name' => $carrierData['name'],
            'dot_number' => $carrierData['dot_number'],
        ]);
    }

    /**
     * Test que un carrier puede seleccionar una membresía en el paso 3
     */
    public function test_carrier_can_select_membership_in_step_3(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user_carrier');
        
        $carrier = Carrier::factory()->create(['user_id' => $user->id]);
        $membership = Membership::factory()->create();

        $response = $this->actingAs($user)->post(route('carrier.wizard.step3.process'), [
            'id_plan' => $membership->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('carriers', [
            'id' => $carrier->id,
            'id_plan' => $membership->id,
        ]);
    }

    /**
     * Test que el wizard redirige correctamente entre pasos
     */
    public function test_wizard_redirects_correctly_between_steps(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user_carrier');

        // Sin carrier, debe redirigir a step2
        $response = $this->actingAs($user)->get(route('carrier.wizard.step3'));
        $response->assertRedirect(route('carrier.wizard.step2'));
    }

    /**
     * Test que un carrier no puede acceder a pasos sin completar los anteriores
     */
    public function test_carrier_cannot_skip_wizard_steps(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user_carrier');

        // Intentar acceder a step 4 sin completar pasos anteriores
        $response = $this->actingAs($user)->get(route('carrier.wizard.step4'));
        
        $response->assertRedirect();
    }

    /**
     * Test que un carrier con status inactivo no puede acceder al dashboard
     */
    public function test_inactive_carrier_cannot_access_dashboard(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user_carrier');
        
        $carrier = Carrier::factory()->create([
            'user_id' => $user->id,
            'status' => Carrier::STATUS_INACTIVE,
        ]);

        $response = $this->actingAs($user)->get(route('carrier.dashboard'));
        
        $response->assertRedirect();
    }

    /**
     * Test que un carrier activo puede acceder al dashboard
     */
    public function test_active_carrier_can_access_dashboard(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user_carrier');
        
        $carrier = Carrier::factory()->create([
            'user_id' => $user->id,
            'status' => Carrier::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($user)->get(route('carrier.dashboard'));
        
        $response->assertStatus(200);
    }
}
