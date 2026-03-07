<?php

namespace Database\Factories\Admin\Vehicle;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Admin\Vehicle\VehicleMaintenance;
use App\Models\Admin\Vehicle\Vehicle;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Admin\Vehicle\VehicleMaintenance>
 */
class VehicleMaintenanceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = VehicleMaintenance::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $serviceDate = $this->faker->dateTimeBetween('-6 months', 'now');
        
        return [
            'vehicle_id' => Vehicle::factory(),
            'unit' => $this->faker->bothify('UNIT-###'),
            'service_tasks' => $this->faker->randomElement([
                'Oil change and filter replacement',
                'Brake inspection and pad replacement',
                'Tire rotation and alignment',
                'Engine diagnostic and tune-up',
                'Transmission service',
                'Annual safety inspection',
                'DOT inspection',
                'Air filter replacement',
                'Battery replacement',
                'Coolant flush and fill'
            ]),
            'service_date' => $serviceDate,
            'next_service_date' => $this->faker->dateTimeBetween('now', '+6 months'),
            'vendor_mechanic' => $this->faker->company() . ' Auto Service',
            'cost' => $this->faker->randomFloat(2, 50, 2000),
            'odometer' => $this->faker->numberBetween(10000, 500000),
            'description' => $this->faker->optional()->paragraph(),
            'status' => $this->faker->boolean(60), // 60% completed
            'is_historical' => false,
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    /**
     * Indicate that the maintenance is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => true,
        ]);
    }

    /**
     * Indicate that the maintenance is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => false,
        ]);
    }

    /**
     * Indicate that the maintenance is overdue.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => false,
            'next_service_date' => $this->faker->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }

    /**
     * Indicate that the maintenance is upcoming.
     */
    public function upcoming(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => false,
            'next_service_date' => $this->faker->dateTimeBetween('now', '+15 days'),
        ]);
    }

    /**
     * Indicate that the maintenance is historical.
     */
    public function historical(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_historical' => true,
            'service_date' => $this->faker->dateTimeBetween('-2 years', '-6 months'),
            'next_service_date' => $this->faker->optional()->dateTimeBetween('-6 months', 'now'),
        ]);
    }
}
