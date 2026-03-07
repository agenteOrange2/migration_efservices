<?php

namespace Database\Factories;

use App\Models\Admin\Vehicle\VehicleDriverAssignment;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\User;
use App\Models\UserDriverDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Admin\Vehicle\VehicleDriverAssignment>
 */
class VehicleDriverAssignmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = VehicleDriverAssignment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vehicle_id' => Vehicle::factory(),
            'user_driver_detail_id' => UserDriverDetail::factory(),
            'driver_type' => $this->faker->randomElement(['company_driver', 'owner_operator', 'third_party']),
            'status' => 'active',
            // removed assigned_by: column does not exist on table
            'start_date' => $this->faker->date('Y-m-d'),
            'end_date' => null,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the assignment is for a company driver.
     */
    public function companyDriver(): static
    {
        return $this->state(fn (array $attributes) => [
            'driver_type' => 'company_driver',
        ]);
    }

    /**
     * Indicate that the assignment is for an owner operator.
     */
    public function ownerOperator(): static
    {
        return $this->state(fn (array $attributes) => [
            'driver_type' => 'owner_operator',
        ]);
    }

    /**
     * Indicate that the assignment is for a third party.
     */
    public function thirdParty(): static
    {
        return $this->state(fn (array $attributes) => [
            'driver_type' => 'third_party',
        ]);
    }

    /**
     * Indicate that the assignment is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'end_date' => null,
        ]);
    }
}