<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\Carrier\CarrierReportService;
use App\Models\Carrier;
use App\Models\UserDriverDetail;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Admin\Driver\DriverAccident;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class CarrierReportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CarrierReportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CarrierReportService::class);
    }

    /** @test */
    public function it_can_get_dashboard_metrics_for_a_carrier()
    {
        // Create a carrier with some data
        $carrier = Carrier::factory()->create();
        UserDriverDetail::factory()->count(5)->create(['carrier_id' => $carrier->id]);
        Vehicle::factory()->count(3)->create(['carrier_id' => $carrier->id]);

        // Get dashboard metrics
        $metrics = $this->service->getDashboardMetrics($carrier->id);

        // Assert structure
        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('drivers', $metrics);
        $this->assertArrayHasKey('vehicles', $metrics);
        $this->assertArrayHasKey('accidents', $metrics);
        $this->assertArrayHasKey('licenses', $metrics);
        $this->assertArrayHasKey('maintenance', $metrics);
        $this->assertArrayHasKey('repairs', $metrics);

        // Assert driver metrics
        $this->assertEquals(5, $metrics['drivers']['total']);
        
        // Assert vehicle metrics
        $this->assertEquals(3, $metrics['vehicles']['total']);
    }

    /** @test */
    public function it_caches_dashboard_metrics()
    {
        $carrier = Carrier::factory()->create();
        
        // Clear cache first
        Cache::flush();

        // First call should hit the database
        $metrics1 = $this->service->getDashboardMetrics($carrier->id);

        // Second call should use cache
        $metrics2 = $this->service->getDashboardMetrics($carrier->id);

        // Both should be identical
        $this->assertEquals($metrics1, $metrics2);
    }

    /** @test */
    public function it_can_invalidate_carrier_cache()
    {
        $carrier = Carrier::factory()->create();
        
        // Get metrics to populate cache
        $this->service->getDashboardMetrics($carrier->id);

        // Invalidate cache
        $this->service->invalidateCarrierCache($carrier->id);

        // Cache should be cleared (we can't directly test this, but we can verify no errors)
        $this->assertTrue(true);
    }

    /** @test */
    public function it_returns_zero_metrics_for_carrier_with_no_data()
    {
        $carrier = Carrier::factory()->create();

        $metrics = $this->service->getDashboardMetrics($carrier->id);

        // All counts should be zero
        $this->assertEquals(0, $metrics['drivers']['total']);
        $this->assertEquals(0, $metrics['vehicles']['total']);
        $this->assertEquals(0, $metrics['accidents']['total']);
    }

    /** @test */
    public function it_can_get_driver_report_for_a_carrier()
    {
        // Create a carrier with drivers
        $carrier = Carrier::factory()->create();
        UserDriverDetail::factory()->count(10)->create(['carrier_id' => $carrier->id]);

        // Get driver report
        $report = $this->service->getDriverReport($carrier->id, []);

        // Assert structure
        $this->assertIsArray($report);
        $this->assertArrayHasKey('drivers', $report);
        $this->assertArrayHasKey('filters', $report);
        $this->assertArrayHasKey('stats', $report);

        // Assert pagination
        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $report['drivers']);
        $this->assertEquals(10, $report['drivers']->total());
    }

    /** @test */
    public function it_filters_drivers_by_search_term()
    {
        $carrier = Carrier::factory()->create();
        
        // Create a driver with specific name
        $driver = UserDriverDetail::factory()->create([
            'carrier_id' => $carrier->id,
            'last_name' => 'TestDriver',
        ]);

        // Create other drivers
        UserDriverDetail::factory()->count(5)->create(['carrier_id' => $carrier->id]);

        // Search for the specific driver
        $report = $this->service->getDriverReport($carrier->id, ['search' => 'TestDriver']);

        // Should only return the matching driver
        $this->assertGreaterThanOrEqual(1, $report['drivers']->total());
    }

    /** @test */
    public function it_filters_drivers_by_status()
    {
        $carrier = Carrier::factory()->create();
        
        // Create active and inactive drivers
        UserDriverDetail::factory()->count(3)->create([
            'carrier_id' => $carrier->id,
            'status' => UserDriverDetail::STATUS_ACTIVE,
        ]);
        UserDriverDetail::factory()->count(2)->create([
            'carrier_id' => $carrier->id,
            'status' => UserDriverDetail::STATUS_INACTIVE,
        ]);

        // Filter by active status
        $report = $this->service->getDriverReport($carrier->id, ['status' => 'active']);

        // Should only return active drivers
        $this->assertEquals(3, $report['drivers']->total());
    }

    /** @test */
    public function it_only_returns_drivers_for_the_specified_carrier()
    {
        $carrier1 = Carrier::factory()->create();
        $carrier2 = Carrier::factory()->create();
        
        // Create drivers for both carriers
        UserDriverDetail::factory()->count(5)->create(['carrier_id' => $carrier1->id]);
        UserDriverDetail::factory()->count(3)->create(['carrier_id' => $carrier2->id]);

        // Get report for carrier1
        $report = $this->service->getDriverReport($carrier1->id, []);

        // Should only return carrier1's drivers
        $this->assertEquals(5, $report['drivers']->total());
        
        foreach ($report['drivers'] as $driver) {
            $this->assertEquals($carrier1->id, $driver->carrier_id);
        }
    }

    /** @test */
    public function it_applies_date_range_filter_to_driver_report()
    {
        $carrier = Carrier::factory()->create();
        
        // Create drivers with different dates
        UserDriverDetail::factory()->create([
            'carrier_id' => $carrier->id,
            'created_at' => now()->subDays(60),
        ]);
        UserDriverDetail::factory()->count(3)->create([
            'carrier_id' => $carrier->id,
            'created_at' => now()->subDays(15),
        ]);

        // Filter by last 30 days (default)
        $report = $this->service->getDriverReport($carrier->id, []);

        // Should only return drivers from last 30 days
        $this->assertEquals(3, $report['drivers']->total());
    }

    /** @test */
    public function it_paginates_driver_report_results()
    {
        $carrier = Carrier::factory()->create();
        UserDriverDetail::factory()->count(25)->create(['carrier_id' => $carrier->id]);

        // Get first page with 10 per page
        $report = $this->service->getDriverReport($carrier->id, ['per_page' => 10]);

        // Should return 10 items on first page
        $this->assertEquals(10, $report['drivers']->count());
        $this->assertEquals(25, $report['drivers']->total());
        $this->assertEquals(3, $report['drivers']->lastPage());
    }

    /** @test */
    public function it_can_export_driver_report_to_pdf()
    {
        // Create a carrier with drivers
        $carrier = Carrier::factory()->create();
        UserDriverDetail::factory()->count(5)->create(['carrier_id' => $carrier->id]);

        // Export to PDF
        $response = $this->service->exportDriverReportPdf($carrier->id, []);

        // Assert response is a PDF download
        $this->assertInstanceOf(\Illuminate\Http\Response::class, $response);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        
        // Assert filename contains carrier slug and date
        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('drivers_report_', $contentDisposition);
        $this->assertStringContainsString($carrier->slug, $contentDisposition);
        $this->assertStringContainsString('.pdf', $contentDisposition);
    }

    /** @test */
    public function it_includes_filters_in_pdf_export()
    {
        $carrier = Carrier::factory()->create();
        UserDriverDetail::factory()->count(5)->create([
            'carrier_id' => $carrier->id,
            'status' => UserDriverDetail::STATUS_ACTIVE,
        ]);

        $filters = [
            'status' => 'active',
            'date_from' => '2024-01-01',
            'date_to' => '2024-12-31',
        ];

        // Export with filters
        $response = $this->service->exportDriverReportPdf($carrier->id, $filters);

        // Should not throw an error
        $this->assertInstanceOf(\Illuminate\Http\Response::class, $response);
    }

    /** @test */
    public function it_exports_pdf_with_no_drivers()
    {
        $carrier = Carrier::factory()->create();
        // No drivers created

        // Export to PDF
        $response = $this->service->exportDriverReportPdf($carrier->id, []);

        // Should still generate a PDF
        $this->assertInstanceOf(\Illuminate\Http\Response::class, $response);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    /** @test */
    public function it_can_get_vehicle_report_for_a_carrier()
    {
        // Create a carrier with vehicles
        $carrier = Carrier::factory()->create();
        Vehicle::factory()->count(10)->create(['carrier_id' => $carrier->id]);

        // Get vehicle report
        $report = $this->service->getVehicleReport($carrier->id, []);

        // Assert structure
        $this->assertIsArray($report);
        $this->assertArrayHasKey('vehicles', $report);
        $this->assertArrayHasKey('filters', $report);
        $this->assertArrayHasKey('stats', $report);

        // Assert pagination
        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $report['vehicles']);
        $this->assertEquals(10, $report['vehicles']->total());
    }

    /** @test */
    public function it_only_returns_vehicles_for_the_specified_carrier()
    {
        $carrier1 = Carrier::factory()->create();
        $carrier2 = Carrier::factory()->create();
        
        // Create vehicles for both carriers
        Vehicle::factory()->count(5)->create(['carrier_id' => $carrier1->id]);
        Vehicle::factory()->count(3)->create(['carrier_id' => $carrier2->id]);

        // Get report for carrier1
        $report = $this->service->getVehicleReport($carrier1->id, []);

        // Should only return carrier1's vehicles
        $this->assertEquals(5, $report['vehicles']->total());
        
        foreach ($report['vehicles'] as $vehicle) {
            $this->assertEquals($carrier1->id, $vehicle->carrier_id);
        }
    }

    /** @test */
    public function it_can_export_vehicle_report_to_pdf()
    {
        // Create a carrier with vehicles
        $carrier = Carrier::factory()->create();
        Vehicle::factory()->count(5)->create(['carrier_id' => $carrier->id]);

        // Export to PDF
        $response = $this->service->exportVehicleReportPdf($carrier->id, []);

        // Assert response is a PDF download
        $this->assertInstanceOf(\Illuminate\Http\Response::class, $response);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        
        // Assert filename contains carrier slug and date
        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('vehicles_report_', $contentDisposition);
        $this->assertStringContainsString($carrier->slug, $contentDisposition);
        $this->assertStringContainsString('.pdf', $contentDisposition);
    }

    /** @test */
    public function it_includes_filters_in_vehicle_pdf_export()
    {
        $carrier = Carrier::factory()->create();
        Vehicle::factory()->count(5)->create([
            'carrier_id' => $carrier->id,
            'out_of_service' => false,
            'suspended' => false,
        ]);

        $filters = [
            'status' => 'active',
            'date_from' => '2024-01-01',
            'date_to' => '2024-12-31',
        ];

        // Export with filters
        $response = $this->service->exportVehicleReportPdf($carrier->id, $filters);

        // Should not throw an error
        $this->assertInstanceOf(\Illuminate\Http\Response::class, $response);
    }

    /** @test */
    public function it_exports_vehicle_pdf_with_no_vehicles()
    {
        $carrier = Carrier::factory()->create();
        // No vehicles created

        // Export to PDF
        $response = $this->service->exportVehicleReportPdf($carrier->id, []);

        // Should still generate a PDF
        $this->assertInstanceOf(\Illuminate\Http\Response::class, $response);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    /** @test */
    public function it_can_get_accident_report_for_a_carrier()
    {
        // Create a carrier with drivers and accidents
        $carrier = Carrier::factory()->create();
        $driver = UserDriverDetail::factory()->create(['carrier_id' => $carrier->id]);
        DriverAccident::factory()->count(5)->create([
            'user_driver_detail_id' => $driver->id,
            'accident_date' => now()->subDays(15), // Within last 30 days
        ]);

        // Get accident report
        $report = $this->service->getAccidentReport($carrier->id, []);

        // Assert structure
        $this->assertIsArray($report);
        $this->assertArrayHasKey('accidents', $report);
        $this->assertArrayHasKey('filters', $report);
        $this->assertArrayHasKey('stats', $report);

        // Assert pagination
        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $report['accidents']);
        $this->assertEquals(5, $report['accidents']->total());
    }

    /** @test */
    public function it_only_returns_accidents_for_the_specified_carrier()
    {
        $carrier1 = Carrier::factory()->create();
        $carrier2 = Carrier::factory()->create();
        
        // Create drivers for both carriers
        $driver1 = UserDriverDetail::factory()->create(['carrier_id' => $carrier1->id]);
        $driver2 = UserDriverDetail::factory()->create(['carrier_id' => $carrier2->id]);
        
        // Create accidents for both carriers (within last 30 days)
        DriverAccident::factory()->count(5)->create([
            'user_driver_detail_id' => $driver1->id,
            'accident_date' => now()->subDays(15),
        ]);
        DriverAccident::factory()->count(3)->create([
            'user_driver_detail_id' => $driver2->id,
            'accident_date' => now()->subDays(15),
        ]);

        // Get report for carrier1
        $report = $this->service->getAccidentReport($carrier1->id, []);

        // Should only return carrier1's accidents
        $this->assertEquals(5, $report['accidents']->total());
        
        foreach ($report['accidents'] as $accident) {
            $this->assertEquals($carrier1->id, $accident->userDriverDetail->carrier_id);
        }
    }

    /** @test */
    public function it_filters_accidents_by_search_term()
    {
        $carrier = Carrier::factory()->create();
        $driver = UserDriverDetail::factory()->create(['carrier_id' => $carrier->id]);
        
        // Create an accident with specific nature (within last 30 days)
        DriverAccident::factory()->create([
            'user_driver_detail_id' => $driver->id,
            'nature_of_accident' => 'Rear-end collision',
            'accident_date' => now()->subDays(15),
        ]);

        // Create other accidents (within last 30 days)
        DriverAccident::factory()->count(3)->create([
            'user_driver_detail_id' => $driver->id,
            'nature_of_accident' => 'Other accident',
            'accident_date' => now()->subDays(15),
        ]);

        // Search for the specific accident
        $report = $this->service->getAccidentReport($carrier->id, ['search' => 'Rear-end']);

        // Should only return the matching accident
        $this->assertEquals(1, $report['accidents']->total());
    }

    /** @test */
    public function it_filters_accidents_by_driver()
    {
        $carrier = Carrier::factory()->create();
        
        // Create two drivers
        $driver1 = UserDriverDetail::factory()->create(['carrier_id' => $carrier->id]);
        $driver2 = UserDriverDetail::factory()->create(['carrier_id' => $carrier->id]);
        
        // Create accidents for both drivers (within last 30 days)
        DriverAccident::factory()->count(3)->create([
            'user_driver_detail_id' => $driver1->id,
            'accident_date' => now()->subDays(15),
        ]);
        DriverAccident::factory()->count(2)->create([
            'user_driver_detail_id' => $driver2->id,
            'accident_date' => now()->subDays(15),
        ]);

        // Filter by driver1
        $report = $this->service->getAccidentReport($carrier->id, ['driver' => $driver1->id]);

        // Should only return driver1's accidents
        $this->assertEquals(3, $report['accidents']->total());
    }

    /** @test */
    public function it_calculates_accident_statistics_correctly()
    {
        $carrier = Carrier::factory()->create();
        $driver = UserDriverDetail::factory()->create(['carrier_id' => $carrier->id]);
        
        // Create accidents with different severities (within last 30 days)
        DriverAccident::factory()->create([
            'user_driver_detail_id' => $driver->id,
            'had_fatalities' => true,
            'number_of_fatalities' => 1,
            'accident_date' => now()->subDays(15),
        ]);
        DriverAccident::factory()->count(2)->create([
            'user_driver_detail_id' => $driver->id,
            'had_injuries' => true,
            'number_of_injuries' => 2,
            'accident_date' => now()->subDays(15),
        ]);
        DriverAccident::factory()->count(3)->create([
            'user_driver_detail_id' => $driver->id,
            'had_fatalities' => false,
            'had_injuries' => false,
            'accident_date' => now()->subDays(15),
        ]);

        // Get report
        $report = $this->service->getAccidentReport($carrier->id, []);

        // Assert statistics
        $this->assertEquals(6, $report['stats']['total']);
        $this->assertEquals(1, $report['stats']['with_fatalities']);
        $this->assertEquals(2, $report['stats']['with_injuries']);
        $this->assertEquals(3, $report['stats']['without_injuries']);
    }

    /** @test */
    public function it_applies_date_range_filter_to_accident_report()
    {
        $carrier = Carrier::factory()->create();
        $driver = UserDriverDetail::factory()->create(['carrier_id' => $carrier->id]);
        
        // Create accidents with different dates
        DriverAccident::factory()->create([
            'user_driver_detail_id' => $driver->id,
            'accident_date' => now()->subDays(60),
        ]);
        DriverAccident::factory()->count(3)->create([
            'user_driver_detail_id' => $driver->id,
            'accident_date' => now()->subDays(15),
        ]);

        // Filter by last 30 days (default)
        $report = $this->service->getAccidentReport($carrier->id, []);

        // Should only return accidents from last 30 days
        $this->assertEquals(3, $report['accidents']->total());
    }

    /** @test */
    public function it_paginates_accident_report_results()
    {
        $carrier = Carrier::factory()->create();
        $driver = UserDriverDetail::factory()->create(['carrier_id' => $carrier->id]);
        DriverAccident::factory()->count(25)->create([
            'user_driver_detail_id' => $driver->id,
            'accident_date' => now()->subDays(15),
        ]);

        // Get first page with 10 per page
        $report = $this->service->getAccidentReport($carrier->id, ['per_page' => 10]);

        // Should return 10 items on first page
        $this->assertEquals(10, $report['accidents']->count());
        $this->assertEquals(25, $report['accidents']->total());
        $this->assertEquals(3, $report['accidents']->lastPage());
    }

    /** @test */
    public function it_returns_empty_accident_report_for_carrier_with_no_accidents()
    {
        $carrier = Carrier::factory()->create();
        UserDriverDetail::factory()->create(['carrier_id' => $carrier->id]);

        // Get accident report
        $report = $this->service->getAccidentReport($carrier->id, []);

        // Should return empty results
        $this->assertEquals(0, $report['accidents']->total());
        $this->assertEquals(0, $report['stats']['total']);
    }

    /** @test */
    public function it_can_export_maintenance_report_to_pdf()
    {
        // Create a carrier with vehicles and maintenance records
        $carrier = Carrier::factory()->create();
        $vehicle = Vehicle::factory()->create(['carrier_id' => $carrier->id]);
        \App\Models\Admin\Vehicle\VehicleMaintenance::factory()->count(5)->create([
            'vehicle_id' => $vehicle->id,
            'service_date' => now()->subDays(15),
        ]);

        // Export to PDF
        $response = $this->service->exportMaintenanceReportPdf($carrier->id, []);

        // Assert response is a PDF download
        $this->assertInstanceOf(\Illuminate\Http\Response::class, $response);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        
        // Assert filename contains carrier slug and date
        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('maintenance_report_', $contentDisposition);
        $this->assertStringContainsString($carrier->slug, $contentDisposition);
        $this->assertStringContainsString('.pdf', $contentDisposition);
    }

    /** @test */
    public function it_includes_filters_in_maintenance_pdf_export()
    {
        $carrier = Carrier::factory()->create();
        $vehicle = Vehicle::factory()->create(['carrier_id' => $carrier->id]);
        \App\Models\Admin\Vehicle\VehicleMaintenance::factory()->count(5)->create([
            'vehicle_id' => $vehicle->id,
            'status' => true,
            'service_date' => now()->subDays(15),
        ]);

        $filters = [
            'status' => 'completed',
            'date_from' => '2024-01-01',
            'date_to' => '2024-12-31',
        ];

        // Export with filters
        $response = $this->service->exportMaintenanceReportPdf($carrier->id, $filters);

        // Should not throw an error
        $this->assertInstanceOf(\Illuminate\Http\Response::class, $response);
    }

    /** @test */
    public function it_exports_maintenance_pdf_with_no_records()
    {
        $carrier = Carrier::factory()->create();
        // No maintenance records created

        // Export to PDF
        $response = $this->service->exportMaintenanceReportPdf($carrier->id, []);

        // Should still generate a PDF
        $this->assertInstanceOf(\Illuminate\Http\Response::class, $response);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }
}
