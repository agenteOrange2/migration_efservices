<?php

namespace Tests\Unit\Events;

use Tests\TestCase;
use App\Models\Carrier;
use App\Models\UserDriverDetail;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Admin\Vehicle\VehicleDriverAssignment;
use App\Events\VehicleAssigned;
use App\Listeners\LogVehicleAssignment;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VehicleAssignedTest extends TestCase
{
    use RefreshDatabase;

    public function test_vehicle_assigned_event_can_be_created(): void
    {
        $assignment = VehicleDriverAssignment::factory()->create();

        $event = new VehicleAssigned($assignment);

        $this->assertInstanceOf(VehicleAssigned::class, $event);
        $this->assertEquals($assignment->id, $event->assignment->id);
    }

    public function test_vehicle_assigned_event_is_dispatched(): void
    {
        Event::fake();

        $assignment = VehicleDriverAssignment::factory()->create();

        event(new VehicleAssigned($assignment));

        Event::assertDispatched(VehicleAssigned::class, function ($event) use ($assignment) {
            return $event->assignment->id === $assignment->id;
        });
    }

    public function test_vehicle_assigned_listener_logs_assignment(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Vehicle assigned to driver', \Mockery::on(function ($context) {
                return isset($context['assignment_id']) &&
                       isset($context['vehicle_id']) &&
                       isset($context['driver_id']) &&
                       isset($context['assignment_type']);
            }));

        $carrier = Carrier::factory()->create();
        $vehicle = Vehicle::factory()->create(['carrier_id' => $carrier->id]);
        $driver = UserDriverDetail::factory()->create(['carrier_id' => $carrier->id]);
        
        $assignment = VehicleDriverAssignment::factory()->create([
            'vehicle_id' => $vehicle->id,
            'user_driver_detail_id' => $driver->id,
        ]);

        $event = new VehicleAssigned($assignment);
        $listener = new LogVehicleAssignment();

        $listener->handle($event);
    }

    public function test_vehicle_assigned_listener_is_registered(): void
    {
        Event::fake();
        Event::assertListening(
            VehicleAssigned::class,
            LogVehicleAssignment::class
        );
    }
}
