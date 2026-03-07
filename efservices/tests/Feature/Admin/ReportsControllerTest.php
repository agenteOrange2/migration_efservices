<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Carrier;
use App\Models\Membership;
use App\Models\UserDriverDetail;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Admin\Driver\DriverAccident;
use App\Models\Admin\Vehicle\VehicleMaintenance;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class ReportsControllerTest extends AdminTestCase
{
    protected $carrier;
    protected $driver;
    protected $vehicle;

    protected function setUp(): void
    {
        parent::setUp();

        $membership = Membership::factory()->create();
        $this->carrier = Carrier::factory()->create(['id_plan' => $membership->id]);
        $this->driver = UserDriverDetail::factory()->create([
            'carrier_id' => $this->carrier->id
        ]);
        $this->vehicle = Vehicle::factory()->create(['carrier_id' => $this->carrier->id]);
    }

    /** @test */
    public function superadmin_can_access_reports_index()
    {
        $response = $this->actingAsSuperAdmin()
            ->get(route('reports.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.reports.index');
    }

    /** @test */
    public function superadmin_can_access_active_drivers_report()
    {
        UserDriverDetail::factory()->count(10)->create([
            'carrier_id' => $this->carrier->id,
            'status' => UserDriverDetail::STATUS_ACTIVE
        ]);

        $response = $this->actingAsSuperAdmin()
            ->get(route('reports.active-drivers'));

        $response->assertStatus(200);
    }

    /** @test */
    public function active_drivers_report_filters_by_carrier()
    {
        UserDriverDetail::factory()->count(5)->create([
            'carrier_id' => $this->carrier->id,
            'status' => UserDriverDetail::STATUS_ACTIVE
        ]);

        $otherCarrier = Carrier::factory()->create(['id_plan' => Membership::factory()->create()->id]);
        UserDriverDetail::factory()->count(3)->create([
            'carrier_id' => $otherCarrier->id,
            'status' => UserDriverDetail::STATUS_ACTIVE
        ]);

        $response = $this->actingAsSuperAdmin()
            ->get(route('reports.active-drivers', ['carrier' => $this->carrier->id]));

        $response->assertStatus(200);
    }

    /** @test */
    public function superadmin_can_access_accidents_report()
    {
        DriverAccident::factory()->count(5)->create([
            'user_driver_detail_id' => $this->driver->id
        ]);

        $response = $this->actingAsSuperAdmin()
            ->get(route('reports.accidents'));

        $response->assertStatus(200);
    }

    /** @test */
    public function accidents_report_filters_by_date_range()
    {
        DriverAccident::factory()->create([
            'user_driver_detail_id' => $this->driver->id,
            'accident_date' => now()->subMonth()->format('Y-m-d')
        ]);

        DriverAccident::factory()->create([
            'user_driver_detail_id' => $this->driver->id,
            'accident_date' => now()->format('Y-m-d')
        ]);

        $response = $this->actingAsSuperAdmin()
            ->get(route('reports.accidents', [
                'start_date' => now()->subMonth()->format('Y-m-d'),
                'end_date' => now()->format('Y-m-d')
            ]));

        $response->assertStatus(200);
    }

    /** @test */
    public function superadmin_can_access_driver_prospects_report()
    {
        UserDriverDetail::factory()->count(8)->create([
            'carrier_id' => $this->carrier->id
        ]);

        $response = $this->actingAsSuperAdmin()
            ->get(route('reports.driver-prospects'));

        $response->assertStatus(200);
    }

    /** @test */
    public function superadmin_can_access_equipment_list_report()
    {
        Vehicle::factory()->count(10)->create([
            'carrier_id' => $this->carrier->id
        ]);

        $response = $this->actingAsSuperAdmin()
            ->get(route('reports.equipment-list'));

        $response->assertStatus(200);
    }

    /** @test */
    public function equipment_list_report_filters_by_type()
    {
        Vehicle::factory()->count(5)->create([
            'carrier_id' => $this->carrier->id,
            'vehicle_type_id' => 1
        ]);

        Vehicle::factory()->count(3)->create([
            'carrier_id' => $this->carrier->id,
            'vehicle_type_id' => 2
        ]);

        $response = $this->actingAsSuperAdmin()
            ->get(route('reports.equipment-list', ['vehicle_type' => 1]));

        $response->assertStatus(200);
    }

    /** @test */
    public function superadmin_can_access_carrier_documents_report()
    {
        $response = $this->actingAsSuperAdmin()
            ->get(route('reports.carrier-documents'));

        $response->assertStatus(200);
    }

    /** @test */
    public function guest_cannot_access_reports()
    {
        $response = $this->get(route('reports.index'));

        $response->assertRedirect('/login');
    }
}
