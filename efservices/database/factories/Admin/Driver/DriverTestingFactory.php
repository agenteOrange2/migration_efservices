<?php

namespace Database\Factories\Admin\Driver;

use App\Models\Admin\Driver\DriverTesting;
use App\Models\Carrier;
use App\Models\UserDriverDetail;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Admin\Driver\DriverTesting>
 */
class DriverTestingFactory extends Factory
{
    protected $model = DriverTesting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $testTypes = array_keys(DriverTesting::getTestTypes());
        $testResults = array_keys(DriverTesting::getTestResults());
        $statuses = array_keys(DriverTesting::getStatuses());
        $locations = array_keys(DriverTesting::getLocations());
        $billOptions = array_keys(DriverTesting::getBillOptions());
        $administrators = array_keys(DriverTesting::getAdministrators());

        return [
            'carrier_id' => Carrier::factory(),
            'user_driver_detail_id' => UserDriverDetail::factory(),
            'test_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'test_type' => $this->faker->randomElement($testTypes),
            'test_result' => $this->faker->randomElement($testResults),
            'status' => $this->faker->randomElement($statuses),
            'administered_by' => $this->faker->randomElement($administrators),
            'mro' => $this->faker->optional()->company(),
            'requester_name' => $this->faker->optional()->name(),
            'location' => $this->faker->randomElement($locations),
            'scheduled_time' => $this->faker->optional()->dateTimeBetween('-1 month', '+1 month'),
            'notes' => $this->faker->optional()->paragraph(),
            'next_test_due' => $this->faker->optional()->dateTimeBetween('now', '+1 year'),
            'is_random_test' => $this->faker->boolean(30),
            'is_post_accident_test' => $this->faker->boolean(10),
            'is_reasonable_suspicion_test' => $this->faker->boolean(10),
            'is_pre_employment_test' => $this->faker->boolean(40),
            'is_follow_up_test' => $this->faker->boolean(10),
            'is_return_to_duty_test' => $this->faker->boolean(10),
            'is_other_reason_test' => $this->faker->boolean(10),
            'other_reason_description' => $this->faker->optional()->sentence(),
            'bill_to' => $this->faker->randomElement($billOptions),
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the test is a random test.
     */
    public function random(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_random_test' => true,
            'is_post_accident_test' => false,
            'is_reasonable_suspicion_test' => false,
            'is_pre_employment_test' => false,
            'is_follow_up_test' => false,
            'is_return_to_duty_test' => false,
            'is_other_reason_test' => false,
        ]);
    }

    /**
     * Indicate that the test is a pre-employment test.
     */
    public function preEmployment(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_random_test' => false,
            'is_post_accident_test' => false,
            'is_reasonable_suspicion_test' => false,
            'is_pre_employment_test' => true,
            'is_follow_up_test' => false,
            'is_return_to_duty_test' => false,
            'is_other_reason_test' => false,
        ]);
    }

    /**
     * Indicate that the test result is positive.
     */
    public function positive(): static
    {
        return $this->state(fn (array $attributes) => [
            'test_result' => 'Positive',
        ]);
    }

    /**
     * Indicate that the test result is negative.
     */
    public function negative(): static
    {
        return $this->state(fn (array $attributes) => [
            'test_result' => 'Negative',
        ]);
    }

    /**
     * Indicate that the test is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'Completed',
        ]);
    }

    /**
     * Indicate that the test is scheduled.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'Schedule',
            'scheduled_time' => $this->faker->dateTimeBetween('now', '+1 month'),
        ]);
    }
}
