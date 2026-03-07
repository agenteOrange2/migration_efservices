<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Trip;
use App\Models\Carrier;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\UserDriverDetail;
use App\Services\Trip\TripService;
use App\Services\Hos\HosFMCSAService;
use App\Services\Hos\HosWeeklyCycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;

class TripServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TripService $tripService;
    protected HosFMCSAService $fmcsaService;
    protected HosWeeklyCycleService $weeklyCycleService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fmcsaService = $this->createMock(HosFMCSAService::class);
        $this->weeklyCycleService = $this->createMock(HosWeeklyCycleService::class);
        $this->tripService = new TripService($this->fmcsaService, $this->weeklyCycleService);
    }

    #[Test]
    public function it_can_create_a_trip_successfully()
    {
        $carrier = Carrier::factory()->create();
        $driver = UserDriverDetail::factory()->create(['carrier_id' => $carrier->id]);
        $vehicle = Vehicle::factory()->create(['carrier_id' => $carrier->id]);

        $tripData = [
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'origin_address' => '123 Start St, City, State',
            'destination_address' => '456 End Ave, City, State',
            'scheduled_start_date' => now()->addDay(),
            'estimated_duration_minutes' => 120,
            'description' => 'Test trip',
        ];

        $trip = $this->tripService->createTrip($carrier->id, $tripData);

        $this->assertNotNull($trip);
        $this->assertEquals($carrier->id, $trip->carrier_id);
        $this->assertEquals($driver->id, $trip->user_driver_detail_id);
        $this->assertEquals($vehicle->id, $trip->vehicle_id);
        $this->assertEquals(Trip::STATUS_PENDING, $trip->status);
        $this->assertEquals(120, $trip->estimated_duration_minutes);
    }

    #[Test]
    public function it_assigns_driver_to_trip_when_driver_has_available_hours()
    {
        $carrier = Carrier::factory()->create();
        $originalDriver = UserDriverDetail::factory()->create(['carrier_id' => $carrier->id]);
        $newDriver = UserDriverDetail::factory()->create(['carrier_id' => $carrier->id]);
        $trip = Trip::factory()->create([
            'carrier_id' => $carrier->id,
            'user_driver_detail_id' => $originalDriver->id,
            'estimated_duration_minutes' => 120
        ]);

        $this->weeklyCycleService
            ->expects($this->once())
            ->method('getWeeklyCycleStatus')
            ->with($newDriver->id)
            ->willReturn([
                'is_over_limit' => false,
                'hours_remaining' => 10,
            ]);

        $this->fmcsaService
            ->expects($this->once())
            ->method('hasBlockingPenalty')
            ->with($newDriver->id)
            ->willReturn(['has_penalty' => false]);

        $result = $this->tripService->assignDriver($trip, $newDriver->id);

        $this->assertEquals($newDriver->id, $result->user_driver_detail_id);
    }

    #[Test]
    public function it_prevents_assigning_driver_who_exceeded_weekly_limit()
    {
        $carrier = Carrier::factory()->create();
        $driver = UserDriverDetail::factory()->create(['carrier_id' => $carrier->id]);
        $trip = Trip::factory()->create(['carrier_id' => $carrier->id]);

        $this->weeklyCycleService
            ->expects($this->once())
            ->method('getWeeklyCycleStatus')
            ->with($driver->id)
            ->willReturn([
                'is_over_limit' => true,
                'hours_remaining' => 0,
            ]);

        $this->expectException(ValidationException::class);
        $this->tripService->assignDriver($trip, $driver->id);
    }

    #[Test]
    public function it_prevents_assigning_driver_with_insufficient_hours()
    {
        $carrier = Carrier::factory()->create();
        $driver = UserDriverDetail::factory()->create(['carrier_id' => $carrier->id]);
        $trip = Trip::factory()->create([
            'carrier_id' => $carrier->id,
            'estimated_duration_minutes' => 360 // 6 hours
        ]);

        $this->weeklyCycleService
            ->expects($this->once())
            ->method('getWeeklyCycleStatus')
            ->with($driver->id)
            ->willReturn([
                'is_over_limit' => false,
                'hours_remaining' => 4, // Solo 4 horas disponibles
            ]);

        $this->expectException(ValidationException::class);

        $this->tripService->assignDriver($trip, $driver->id);
    }

    #[Test]
    public function it_prevents_assigning_driver_with_active_penalty()
    {
        $carrier = Carrier::factory()->create();
        $driver = UserDriverDetail::factory()->create(['carrier_id' => $carrier->id]);
        $trip = Trip::factory()->create(['carrier_id' => $carrier->id]);

        $this->weeklyCycleService
            ->expects($this->once())
            ->method('getWeeklyCycleStatus')
            ->with($driver->id)
            ->willReturn([
                'is_over_limit' => false,
                'hours_remaining' => 10,
            ]);

        $this->fmcsaService
            ->expects($this->once())
            ->method('hasBlockingPenalty')
            ->with($driver->id)
            ->willReturn([
                'has_penalty' => true,
                'penalty_type' => 'HOS Violation'
            ]);

        $this->expectException(ValidationException::class);

        $this->tripService->assignDriver($trip, $driver->id);
    }

    #[Test]
    public function driver_can_accept_assigned_trip()
    {
        $carrier = Carrier::factory()->create();
        $driver = UserDriverDetail::factory()->create(['carrier_id' => $carrier->id]);
        $trip = Trip::factory()->create([
            'carrier_id' => $carrier->id,
            'user_driver_detail_id' => $driver->id,
            'status' => Trip::STATUS_PENDING
        ]);

        $result = $this->tripService->acceptTrip($trip, $driver->id);

        $this->assertEquals(Trip::STATUS_ACCEPTED, $result->status);
        $this->assertNotNull($result->accepted_at);
    }

    #[Test]
    public function driver_cannot_accept_trip_assigned_to_another_driver()
    {
        $carrier = Carrier::factory()->create();
        $driver1 = UserDriverDetail::factory()->create(['carrier_id' => $carrier->id]);
        $driver2 = UserDriverDetail::factory()->create(['carrier_id' => $carrier->id]);

        $trip = Trip::factory()->create([
            'carrier_id' => $carrier->id,
            'user_driver_detail_id' => $driver1->id,
            'status' => Trip::STATUS_PENDING
        ]);

        $this->expectException(ValidationException::class);

        $this->tripService->acceptTrip($trip, $driver2->id);
    }

    #[Test]
    public function driver_cannot_accept_non_pending_trip()
    {
        $carrier = Carrier::factory()->create();
        $driver = UserDriverDetail::factory()->create(['carrier_id' => $carrier->id]);
        $trip = Trip::factory()->inProgress()->create([
            'carrier_id' => $carrier->id,
            'user_driver_detail_id' => $driver->id,
        ]);

        $this->expectException(ValidationException::class);

        $this->tripService->acceptTrip($trip, $driver->id);
    }

    #[Test]
    public function driver_can_reject_assigned_trip_with_reason()
    {
        $carrier = Carrier::factory()->create();
        $driver = UserDriverDetail::factory()->create(['carrier_id' => $carrier->id]);
        $trip = Trip::factory()->create([
            'carrier_id' => $carrier->id,
            'user_driver_detail_id' => $driver->id,
            'status' => Trip::STATUS_PENDING
        ]);

        $result = $this->tripService->rejectTrip($trip, $driver->id, 'Vehicle unavailable');

        $this->assertEquals(Trip::STATUS_CANCELLED, $result->status);
        $this->assertEquals('Vehicle unavailable', $result->cancellation_reason);
        $this->assertNotNull($result->cancelled_at);
    }

    #[Test]
    public function driver_cannot_reject_trip_assigned_to_another_driver()
    {
        $carrier = Carrier::factory()->create();
        $driver1 = UserDriverDetail::factory()->create(['carrier_id' => $carrier->id]);
        $driver2 = UserDriverDetail::factory()->create(['carrier_id' => $carrier->id]);

        $trip = Trip::factory()->create([
            'carrier_id' => $carrier->id,
            'user_driver_detail_id' => $driver1->id,
            'status' => Trip::STATUS_PENDING
        ]);

        $this->expectException(ValidationException::class);

        $this->tripService->rejectTrip($trip, $driver2->id, 'Test reason');
    }

    #[Test]
    public function it_can_start_a_trip_successfully()
    {
        // Arrange
        $carrier = Carrier::factory()->create();
        $driver = UserDriverDetail::factory()->create(['carrier_id' => $carrier->id]);
        $trip = Trip::factory()->accepted()->create([
            'carrier_id' => $carrier->id,
            'user_driver_detail_id' => $driver->id,
        ]);

        $this->fmcsaService
            ->expects($this->once())
            ->method('validateTripStart')
            ->willReturn(['valid' => true, 'errors' => []]);

        // Act
        $result = $this->tripService->startTrip($trip, $driver->id);

        // Assert
        $this->assertEquals(Trip::STATUS_IN_PROGRESS, $result->status);
        $this->assertNotNull($result->actual_start_time);
    }

    #[Test]
    public function it_prevents_starting_trip_with_fmcsa_violations()
    {
        // Arrange
        $carrier = Carrier::factory()->create();
        $driver = UserDriverDetail::factory()->create(['carrier_id' => $carrier->id]);
        $trip = Trip::factory()->accepted()->create([
            'carrier_id' => $carrier->id,
            'user_driver_detail_id' => $driver->id,
        ]);

        $this->fmcsaService
            ->expects($this->once())
            ->method('validateTripStart')
            ->willReturn([
                'valid' => false,
                'errors' => [
                    ['type' => 'driving_limit', 'message' => '11-hour driving limit exceeded']
                ]
            ]);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->tripService->startTrip($trip, $driver->id);
    }

    #[Test]
    public function it_prevents_starting_already_started_trip()
    {
        // Arrange
        $carrier = Carrier::factory()->create();
        $driver = UserDriverDetail::factory()->create(['carrier_id' => $carrier->id]);
        $trip = Trip::factory()->inProgress()->create([
            'carrier_id' => $carrier->id,
            'user_driver_detail_id' => $driver->id,
        ]);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->tripService->startTrip($trip, $driver->id);
    }

    #[Test]
    public function it_can_end_a_trip_successfully()
    {
        // Arrange
        $carrier = Carrier::factory()->create();
        $driver = UserDriverDetail::factory()->create(['carrier_id' => $carrier->id]);
        $trip = Trip::factory()->inProgress()->create([
            'carrier_id' => $carrier->id,
            'user_driver_detail_id' => $driver->id,
            'actual_start_time' => now()->subHours(5),
        ]);

        $postInspection = [
            ['item' => 'Tires', 'status' => 'pass', 'notes' => null],
            ['item' => 'Brakes', 'status' => 'pass', 'notes' => null],
        ];

        // Act
        $result = $this->tripService->endTrip($trip, $driver->id, $postInspection);

        // Assert
        $this->assertEquals(Trip::STATUS_COMPLETED, $result->status);
        $this->assertNotNull($result->actual_end_time);
    }

    #[Test]
    public function it_prevents_ending_trip_that_is_not_in_progress()
    {
        // Arrange
        $carrier = Carrier::factory()->create();
        $driver = UserDriverDetail::factory()->create(['carrier_id' => $carrier->id]);
        $trip = Trip::factory()->create([
            'carrier_id' => $carrier->id,
            'user_driver_detail_id' => $driver->id,
            'status' => Trip::STATUS_PENDING
        ]);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->tripService->endTrip($trip, $driver->id, []);
    }

    #[Test]
    public function it_validates_trip_inspection_data()
    {
        // Arrange
        $carrier = Carrier::factory()->create();
        $driver = UserDriverDetail::factory()->create(['carrier_id' => $carrier->id]);
        $trip = Trip::factory()->accepted()->create([
            'carrier_id' => $carrier->id,
            'user_driver_detail_id' => $driver->id,
        ]);

        $invalidInspection = [
            ['item' => '', 'status' => 'invalid'],  // Invalid data
        ];

        $this->fmcsaService
            ->method('validateTripStart')
            ->willReturn(['valid' => true, 'errors' => []]);

        // Act
        $result = $this->tripService->startTrip($trip, $driver->id, $invalidInspection);

        // Assert - Service should handle invalid inspection gracefully
        $this->assertNotNull($result);
        $this->assertEquals(Trip::STATUS_IN_PROGRESS, $result->status);
    }

    #[Test]
    public function it_creates_driving_entry_when_starting_trip()
    {
        // Arrange
        $carrier = Carrier::factory()->create();
        $driver = UserDriverDetail::factory()->create(['carrier_id' => $carrier->id]);
        $trip = Trip::factory()->accepted()->create([
            'carrier_id' => $carrier->id,
            'user_driver_detail_id' => $driver->id,
        ]);

        $this->fmcsaService
            ->method('validateTripStart')
            ->willReturn(['valid' => true, 'errors' => []]);

        // Act
        $result = $this->tripService->startTrip($trip, $driver->id);

        // Assert
        $this->assertDatabaseHas('hos_entries', [
            'user_driver_detail_id' => $driver->id,
            'trip_id' => $trip->id,
            'status' => 'on_duty_driving'
        ]);
    }

    #[Test]
    public function it_validates_driver_exists_before_starting_trip()
    {
        // Arrange
        $carrier = Carrier::factory()->create();
        $driver = UserDriverDetail::factory()->create(['carrier_id' => $carrier->id]);
        $trip = Trip::factory()->accepted()->create([
            'carrier_id' => $carrier->id,
            'user_driver_detail_id' => $driver->id,
        ]);
        $invalidDriverId = 99999;

        $this->fmcsaService
            ->method('validateTripStart')
            ->willReturn(['valid' => true, 'errors' => []]);

        // Act & Assert - Trying to start trip with a different driver should fail
        $this->expectException(ValidationException::class);
        $this->tripService->startTrip($trip, $invalidDriverId);
    }
}
