<?php

namespace Database\Factories;

use App\Models\Trip;
use App\Models\Carrier;
use App\Models\UserDriverDetail;
use App\Models\Admin\Vehicle\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Trip>
 */
class TripFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Trip::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $scheduledStart = $this->faker->dateTimeBetween('now', '+1 week');
        $estimatedMinutes = $this->faker->numberBetween(60, 480);
        $hours = floor($estimatedMinutes / 60);
        $minutes = $estimatedMinutes % 60;

        return [
            'user_driver_detail_id' => UserDriverDetail::factory(),
            'carrier_id' => Carrier::factory(),
            'vehicle_id' => Vehicle::factory(),
            // Legacy fields (original table)
            'start_time' => $scheduledStart,
            'destination' => $this->faker->address(),
            'estimated_duration' => sprintf('%02d:%02d:00', $hours, $minutes),
            'status' => Trip::STATUS_PENDING,
            // New FMCSA fields
            'scheduled_start_date' => $scheduledStart,
            'scheduled_end_date' => $this->faker->optional()->dateTimeBetween($scheduledStart, '+2 weeks'),
            'estimated_duration_minutes' => $estimatedMinutes,
            'origin_address' => $this->faker->address(),
            'destination_address' => $this->faker->address(),
            'origin_latitude' => $this->faker->latitude(),
            'origin_longitude' => $this->faker->longitude(),
            'destination_latitude' => $this->faker->latitude(),
            'destination_longitude' => $this->faker->longitude(),
            'description' => $this->faker->optional()->sentence(),
            'notes' => $this->faker->optional()->sentence(),
            'load_type' => $this->faker->optional()->randomElement(['General Freight', 'Hazmat', 'Refrigerated', 'Oversized']),
            'load_weight' => $this->faker->optional()->numberBetween(1000, 45000),
            'pre_trip_inspection_completed' => false,
            'post_trip_inspection_completed' => false,
            'gps_tracking_enabled' => true,
            'has_violations' => false,
            'forgot_to_close' => false,
        ];
    }

    /**
     * Indicate that the trip is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Trip::STATUS_PENDING,
        ]);
    }

    /**
     * Indicate that the trip is accepted.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Trip::STATUS_ACCEPTED,
            'accepted_at' => now(),
        ]);
    }

    /**
     * Indicate that the trip is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Trip::STATUS_IN_PROGRESS,
            'accepted_at' => now()->subHour(),
            'started_at' => now(),
            'actual_start_time' => now(),
        ]);
    }

    /**
     * Indicate that the trip is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Trip::STATUS_COMPLETED,
            'accepted_at' => now()->subHours(5),
            'started_at' => now()->subHours(4),
            'actual_start_time' => now()->subHours(4),
            'completed_at' => now(),
            'actual_end_time' => now(),
            'actual_duration_minutes' => 240,
        ]);
    }

    /**
     * Indicate that the trip is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Trip::STATUS_CANCELLED,
            'cancellation_reason' => $this->faker->sentence(),
            'cancelled_at' => now(),
        ]);
    }

    /**
     * Create trip without driver assignment.
     */
    public function unassigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_driver_detail_id' => null,
        ]);
    }

    /**
     * Create trip with specific carrier.
     */
    public function forCarrier(Carrier $carrier): static
    {
        return $this->state(fn (array $attributes) => [
            'carrier_id' => $carrier->id,
        ]);
    }

    /**
     * Create trip with specific driver.
     */
    public function forDriver(UserDriverDetail $driver): static
    {
        return $this->state(fn (array $attributes) => [
            'user_driver_detail_id' => $driver->id,
            'carrier_id' => $driver->carrier_id,
        ]);
    }
}
