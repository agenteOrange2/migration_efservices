<?php

namespace Database\Factories;

use App\Models\Membership;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Membership>
 */
class MembershipFactory extends Factory
{
    protected $model = Membership::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['Basic', 'Professional', 'Enterprise']),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->randomFloat(2, 99, 999),
            'max_carrier' => $this->faker->numberBetween(1, 10),
            'max_drivers' => $this->faker->numberBetween(5, 100),
            'max_vehicles' => $this->faker->numberBetween(5, 100),
            'status' => 1,
            'show_in_register' => true,
        ];
    }

    /**
     * Indicate that the membership is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 0,
        ]);
    }

    /**
     * Create a basic membership.
     */
    public function basic(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Basic',
            'price' => 99.00,
            'max_drivers' => 5,
            'max_vehicles' => 5,
        ]);
    }

    /**
     * Create a professional membership.
     */
    public function professional(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Professional',
            'price' => 299.00,
            'max_drivers' => 25,
            'max_vehicles' => 25,
        ]);
    }

    /**
     * Create an enterprise membership.
     */
    public function enterprise(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Enterprise',
            'price' => 999.00,
            'max_drivers' => 100,
            'max_vehicles' => 100,
        ]);
    }
}
