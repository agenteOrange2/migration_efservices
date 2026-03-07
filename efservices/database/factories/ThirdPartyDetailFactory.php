<?php

namespace Database\Factories;

use App\Models\ThirdPartyDetail;
use App\Models\Admin\Vehicle\VehicleDriverAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

class ThirdPartyDetailFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ThirdPartyDetail::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vehicle_driver_assignment_id' => VehicleDriverAssignment::factory(),
            'third_party_name' => $this->faker->company(),
            'third_party_phone' => $this->faker->phoneNumber(),
            'third_party_email' => $this->faker->optional()->safeEmail(),
            'third_party_dba' => $this->faker->optional()->company(),
            'third_party_address' => $this->faker->optional()->address(),
            'third_party_contact' => $this->faker->optional()->name(),
            'third_party_fein' => $this->faker->optional()->numerify('##-#######'),
            'email_sent' => false,
            'notes' => $this->faker->optional()->paragraph(),
        ];
    }

    /**
     * Indicate that the third party has minimal information.
     */
    public function minimal(): static
    {
        return $this->state(fn (array $attributes) => [
            'third_party_email' => null,
            'third_party_dba' => null,
            'third_party_address' => null,
            'third_party_contact' => null,
            'third_party_fein' => null,
            'notes' => null,
        ]);
    }

    /**
     * Create a third party detail for a specific assignment.
     */
    public function forAssignment($assignment): static
    {
        return $this->state(fn (array $attributes) => [
            'vehicle_driver_assignment_id' => is_object($assignment) ? $assignment->id : $assignment,
        ]);
    }

    /**
     * Create a third party with a specific company name.
     */
    public function withCompany(string $companyName): static
    {
        return $this->state(fn (array $attributes) => [
            'third_party_name' => $companyName,
        ]);
    }
}