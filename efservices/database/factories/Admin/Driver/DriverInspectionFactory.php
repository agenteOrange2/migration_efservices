<?php

namespace Database\Factories\Admin\Driver;

use App\Models\Admin\Driver\DriverInspection;
use App\Models\UserDriverDetail;
use App\Models\Admin\Vehicle\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

class DriverInspectionFactory extends Factory
{
    protected $model = DriverInspection::class;

    public function definition(): array
    {
        return [
            'user_driver_detail_id' => UserDriverDetail::factory(),
            'vehicle_id' => null,
            'inspection_date' => $this->faker->date(),
            'inspection_type' => $this->faker->randomElement(['DOT Roadside', 'State Police', 'Annual DOT', 'Pre-trip', 'Post-trip']),
            'inspection_level' => $this->faker->randomElement(['Level I', 'Level II', 'Level III', 'Level IV', 'Level V', 'Level VI']),
            'inspector_name' => $this->faker->name(),
            'inspector_number' => $this->faker->optional()->bothify('INS-####'),
            'location' => $this->faker->optional()->city(),
            'status' => $this->faker->randomElement(['Pass', 'Fail', 'Conditional Pass', 'Out of Service', 'Pending']),
            'defects_found' => $this->faker->optional()->sentence(),
            'corrective_actions' => $this->faker->optional()->sentence(),
            'is_defects_corrected' => $this->faker->boolean(),
            'defects_corrected_date' => $this->faker->optional()->date(),
            'corrected_by' => $this->faker->optional()->name(),
            'is_vehicle_safe_to_operate' => $this->faker->boolean(80),
            'notes' => $this->faker->optional()->paragraph(),
        ];
    }
}
