<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Admin\Vehicle\VehicleDriverAssignment;
use App\Models\User;
use App\Models\UserDriverDetail;
use App\Models\CompanyDriverDetail;
use App\Models\OwnerOperatorDetail;
use App\Models\ThirdPartyDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class VehicleDriverAssignmentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_belongs_to_a_vehicle()
    {
        $vehicle = Vehicle::factory()->create();
        $assignment = VehicleDriverAssignment::factory()->create([
            'vehicle_id' => $vehicle->id
        ]);

        $this->assertInstanceOf(Vehicle::class, $assignment->vehicle);
        $this->assertEquals($vehicle->id, $assignment->vehicle->id);
    }

    /** @test */
    public function it_has_company_driver_detail_relationship()
    {
        $assignment = VehicleDriverAssignment::factory()->create([
            'driver_type' => 'company_driver'
        ]);
        $companyDetail = CompanyDriverDetail::factory()->create([
            'vehicle_driver_assignment_id' => $assignment->id
        ]);

        $this->assertInstanceOf(CompanyDriverDetail::class, $assignment->companyDriverDetail);
        $this->assertEquals($companyDetail->id, $assignment->companyDriverDetail->id);
    }

    /** @test */
    public function it_has_owner_operator_detail_relationship()
    {
        $assignment = VehicleDriverAssignment::factory()->ownerOperator()->create();
        $ownerDetail = OwnerOperatorDetail::factory()->forAssignment($assignment)->create();

        $this->assertInstanceOf(OwnerOperatorDetail::class, $assignment->ownerOperatorDetail);
        $this->assertEquals($ownerDetail->id, $assignment->ownerOperatorDetail->id);
    }

    /** @test */
    public function it_has_third_party_detail_relationship()
    {
        $assignment = VehicleDriverAssignment::factory()->thirdParty()->create();
        $thirdParty = ThirdPartyDetail::factory()->forAssignment($assignment)->create();

        $this->assertInstanceOf(ThirdPartyDetail::class, $assignment->thirdPartyDetail);
        $this->assertEquals($thirdParty->id, $assignment->thirdPartyDetail->id);
    }

    /** @test */
    public function is_active_respects_dates_and_status()
    {
        Carbon::setTestNow(Carbon::parse('2024-01-15'));

        $active = VehicleDriverAssignment::factory()->create([
            'status' => 'active',
            'start_date' => '2024-01-01',
            'end_date' => null,
        ]);
        $this->assertTrue($active->isActive());

        $futureStart = VehicleDriverAssignment::factory()->create([
            'status' => 'active',
            'start_date' => '2024-02-01',
            'end_date' => null,
        ]);
        $this->assertFalse($futureStart->isActive());

        $ended = VehicleDriverAssignment::factory()->create([
            'status' => 'active',
            'start_date' => '2023-12-01',
            'end_date' => '2024-01-10',
        ]);
        $this->assertFalse($ended->isActive());

        $inactive = VehicleDriverAssignment::factory()->create([
            'status' => 'inactive'
        ]);
        $this->assertFalse($inactive->isActive());

        Carbon::setTestNow();
    }

    /** @test */
    public function scope_active_returns_only_active()
    {
        VehicleDriverAssignment::factory()->count(3)->active()->create();
        VehicleDriverAssignment::factory()->count(2)->create(['status' => 'inactive']);

        $activeAssignments = VehicleDriverAssignment::active()->get();
        $this->assertCount(3, $activeAssignments);
        $activeAssignments->each(function ($assignment) {
            $this->assertEquals('active', $assignment->status);
        });
    }

    /** @test */
    public function scope_current_respects_date_range()
    {
        Carbon::setTestNow(Carbon::parse('2024-01-15'));

        // Current: started before today, no end_date
        VehicleDriverAssignment::factory()->create([
            'status' => 'active',
            'start_date' => '2024-01-01',
            'end_date' => null,
        ]);

        // Current: started before today, ends in future
        VehicleDriverAssignment::factory()->create([
            'status' => 'active',
            'start_date' => '2024-01-01',
            'end_date' => '2024-02-01',
        ]);

        // Not current: starts in future
        VehicleDriverAssignment::factory()->create([
            'status' => 'active',
            'start_date' => '2024-02-01',
            'end_date' => null,
        ]);

        // Not current: ended before today
        VehicleDriverAssignment::factory()->create([
            'status' => 'active',
            'start_date' => '2023-12-01',
            'end_date' => '2024-01-10',
        ]);

        $current = VehicleDriverAssignment::current()->get();
        $this->assertCount(2, $current);

        Carbon::setTestNow();
    }

    /** @test */
    public function it_can_end_assignment_sets_status_inactive_and_end_date()
    {
        Carbon::setTestNow(Carbon::parse('2024-01-15'));

        $assignment = VehicleDriverAssignment::factory()->create([
            'status' => 'active',
            'start_date' => '2024-01-01',
            'end_date' => null,
        ]);

        $assignment->end();

        $fresh = $assignment->fresh();
        $this->assertEquals('inactive', $fresh->status);
        $this->assertEquals(Carbon::today(), $fresh->end_date);

        Carbon::setTestNow();
    }

    /** @test */
    public function it_belongs_to_a_user_through_driver_detail()
    {
        $user = User::factory()->create();
        $driverDetail = UserDriverDetail::factory()->create([
            'user_id' => $user->id
        ]);

        $assignment = VehicleDriverAssignment::factory()->create([
            'user_driver_detail_id' => $driverDetail->id
        ]);

        $this->assertInstanceOf(User::class, $assignment->user);
        $this->assertEquals($user->id, $assignment->user->id);
    }
}