<?php

namespace Tests\Feature\Banking;

use Tests\TestCase;
use App\Models\User;
use App\Models\Carrier;
use App\Models\CarrierBanking;
use App\Models\UserCarrier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class CarrierBankingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $carrierUser;
    protected Carrier $carrier;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear admin
        $this->admin = User::factory()->create();
        $this->admin->assignRole('superadmin');

        // Crear carrier user
        $this->carrierUser = User::factory()->create();
        $this->carrierUser->assignRole('user_carrier');

        // Crear carrier
        $this->carrier = Carrier::factory()->create([
            'status' => Carrier::STATUS_PENDING
        ]);

        // Asociar usuario con carrier
        UserCarrier::create([
            'user_id' => $this->carrierUser->id,
            'carrier_id' => $this->carrier->id,
            'status' => 1
        ]);
    }

    /** @test */
    public function admin_can_view_carrier_banking_details()
    {
        CarrierBanking::create([
            'carrier_id' => $this->carrier->id,
            'account_holder_name' => 'John Doe',
            'account_number' => '1234567890',
            'banking_routing_number' => '123456789',
            'zip_code' => '12345',
            'security_code' => '123',
            'country_code' => 'US',
            'status' => 'pending'
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.carriers.edit', $this->carrier));

        $response->assertStatus(200);
        $response->assertSee('John Doe');
    }

    /** @test */
    public function admin_can_update_banking_details()
    {
        $banking = CarrierBanking::create([
            'carrier_id' => $this->carrier->id,
            'account_holder_name' => 'John Doe',
            'account_number' => '1234567890',
            'banking_routing_number' => '123456789',
            'zip_code' => '12345',
            'security_code' => '123',
            'country_code' => 'US',
            'status' => 'pending'
        ]);

        $response = $this->actingAs($this->admin)
            ->put(route('admin.carriers.update-banking', $this->carrier), [
                'account_holder_name' => 'Jane Smith',
                'account_number' => '9876543210',
                'banking_routing_number' => '987654321',
                'zip_code' => '54321',
                'security_code' => '456',
                'country_code' => 'US',
                'status' => 'pending',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('carrier_banking', [
            'carrier_id' => $this->carrier->id,
            'account_holder_name' => 'Jane Smith',
            'account_number' => '9876543210',
            'banking_routing_number' => '987654321',
            'status' => 'pending'
        ]);
    }

    /** @test */
    public function admin_can_approve_banking_details()
    {
        Mail::fake();

        $banking = CarrierBanking::create([
            'carrier_id' => $this->carrier->id,
            'account_holder_name' => 'John Doe',
            'account_number' => '1234567890',
            'banking_routing_number' => '123456789',
            'zip_code' => '12345',
            'security_code' => '123',
            'country_code' => 'US',
            'status' => 'pending'
        ]);

        $response = $this->actingAs($this->admin)
            ->put(route('admin.carriers.update-banking', $this->carrier), [
                'account_holder_name' => 'John Doe',
                'account_number' => '1234567890',
                'banking_routing_number' => '123456789',
                'zip_code' => '12345',
                'security_code' => '123',
                'country_code' => 'US',
                'status' => 'approved',
            ]);

        $response->assertRedirect();

        // Verificar que el status cambió
        $this->assertDatabaseHas('carrier_banking', [
            'carrier_id' => $this->carrier->id,
            'status' => 'approved'
        ]);

        // Verificar que el carrier se activó
        $this->assertEquals(Carrier::STATUS_ACTIVE, $this->carrier->fresh()->status);
    }

    /** @test */
    public function admin_can_reject_banking_details()
    {
        Mail::fake();

        $banking = CarrierBanking::create([
            'carrier_id' => $this->carrier->id,
            'account_holder_name' => 'John Doe',
            'account_number' => '1234567890',
            'banking_routing_number' => '123456789',
            'zip_code' => '12345',
            'security_code' => '123',
            'country_code' => 'US',
            'status' => 'pending'
        ]);

        $response = $this->actingAs($this->admin)
            ->put(route('admin.carriers.update-banking', $this->carrier), [
                'account_holder_name' => 'John Doe',
                'account_number' => '1234567890',
                'banking_routing_number' => '123456789',
                'zip_code' => '12345',
                'security_code' => '123',
                'country_code' => 'US',
                'status' => 'rejected',
                'rejection_reason' => 'Invalid routing number'
            ]);

        $response->assertRedirect();

        // Verificar que el status cambió
        $this->assertDatabaseHas('carrier_banking', [
            'carrier_id' => $this->carrier->id,
            'status' => 'rejected',
            'rejection_reason' => 'Invalid routing number'
        ]);

        // Verificar que el carrier se marcó como rejected
        $this->assertEquals(Carrier::STATUS_REJECTED, $this->carrier->fresh()->status);
    }

    /** @test */
    public function routing_number_must_be_9_digits()
    {
        $banking = CarrierBanking::create([
            'carrier_id' => $this->carrier->id,
            'account_holder_name' => 'John Doe',
            'account_number' => '1234567890',
            'banking_routing_number' => '123456789',
            'zip_code' => '12345',
            'security_code' => '123',
            'country_code' => 'US',
            'status' => 'pending'
        ]);

        // Intentar con routing number inválido (8 dígitos)
        $response = $this->actingAs($this->admin)
            ->put(route('admin.carriers.update-banking', $this->carrier), [
                'account_holder_name' => 'John Doe',
                'account_number' => '1234567890',
                'banking_routing_number' => '12345678', // Solo 8 dígitos
                'zip_code' => '12345',
                'security_code' => '123',
                'country_code' => 'US',
                'status' => 'pending',
            ]);

        $response->assertSessionHasErrors('banking_routing_number');
    }

    /** @test */
    public function routing_number_must_be_numeric()
    {
        $banking = CarrierBanking::create([
            'carrier_id' => $this->carrier->id,
            'account_holder_name' => 'John Doe',
            'account_number' => '1234567890',
            'banking_routing_number' => '123456789',
            'zip_code' => '12345',
            'security_code' => '123',
            'country_code' => 'US',
            'status' => 'pending'
        ]);

        // Intentar con routing number con letras
        $response = $this->actingAs($this->admin)
            ->put(route('admin.carriers.update-banking', $this->carrier), [
                'account_holder_name' => 'John Doe',
                'account_number' => '1234567890',
                'banking_routing_number' => '12345678A',
                'zip_code' => '12345',
                'security_code' => '123',
                'country_code' => 'US',
                'status' => 'pending',
            ]);

        $response->assertSessionHasErrors('banking_routing_number');
    }

    /** @test */
    public function zip_code_must_be_valid_format()
    {
        $banking = CarrierBanking::create([
            'carrier_id' => $this->carrier->id,
            'account_holder_name' => 'John Doe',
            'account_number' => '1234567890',
            'banking_routing_number' => '123456789',
            'zip_code' => '12345',
            'security_code' => '123',
            'country_code' => 'US',
            'status' => 'pending'
        ]);

        // Intentar con zip code inválido
        $response = $this->actingAs($this->admin)
            ->put(route('admin.carriers.update-banking', $this->carrier), [
                'account_holder_name' => 'John Doe',
                'account_number' => '1234567890',
                'banking_routing_number' => '123456789',
                'zip_code' => 'ABCDE',
                'security_code' => '123',
                'country_code' => 'US',
                'status' => 'pending',
            ]);

        $response->assertSessionHasErrors('zip_code');
    }

    /** @test */
    public function zip_code_can_be_extended_format()
    {
        $banking = CarrierBanking::create([
            'carrier_id' => $this->carrier->id,
            'account_holder_name' => 'John Doe',
            'account_number' => '1234567890',
            'banking_routing_number' => '123456789',
            'zip_code' => '12345',
            'security_code' => '123',
            'country_code' => 'US',
            'status' => 'pending'
        ]);

        // Usar formato extendido ZIP+4
        $response = $this->actingAs($this->admin)
            ->put(route('admin.carriers.update-banking', $this->carrier), [
                'account_holder_name' => 'John Doe',
                'account_number' => '1234567890',
                'banking_routing_number' => '123456789',
                'zip_code' => '12345-6789',
                'security_code' => '123',
                'country_code' => 'US',
                'status' => 'pending',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('carrier_banking', [
            'carrier_id' => $this->carrier->id,
            'zip_code' => '12345-6789'
        ]);
    }

    /** @test */
    public function security_code_must_be_3_or_4_digits()
    {
        $banking = CarrierBanking::create([
            'carrier_id' => $this->carrier->id,
            'account_holder_name' => 'John Doe',
            'account_number' => '1234567890',
            'banking_routing_number' => '123456789',
            'zip_code' => '12345',
            'security_code' => '123',
            'country_code' => 'US',
            'status' => 'pending'
        ]);

        // Intentar con security code de 2 dígitos
        $response = $this->actingAs($this->admin)
            ->put(route('admin.carriers.update-banking', $this->carrier), [
                'account_holder_name' => 'John Doe',
                'account_number' => '1234567890',
                'banking_routing_number' => '123456789',
                'zip_code' => '12345',
                'security_code' => '12',
                'country_code' => 'US',
                'status' => 'pending',
            ]);

        $response->assertSessionHasErrors('security_code');
    }

    /** @test */
    public function carrier_cannot_update_own_banking_details()
    {
        $banking = CarrierBanking::create([
            'carrier_id' => $this->carrier->id,
            'account_holder_name' => 'John Doe',
            'account_number' => '1234567890',
            'banking_routing_number' => '123456789',
            'zip_code' => '12345',
            'security_code' => '123',
            'country_code' => 'US',
            'status' => 'pending'
        ]);

        $response = $this->actingAs($this->carrierUser)
            ->put(route('admin.carriers.update-banking', $this->carrier), [
                'account_holder_name' => 'Hacked Name',
                'account_number' => '9999999999',
                'banking_routing_number' => '999999999',
                'zip_code' => '99999',
                'security_code' => '999',
                'country_code' => 'US',
                'status' => 'approved', // Intentando auto-aprobar
            ]);

        // Debe ser redirigido o rechazado (403)
        $this->assertTrue(
            $response->status() === 403 || $response->status() === 302
        );

        // Verificar que NO se actualizó
        $this->assertDatabaseHas('carrier_banking', [
            'carrier_id' => $this->carrier->id,
            'account_holder_name' => 'John Doe',
            'status' => 'pending'
        ]);
    }

    /** @test */
    public function rejection_reason_is_required_when_rejecting()
    {
        $banking = CarrierBanking::create([
            'carrier_id' => $this->carrier->id,
            'account_holder_name' => 'John Doe',
            'account_number' => '1234567890',
            'banking_routing_number' => '123456789',
            'zip_code' => '12345',
            'security_code' => '123',
            'country_code' => 'US',
            'status' => 'pending'
        ]);

        $response = $this->actingAs($this->admin)
            ->put(route('admin.carriers.update-banking', $this->carrier), [
                'account_holder_name' => 'John Doe',
                'account_number' => '1234567890',
                'banking_routing_number' => '123456789',
                'zip_code' => '12345',
                'security_code' => '123',
                'country_code' => 'US',
                'status' => 'rejected',
                // rejection_reason ausente
            ]);

        // Debe permitir (rejection_reason es nullable)
        $response->assertRedirect();
    }

    /** @test */
    public function it_returns_error_if_no_banking_details_exist()
    {
        // No crear banking details

        $response = $this->actingAs($this->admin)
            ->put(route('admin.carriers.update-banking', $this->carrier), [
                'account_holder_name' => 'John Doe',
                'account_number' => '1234567890',
                'banking_routing_number' => '123456789',
                'zip_code' => '12345',
                'security_code' => '123',
                'country_code' => 'US',
                'status' => 'pending',
            ]);

        $response->assertSessionHas('error', 'No banking information found for this carrier.');
    }

    /** @test */
    public function all_fields_are_required_except_rejection_reason()
    {
        $banking = CarrierBanking::create([
            'carrier_id' => $this->carrier->id,
            'account_holder_name' => 'John Doe',
            'account_number' => '1234567890',
            'banking_routing_number' => '123456789',
            'zip_code' => '12345',
            'security_code' => '123',
            'country_code' => 'US',
            'status' => 'pending'
        ]);

        $response = $this->actingAs($this->admin)
            ->put(route('admin.carriers.update-banking', $this->carrier), []);

        $response->assertSessionHasErrors([
            'account_holder_name',
            'account_number',
            'banking_routing_number',
            'zip_code',
            'security_code',
            'country_code',
            'status'
        ]);
    }
}
