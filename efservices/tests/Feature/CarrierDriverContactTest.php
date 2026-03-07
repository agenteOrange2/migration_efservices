<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Carrier;
use App\Models\Membership;
use App\Models\UserCarrierDetail;
use App\Models\UserDriverDetail;
use App\Models\AdminMessage;
use App\Models\MessageRecipient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Mail;

class CarrierDriverContactTest extends TestCase
{
    use RefreshDatabase, WithFaker;

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

        // Create carrier with active status
        $this->carrier = Carrier::factory()->create([
            'membership_id' => $membership->id,
            'status' => 'active',
            'documents_completed' => true,
            'document_status' => 'approved',
        ]);

        // Create approved banking details for the carrier
        \App\Models\CarrierBankingDetail::create([
            'carrier_id' => $this->carrier->id,
            'account_holder_name' => 'Test Holder',
            'account_number' => '1234567890',
            'banking_routing_number' => '123456789',
            'status' => 'approved',
        ]);

        // Create carrier user
        $this->carrierUser = User::factory()->create([
            'status' => 1, // Active status
        ]);
        $this->carrierUser->assignRole('user_carrier');

        UserCarrierDetail::factory()->create([
            'user_id' => $this->carrierUser->id,
            'carrier_id' => $this->carrier->id,
            'status' => 1, // Active status
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
    public function carrier_can_access_contact_form()
    {
        $response = $this->actingAs($this->carrierUser)
            ->get(route('carrier.driver-vehicle-management.contact', $this->driver->id));

        $response->assertStatus(200);
        $response->assertSee('Contact Driver');
        $response->assertSee($this->driver->user->name);
        $response->assertSee($this->driver->user->email);
    }

    /** @test */
    public function carrier_cannot_access_contact_form_for_driver_from_different_carrier()
    {
        // Create another carrier
        $otherMembership = Membership::factory()->create([
            'name' => 'Premium',
        ]);
        $otherCarrier = Carrier::factory()->create([
            'membership_id' => $otherMembership->id,
            'status' => 'active',
            'documents_completed' => true,
        ]);

        // Create driver for other carrier
        $otherDriverUser = User::factory()->create();
        $otherDriverUser->assignRole('user_driver');

        $otherDriver = UserDriverDetail::factory()->create([
            'user_id' => $otherDriverUser->id,
            'carrier_id' => $otherCarrier->id,
            'status' => 1,
        ]);

        $response = $this->actingAs($this->carrierUser)
            ->get(route('carrier.driver-vehicle-management.contact', $otherDriver->id));

        $response->assertStatus(403);
    }

    /** @test */
    public function carrier_can_send_contact_message_to_driver()
    {
        Mail::fake();

        $messageData = [
            'subject' => 'Test Subject',
            'message' => 'This is a test message to the driver.',
            'priority' => 'normal',
        ];

        $response = $this->actingAs($this->carrierUser)
            ->post(route('carrier.driver-vehicle-management.send-contact', $this->driver->id), $messageData);

        $response->assertRedirect(route('carrier.driver-vehicle-management.show', $this->driver->id));
        $response->assertSessionHas('success', 'Message sent successfully to driver.');

        // Assert AdminMessage was created
        $this->assertDatabaseHas('admin_messages', [
            'sender_id' => $this->carrierUser->id,
            'subject' => 'Test Subject',
            'message' => 'This is a test message to the driver.',
            'priority' => 'normal',
            'status' => 'sent',
        ]);

        // Assert MessageRecipient was created
        $adminMessage = AdminMessage::where('subject', 'Test Subject')->first();
        $this->assertDatabaseHas('message_recipients', [
            'message_id' => $adminMessage->id,
            'recipient_type' => 'App\Models\UserDriverDetail',
            'recipient_id' => $this->driver->id,
            'email' => $this->driver->user->email,
            'delivery_status' => 'delivered',
        ]);

        // Assert email was sent
        Mail::assertSent(\App\Mail\DriverContactMail::class, function ($mail) {
            return $mail->hasTo($this->driver->user->email);
        });
    }

    /** @test */
    public function contact_message_requires_subject()
    {
        $messageData = [
            'message' => 'This is a test message.',
            'priority' => 'normal',
        ];

        $response = $this->actingAs($this->carrierUser)
            ->post(route('carrier.driver-vehicle-management.send-contact', $this->driver->id), $messageData);

        $response->assertSessionHasErrors('subject');
    }

    /** @test */
    public function contact_message_requires_message()
    {
        $messageData = [
            'subject' => 'Test Subject',
            'priority' => 'normal',
        ];

        $response = $this->actingAs($this->carrierUser)
            ->post(route('carrier.driver-vehicle-management.send-contact', $this->driver->id), $messageData);

        $response->assertSessionHasErrors('message');
    }

    /** @test */
    public function contact_message_requires_priority()
    {
        $messageData = [
            'subject' => 'Test Subject',
            'message' => 'This is a test message.',
        ];

        $response = $this->actingAs($this->carrierUser)
            ->post(route('carrier.driver-vehicle-management.send-contact', $this->driver->id), $messageData);

        $response->assertSessionHasErrors('priority');
    }

    /** @test */
    public function contact_message_priority_must_be_valid()
    {
        $messageData = [
            'subject' => 'Test Subject',
            'message' => 'This is a test message.',
            'priority' => 'invalid_priority',
        ];

        $response = $this->actingAs($this->carrierUser)
            ->post(route('carrier.driver-vehicle-management.send-contact', $this->driver->id), $messageData);

        $response->assertSessionHasErrors('priority');
    }

    /** @test */
    public function carrier_cannot_send_contact_message_to_driver_from_different_carrier()
    {
        // Create another carrier
        $otherMembership = Membership::factory()->create([
            'name' => 'Premium',
        ]);
        $otherCarrier = Carrier::factory()->create([
            'membership_id' => $otherMembership->id,
            'status' => 'active',
            'documents_completed' => true,
        ]);

        // Create driver for other carrier
        $otherDriverUser = User::factory()->create();
        $otherDriverUser->assignRole('user_driver');

        $otherDriver = UserDriverDetail::factory()->create([
            'user_id' => $otherDriverUser->id,
            'carrier_id' => $otherCarrier->id,
            'status' => 1,
        ]);

        $messageData = [
            'subject' => 'Test Subject',
            'message' => 'This is a test message.',
            'priority' => 'normal',
        ];

        $response = $this->actingAs($this->carrierUser)
            ->post(route('carrier.driver-vehicle-management.send-contact', $otherDriver->id), $messageData);

        $response->assertStatus(403);
    }
}
