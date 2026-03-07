<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\CompanyDriverDetail;
use App\Models\Admin\Vehicle\VehicleDriverAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CompanyDriverDetailTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_belongs_to_a_vehicle_driver_assignment()
    {
        $assignment = VehicleDriverAssignment::factory()->create();
        $companyDetail = CompanyDriverDetail::factory()->create([
            'vehicle_driver_assignment_id' => $assignment->id
        ]);

        $this->assertInstanceOf(VehicleDriverAssignment::class, $companyDetail->assignment);
        $this->assertEquals($assignment->id, $companyDetail->assignment->id);
    }

    /** @test */
    public function it_has_fillable_attributes_simplified()
    {
        $fillable = [
            'vehicle_driver_assignment_id',
            'carrier_id',
            'notes'
        ];

        $companyDriver = new CompanyDriverDetail();
        $this->assertEquals($fillable, $companyDriver->getFillable());
    }

    /** @test */
    public function it_can_be_created_with_minimal_fields()
    {
        $assignment = VehicleDriverAssignment::factory()->create();
        $companyDriver = CompanyDriverDetail::factory()->create([
            'vehicle_driver_assignment_id' => $assignment->id,
            'notes' => 'Notas de prueba'
        ]);

        $this->assertEquals($assignment->id, $companyDriver->vehicle_driver_assignment_id);
        $this->assertEquals('Notas de prueba', $companyDriver->notes);
    }
}