<?php

namespace Database\Factories;

use App\Models\Carrier;
use App\Models\DriverArchive;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DriverArchive>
 */
class DriverArchiveFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = DriverArchive::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'original_user_driver_detail_id' => $this->faker->numberBetween(1, 1000),
            'user_id' => User::factory(),
            'carrier_id' => Carrier::factory(),
            'migration_record_id' => null,
            'archived_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'archive_reason' => $this->faker->randomElement([
                DriverArchive::REASON_MIGRATION,
                DriverArchive::REASON_TERMINATION,
                DriverArchive::REASON_MANUAL,
            ]),
            'driver_data_snapshot' => [
                'name' => $this->faker->firstName(),
                'middle_name' => $this->faker->firstName(),
                'last_name' => $this->faker->lastName(),
                'email' => $this->faker->unique()->safeEmail(),
                'phone' => $this->faker->phoneNumber(),
                'date_of_birth' => $this->faker->date(),
                'address' => $this->faker->streetAddress(),
                'city' => $this->faker->city(),
                'state' => $this->faker->stateAbbr(),
                'zip' => $this->faker->postcode(),
            ],
            'licenses_snapshot' => [
                [
                    'license_number' => $this->faker->bothify('??######'),
                    'license_type' => 'CDL-A',
                    'state' => $this->faker->stateAbbr(),
                    'issue_date' => $this->faker->date(),
                    'expiration_date' => $this->faker->dateTimeBetween('now', '+2 years')->format('Y-m-d'),
                    'status' => 'active',
                ],
            ],
            'medical_snapshot' => [
                [
                    'certification_date' => $this->faker->date(),
                    'expiration_date' => $this->faker->dateTimeBetween('now', '+1 year')->format('Y-m-d'),
                    'examiner_name' => $this->faker->name(),
                    'status' => 'active',
                ],
            ],
            'certifications_snapshot' => [],
            'employment_history_snapshot' => [],
            'training_snapshot' => [],
            'testing_snapshot' => [],
            'accidents_snapshot' => [],
            'convictions_snapshot' => [],
            'inspections_snapshot' => [],
            'hos_snapshot' => [],
            'vehicle_assignments_snapshot' => [],
            'status' => DriverArchive::STATUS_ARCHIVED,
        ];
    }

    /**
     * Indicate that the archive is from a migration.
     */
    public function migration(): static
    {
        return $this->state(fn (array $attributes) => [
            'archive_reason' => DriverArchive::REASON_MIGRATION,
            'migration_record_id' => $this->faker->numberBetween(1, 1000),
        ]);
    }

    /**
     * Indicate that the archive is from a termination.
     */
    public function termination(): static
    {
        return $this->state(fn (array $attributes) => [
            'archive_reason' => DriverArchive::REASON_TERMINATION,
            'migration_record_id' => null,
        ]);
    }

    /**
     * Indicate that the archive has been restored.
     */
    public function restored(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DriverArchive::STATUS_RESTORED,
        ]);
    }
}
