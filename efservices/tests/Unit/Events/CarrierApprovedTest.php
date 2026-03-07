<?php

namespace Tests\Unit\Events;

use Tests\TestCase;
use App\Models\User;
use App\Models\Carrier;
use App\Events\CarrierApproved;
use App\Listeners\SendCarrierApprovalNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CarrierApprovedTest extends TestCase
{
    use RefreshDatabase;

    public function test_carrier_approved_event_can_be_created(): void
    {
        $carrier = Carrier::factory()->create();
        $approvedBy = 1;

        $event = new CarrierApproved($carrier, $approvedBy);

        $this->assertInstanceOf(CarrierApproved::class, $event);
        $this->assertEquals($carrier->id, $event->carrier->id);
        $this->assertEquals($approvedBy, $event->approvedBy);
    }

    public function test_carrier_approved_event_is_dispatched(): void
    {
        Event::fake();

        $carrier = Carrier::factory()->create();
        $approvedBy = 1;

        event(new CarrierApproved($carrier, $approvedBy));

        Event::assertDispatched(CarrierApproved::class, function ($event) use ($carrier, $approvedBy) {
            return $event->carrier->id === $carrier->id &&
                   $event->approvedBy === $approvedBy;
        });
    }

    public function test_carrier_approved_listener_logs_approval(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Carrier approved', \Mockery::on(function ($context) {
                return isset($context['carrier_id']) &&
                       isset($context['carrier_name']) &&
                       isset($context['approved_by']);
            }));

        $carrier = Carrier::factory()->create();
        $event = new CarrierApproved($carrier, 1);
        $listener = new SendCarrierApprovalNotification();

        $listener->handle($event);
    }

    public function test_carrier_approved_listener_is_registered(): void
    {
        Event::fake();
        Event::assertListening(
            CarrierApproved::class,
            SendCarrierApprovalNotification::class
        );
    }
}
