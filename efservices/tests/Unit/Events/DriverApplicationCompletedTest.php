<?php

namespace Tests\Unit\Events;

use Tests\TestCase;
use App\Models\Carrier;
use App\Models\UserDriverDetail;
use App\Events\DriverApplicationCompleted;
use App\Listeners\NotifyCarrierOfCompletedApplication;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DriverApplicationCompletedTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_application_completed_event_can_be_created(): void
    {
        $driver = UserDriverDetail::factory()->create();

        $event = new DriverApplicationCompleted($driver);

        $this->assertInstanceOf(DriverApplicationCompleted::class, $event);
        $this->assertEquals($driver->id, $event->driver->id);
    }

    public function test_driver_application_completed_event_is_dispatched(): void
    {
        Event::fake();

        $driver = UserDriverDetail::factory()->create();

        event(new DriverApplicationCompleted($driver));

        Event::assertDispatched(DriverApplicationCompleted::class, function ($event) use ($driver) {
            return $event->driver->id === $driver->id;
        });
    }

    public function test_driver_application_completed_listener_logs_event(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Driver application completed', \Mockery::on(function ($context) {
                return isset($context['driver_id']) &&
                       isset($context['driver_name']) &&
                       isset($context['carrier_id']) &&
                       isset($context['carrier_name']);
            }));

        $carrier = Carrier::factory()->create();
        $driver = UserDriverDetail::factory()->create(['carrier_id' => $carrier->id]);
        $event = new DriverApplicationCompleted($driver);
        $listener = new NotifyCarrierOfCompletedApplication();

        $listener->handle($event);
    }

    public function test_driver_application_completed_listener_is_registered(): void
    {
        Event::fake();
        Event::assertListening(
            DriverApplicationCompleted::class,
            NotifyCarrierOfCompletedApplication::class
        );
    }
}
