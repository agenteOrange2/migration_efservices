<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\UserDriverDetail;
use App\Models\DriverEmploymentCompany;
use App\Services\Driver\EmploymentHistoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class EmploymentHistoryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected EmploymentHistoryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new EmploymentHistoryService();
        Cache::flush();
    }

    /** @test */
    public function it_calculates_employment_gaps_correctly()
    {
        // Arrange
        $driver = UserDriverDetail::factory()->create();

        DriverEmploymentCompany::factory()->create([
            'user_driver_detail_id' => $driver->id,
            'employed_from' => '2020-01-01',
            'employed_to' => '2021-01-01',
        ]);

        // Gap of 365 days
        DriverEmploymentCompany::factory()->create([
            'user_driver_detail_id' => $driver->id,
            'employed_from' => '2022-01-01',
            'employed_to' => '2023-01-01',
        ]);

        // Act
        $gaps = $this->service->getEmploymentGaps($driver->id);

        // Assert
        $this->assertCount(1, $gaps);
        $this->assertEquals(365, $gaps[0]['days']);
        $this->assertArrayHasKey('from', $gaps[0]);
        $this->assertArrayHasKey('to', $gaps[0]);
    }

    /** @test */
    public function it_ignores_gaps_less_than_30_days()
    {
        // Arrange
        $driver = UserDriverDetail::factory()->create();

        DriverEmploymentCompany::factory()->create([
            'user_driver_detail_id' => $driver->id,
            'employed_from' => '2023-01-01',
            'employed_to' => '2023-03-01',
        ]);

        // Gap of only 15 days
        DriverEmploymentCompany::factory()->create([
            'user_driver_detail_id' => $driver->id,
            'employed_from' => '2023-03-16',
            'employed_to' => '2023-06-01',
        ]);

        // Act
        $gaps = $this->service->getEmploymentGaps($driver->id);

        // Assert
        $this->assertCount(0, $gaps);
    }

    /** @test */
    public function it_returns_empty_array_when_no_gaps_exist()
    {
        // Arrange
        $driver = UserDriverDetail::factory()->create();

        DriverEmploymentCompany::factory()->create([
            'user_driver_detail_id' => $driver->id,
            'employed_from' => '2020-01-01',
            'employed_to' => '2021-01-01',
        ]);

        DriverEmploymentCompany::factory()->create([
            'user_driver_detail_id' => $driver->id,
            'employed_from' => '2021-01-01',
            'employed_to' => '2023-01-01',
        ]);

        // Act
        $gaps = $this->service->getEmploymentGaps($driver->id);

        // Assert
        $this->assertCount(0, $gaps);
    }

    /** @test */
    public function it_searches_companies_with_caching()
    {
        // Arrange
        $searchTerm = 'ACME';

        // Act - First call (cache miss)
        $result1 = $this->service->searchCompanies($searchTerm, 1, 10);

        // Act - Second call (cache hit)
        $result2 = $this->service->searchCompanies($searchTerm, 1, 10);

        // Assert
        $this->assertEquals($result1, $result2);
        $this->assertArrayHasKey('data', $result1);
        $this->assertArrayHasKey('meta', $result1);
    }

    /** @test */
    public function it_validates_search_term_minimum_length()
    {
        // Act
        $result = $this->service->searchCompanies('A', 1, 10);

        // Assert
        $this->assertEmpty($result['data']);
        $this->assertEquals(0, $result['meta']['total']);
    }

    /** @test */
    public function it_limits_results_per_page()
    {
        // Act
        $result = $this->service->searchCompanies('Company', 1, 100);

        // Assert - Should be limited to 20
        $this->assertLessThanOrEqual(20, $result['meta']['per_page']);
    }

    /** @test */
    public function it_calculates_coverage_percentage_correctly()
    {
        // Arrange
        $driver = UserDriverDetail::factory()->create();

        // 5 years of employment (50% of required 10 years)
        DriverEmploymentCompany::factory()->create([
            'user_driver_detail_id' => $driver->id,
            'employed_from' => now()->subYears(5)->format('Y-m-d'),
            'employed_to' => now()->format('Y-m-d'),
        ]);

        // Act
        $coverage = $this->service->getEmploymentCoverage($driver->id);

        // Assert
        $this->assertArrayHasKey('percentage', $coverage);
        $this->assertArrayHasKey('years_covered', $coverage);
        $this->assertArrayHasKey('years_required', $coverage);
        $this->assertEquals(10, $coverage['years_required']);
        $this->assertGreaterThanOrEqual(45, $coverage['percentage']);
        $this->assertLessThanOrEqual(55, $coverage['percentage']);
    }

    /** @test */
    public function it_identifies_overlapping_employment_periods()
    {
        // Arrange
        $driver = UserDriverDetail::factory()->create();

        DriverEmploymentCompany::factory()->create([
            'user_driver_detail_id' => $driver->id,
            'employed_from' => '2020-01-01',
            'employed_to' => '2021-06-01',
        ]);

        // Overlapping period
        DriverEmploymentCompany::factory()->create([
            'user_driver_detail_id' => $driver->id,
            'employed_from' => '2021-01-01',
            'employed_to' => '2022-01-01',
        ]);

        // Act
        $overlaps = $this->service->getOverlappingPeriods($driver->id);

        // Assert
        $this->assertNotEmpty($overlaps);
        $this->assertArrayHasKey('company_1', $overlaps[0]);
        $this->assertArrayHasKey('company_2', $overlaps[0]);
        $this->assertArrayHasKey('overlap_days', $overlaps[0]);
    }

    /** @test */
    public function it_handles_current_employment_correctly()
    {
        // Arrange
        $driver = UserDriverDetail::factory()->create();

        DriverEmploymentCompany::factory()->create([
            'user_driver_detail_id' => $driver->id,
            'employed_from' => '2020-01-01',
            'employed_to' => null,  // Current employment
            'currently_employed' => true,
        ]);

        // Act
        $coverage = $this->service->getEmploymentCoverage($driver->id);

        // Assert
        $this->assertGreaterThan(0, $coverage['years_covered']);
    }
}
