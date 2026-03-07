<?php

namespace Database\Factories;

use App\Models\OwnerOperatorDetail;
use App\Models\Admin\Vehicle\VehicleDriverAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

class OwnerOperatorDetailFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = OwnerOperatorDetail::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vehicle_driver_assignment_id' => VehicleDriverAssignment::factory(),
            'owner_name' => $this->faker->name(),
            'owner_phone' => $this->faker->phoneNumber(),
            'owner_email' => $this->faker->optional()->safeEmail(),
            'contract_agreed' => $this->faker->boolean(80),
            'notes' => $this->faker->optional()->paragraph(),
        ];
    }

    /**
     * Indicate that the owner operator has minimal information.
     */
    public function minimal(): static
    {
        return $this->state(fn (array $attributes) => [
            'owner_email' => null,
            'contract_agreed' => false,
            'notes' => null,
        ]);
    }

    /**
     * Create an owner operator detail for a specific assignment.
     */
    public function forAssignment($assignment): static
    {
        return $this->state(fn (array $attributes) => [
            'vehicle_driver_assignment_id' => is_object($assignment) ? $assignment->id : $assignment,
        ]);
    }
}