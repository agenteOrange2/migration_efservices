<?php

namespace Database\Factories\Admin\Driver;

use App\Models\Admin\Driver\DriverAccident;
use App\Models\UserDriverDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Admin\Driver\DriverAccident>
 */
class DriverAccidentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = DriverAccident::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $hadInjuries = fake()->boolean(30); // 30% chance of injuries
        $hadFatalities = fake()->boolean(10); // 10% chance of fatalities
        
        return [
            'user_driver_detail_id' => UserDriverDetail::factory(),
            'accident_date' => fake()->dateTimeBetween('-2 years', 'now'),
            'nature_of_accident' => fake()->randomElement([
                'Rear-end collision',
                'Side-impact collision',
                'Head-on collision',
                'Single-vehicle accident',
                'Multi-vehicle accident',
                'Backing accident',
                'Parking lot accident',
                'Hit and run',
                'Rollover',
                'Other',
            ]),
            'had_injuries' => $hadInjuries,
            'number_of_injuries' => $hadInjuries ? fake()->numberBetween(1, 5) : null,
            'had_fatalities' => $hadFatalities,
            'number_of_fatalities' => $hadFatalities ? fake()->numberBetween(1, 3) : null,
            'comments' => fake()->optional(0.7)->paragraph(),
        ];
    }

    /**
     * Indicate that the accident had fatalities.
     */
    public function withFatalities(int $count = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'had_fatalities' => true,
            'number_of_fatalities' => $count,
        ]);
    }

    /**
     * Indicate that the accident had injuries.
     */
    public function withInjuries(int $count = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'had_injuries' => true,
            'number_of_injuries' => $count,
        ]);
    }

    /**
     * Indicate that the accident had no injuries or fatalities.
     */
    public function minor(): static
    {
        return $this->state(fn (array $attributes) => [
            'had_injuries' => false,
            'number_of_injuries' => null,
            'had_fatalities' => false,
            'number_of_fatalities' => null,
        ]);
    }
}
