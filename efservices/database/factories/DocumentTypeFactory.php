<?php

namespace Database\Factories;

use App\Models\DocumentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DocumentType>
 */
class DocumentTypeFactory extends Factory
{
    protected $model = DocumentType::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->randomElement([
                'Insurance Certificate',
                'DOT Registration',
                'MC Authority',
                'Vehicle Registration',
                'Driver License',
                'Medical Certificate',
            ]),
            'requirement' => $this->faker->boolean(80), // 80% chance of being required
        ];
    }

    /**
     * Indicate that the document type is required.
     */
    public function required(): static
    {
        return $this->state(fn (array $attributes) => [
            'requirement' => true,
        ]);
    }

    /**
     * Indicate that the document type is optional.
     */
    public function optional(): static
    {
        return $this->state(fn (array $attributes) => [
            'requirement' => false,
        ]);
    }
}
