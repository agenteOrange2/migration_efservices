<?php

namespace Database\Factories;

use App\Models\CompanyDriverDetail;
use App\Models\Admin\Vehicle\VehicleDriverAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyDriverDetailFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CompanyDriverDetail::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vehicle_driver_assignment_id' => VehicleDriverAssignment::factory(),
            'carrier_id' => null,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Create a company driver detail for a specific assignment.
     */
    public function forAssignment($assignment): static
    {
        return $this->state(fn (array $attributes) => [
            'vehicle_driver_assignment_id' => is_object($assignment) ? $assignment->id : $assignment,
        ]);
    }

    /**
     * Attach a carrier id.
     */
    public function forCarrier(int $carrierId): static
    {
        return $this->state(fn (array $attributes) => [
            'carrier_id' => $carrierId,
        ]);
    }
}