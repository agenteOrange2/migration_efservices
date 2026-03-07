<?php

namespace Tests\Unit\Policies;

use Tests\TestCase;
use App\Models\User;
use App\Models\Carrier;
use App\Models\AdminMessage;
use App\Models\UserDriverDetail;
use App\Policies\AdminMessagePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminMessagePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected AdminMessagePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new AdminMessagePolicy();
    }

    /** @test */
    public function superadmin_can_view_any_message()
    {
        $user = User::factory()->create();
        $user->assignRole('superadmin');

        $message = AdminMessage::factory()->create();

        $this->assertTrue($this->policy->view($user, $message));
    }

    /** @test */
    public function carrier_can_view_own_sent_messages()
    {
        $user = User::factory()->create();
        $user->assignRole('user_carrier');

        $carrier = Carrier::factory()->create();
        $user->carrierDetails()->create([
            'carrier_id' => $carrier->id,
            'status' => 1,
        ]);

        $message = AdminMessage::factory()->create([
            'sender_type' => 'App\\Models\\Carrier',
            'sender_id' => $carrier->id,
        ]);

        $this->assertTrue($this->policy->view($user, $message));
    }

    /** @test */
    public function carrier_can_view_messages_sent_to_them()
    {
        $user = User::factory()->create();
        $user->assignRole('user_carrier');

        $carrier = Carrier::factory()->create();
        $user->carrierDetails()->create([
            'carrier_id' => $carrier->id,
            'status' => 1,
        ]);

        $message = AdminMessage::factory()->create();
        $message->recipients()->create([
            'recipient_type' => 'carrier',
            'recipient_id' => $carrier->id,
        ]);

        $this->assertTrue($this->policy->view($user, $message));
    }

    /** @test */
    public function carrier_cannot_view_messages_not_related_to_them()
    {
        $user = User::factory()->create();
        $user->assignRole('user_carrier');

        $carrier = Carrier::factory()->create();
        $user->carrierDetails()->create([
            'carrier_id' => $carrier->id,
            'status' => 1,
        ]);

        $otherCarrier = Carrier::factory()->create();
        $message = AdminMessage::factory()->create([
            'sender_type' => 'App\\Models\\Carrier',
            'sender_id' => $otherCarrier->id,
        ]);

        $this->assertFalse($this->policy->view($user, $message));
    }

    /** @test */
    public function driver_can_view_own_sent_messages()
    {
        $user = User::factory()->create();
        $user->assignRole('user_driver');

        $driverDetail = $user->driverDetails()->create([
            'user_id' => $user->id,
            'status' => 1,
        ]);

        $message = AdminMessage::factory()->create([
            'sender_type' => 'App\\Models\\UserDriverDetail',
            'sender_id' => $driverDetail->id,
        ]);

        $this->assertTrue($this->policy->view($user, $message));
    }

    /** @test */
    public function driver_can_view_messages_sent_to_them()
    {
        $user = User::factory()->create();
        $user->assignRole('user_driver');

        $driverDetail = $user->driverDetails()->create([
            'user_id' => $user->id,
            'status' => 1,
        ]);

        $message = AdminMessage::factory()->create();
        $message->recipients()->create([
            'recipient_type' => 'driver',
            'recipient_id' => $driverDetail->id,
        ]);

        $this->assertTrue($this->policy->view($user, $message));
    }

    /** @test */
    public function driver_cannot_view_messages_not_related_to_them()
    {
        $user = User::factory()->create();
        $user->assignRole('user_driver');

        $driverDetail = $user->driverDetails()->create([
            'user_id' => $user->id,
            'status' => 1,
        ]);

        $otherDriver = UserDriverDetail::factory()->create();
        $message = AdminMessage::factory()->create([
            'sender_type' => 'App\\Models\\UserDriverDetail',
            'sender_id' => $otherDriver->id,
        ]);

        $this->assertFalse($this->policy->view($user, $message));
    }
}
