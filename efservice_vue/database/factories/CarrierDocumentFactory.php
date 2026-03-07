<?php

namespace Database\Factories;

use App\Models\CarrierDocument;
use App\Models\Carrier;
use App\Models\DocumentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CarrierDocument>
 */
class CarrierDocumentFactory extends Factory
{
    protected $model = CarrierDocument::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'carrier_id' => Carrier::factory(),
            'document_type_id' => DocumentType::factory(),
            'filename' => $this->faker->word() . '.pdf',
            'date' => $this->faker->date(),
            'notes' => $this->faker->sentence(),
            'status' => CarrierDocument::STATUS_PENDING,
        ];
    }

    /**
     * Indicate that the document is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CarrierDocument::STATUS_APPROVED,
        ]);
    }

    /**
     * Indicate that the document is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CarrierDocument::STATUS_REJECTED,
        ]);
    }

    /**
     * Indicate that the document is in process.
     */
    public function inProcess(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CarrierDocument::STATUS_IN_PROCESS,
        ]);
    }

    /**
     * Indicate that the document is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CarrierDocument::STATUS_PENDING,
        ]);
    }
}
