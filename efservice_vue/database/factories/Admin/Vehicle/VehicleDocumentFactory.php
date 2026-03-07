<?php

namespace Database\Factories\Admin\Vehicle;

use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Admin\Vehicle\VehicleDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleDocumentFactory extends Factory
{
    protected $model = VehicleDocument::class;

    public function definition(): array
    {
        $documentTypes = [
            VehicleDocument::DOC_TYPE_REGISTRATION,
            VehicleDocument::DOC_TYPE_INSURANCE,
            VehicleDocument::DOC_TYPE_ANNUAL_INSPECTION,
            VehicleDocument::DOC_TYPE_IRP_PERMIT,
            VehicleDocument::DOC_TYPE_IFTA,
            VehicleDocument::DOC_TYPE_TITLE,
            VehicleDocument::DOC_TYPE_LEASE_AGREEMENT,
            VehicleDocument::DOC_TYPE_MAINTENANCE_RECORD,
            VehicleDocument::DOC_TYPE_EMISSIONS_TEST,
            VehicleDocument::DOC_TYPE_OTHER,
        ];

        $statuses = [
            VehicleDocument::STATUS_ACTIVE,
            VehicleDocument::STATUS_EXPIRED,
            VehicleDocument::STATUS_PENDING,
            VehicleDocument::STATUS_REJECTED,
        ];

        return [
            'vehicle_id' => Vehicle::factory(),
            'document_type' => $this->faker->randomElement($documentTypes),
            'document_number' => $this->faker->bothify('??-####-####'),
            'issued_date' => $this->faker->dateTimeBetween('-2 years', '-1 month'),
            'expiration_date' => $this->faker->dateTimeBetween('now', '+2 years'),
            'status' => $this->faker->randomElement($statuses),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    public function active(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => VehicleDocument::STATUS_ACTIVE,
        ]);
    }

    public function expired(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => VehicleDocument::STATUS_EXPIRED,
            'expiration_date' => $this->faker->dateTimeBetween('-1 year', '-1 day'),
        ]);
    }

    public function pending(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => VehicleDocument::STATUS_PENDING,
        ]);
    }

    public function rejected(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => VehicleDocument::STATUS_REJECTED,
        ]);
    }
}
