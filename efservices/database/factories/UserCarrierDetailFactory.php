<?php

namespace Database\Factories;

use App\Models\UserCarrierDetail;
use App\Models\User;
use App\Models\Carrier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserCarrierDetail>
 */
class UserCarrierDetailFactory extends Factory
{
    protected $model = UserCarrierDetail::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'carrier_id' => Carrier::factory(),
            'phone' => $this->faker->phoneNumber(),
            'job_position' => $this->faker->jobTitle(),
            'status' => UserCarrierDetail::STATUS_ACTIVE,
            'confirmation_token' => $this->faker->uuid(),
        ];
    }

    /**
     * Indicate that the user carrier detail is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserCarrierDetail::STATUS_ACTIVE,
        ]);
    }

    /**
     * Indicate that the user carrier detail is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserCarrierDetail::STATUS_INACTIVE,
        ]);
    }

    /**
     * Indicate that the user carrier detail is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserCarrierDetail::STATUS_PENDING,
        ]);
    }
}
