<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\ThirdPartyDetail;
use App\Models\Admin\Vehicle\VehicleDriverAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ThirdPartyDetailTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_belongs_to_a_vehicle_driver_assignment()
    {
        $assignment = VehicleDriverAssignment::factory()->create();
        $detail = ThirdPartyDetail::factory()->create([
            'vehicle_driver_assignment_id' => $assignment->id
        ]);

        $this->assertInstanceOf(VehicleDriverAssignment::class, $detail->assignment);
        $this->assertEquals($assignment->id, $detail->assignment->id);
    }

    /** @test */
    public function it_has_required_fillable_attributes_simplified()
    {
        $fillable = [
            'vehicle_driver_assignment_id',
            'third_party_name',
            'third_party_phone',
            'third_party_email',
            'third_party_dba',
            'third_party_address',
            'third_party_contact',
            'third_party_fein',
            'email_sent',
            'notes'
        ];

        $thirdParty = new ThirdPartyDetail();
        $this->assertEquals($fillable, $thirdParty->getFillable());
    }

    /** @test */
    public function it_can_be_created_with_minimal_attributes()
    {
        $data = [
            'third_party_name' => 'Sarah Wilson',
            'third_party_phone' => '555-7777'
        ];
        $assignment = VehicleDriverAssignment::factory()->create();
        $thirdParty = ThirdPartyDetail::factory()->forAssignment($assignment)->create($data);

        $this->assertEquals($data['third_party_name'], $thirdParty->third_party_name);
        $this->assertEquals($data['third_party_phone'], $thirdParty->third_party_phone);
        $this->assertEquals($assignment->id, $thirdParty->vehicle_driver_assignment_id);
    }

    /** @test */
    public function it_can_be_created_without_optional_fields()
    {
        $assignment = VehicleDriverAssignment::factory()->create();
        $thirdParty = ThirdPartyDetail::factory()->forAssignment($assignment)->minimal()->create([
            'third_party_name' => 'Required Name',
            'third_party_phone' => '555-0000'
        ]);

        $this->assertEquals('Required Name', $thirdParty->third_party_name);
        $this->assertEquals('555-0000', $thirdParty->third_party_phone);
        $this->assertNull($thirdParty->third_party_email);
        $this->assertNull($thirdParty->third_party_dba);
        $this->assertNull($thirdParty->third_party_address);
        $this->assertNull($thirdParty->third_party_contact);
        $this->assertNull($thirdParty->third_party_fein);
        $this->assertNull($thirdParty->notes);
    }
}