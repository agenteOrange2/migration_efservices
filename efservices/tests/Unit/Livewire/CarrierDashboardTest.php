<?php

namespace Tests\Unit\Livewire;

use Tests\TestCase;
use App\Models\User;
use App\Models\Carrier;
use App\Models\Membership;
use App\Models\CarrierDocument;
use App\Models\DocumentType;
use App\Models\UserDriverDetail;
use App\Models\UserCarrierDetail;
use App\Livewire\Carrier\CarrierDashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Carbon\Carbon;

class CarrierDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Carrier $carrier;
    protected Membership $membership;

    protected function setUp(): void
    {
        parent::setUp();

        // Create the user_carrier role
        \Spatie\Permission\Models\Role::create(['name' => 'user_carrier']);

        // Create a membership
        $this->membership = Membership::factory()->create([
            'max_drivers' => 20,
            'max_vehicles' => 10,
        ]);

        // Create a carrier with membership
        $this->carrier = Carrier::factory()->create([
            'id_plan' => $this->membership->id,
            'status' => Carrier::STATUS_ACTIVE,
            'documents_completed' => true,
        ]);

        // Create a user with carrier details
        $this->user = User::factory()->create();
        $this->user->assignRole('user_carrier');
        
        UserCarrierDetail::factory()->create([
            'user_id' => $this->user->id,
            'carrier_id' => $this->carrier->id,
        ]);
    }

    /** @test */
    public function it_initializes_carrier_from_authenticated_user_when_not_provided()
    {
        $component = Livewire::actingAs($this->user)
            ->test(CarrierDashboard::class);

        $this->assertEquals($this->carrier->id, $component->carrier->id);
    }

    /** @test */
    public function it_uses_provided_carrier_when_passed_as_parameter()
    {
        $anotherCarrier = Carrier::factory()->create([
            'id_plan' => $this->membership->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(CarrierDashboard::class, ['carrier' => $anotherCarrier]);

        $this->assertEquals($anotherCarrier->id, $component->carrier->id);
    }

    /** @test */
    public function it_calls_load_data_during_mount()
    {
        $component = Livewire::actingAs($this->user)
            ->test(CarrierDashboard::class);

        // Verify that data properties are set (indicating loadData was called)
        $this->assertNotNull($component->driversCount);
        $this->assertNotNull($component->vehiclesCount);
        $this->assertNotNull($component->documentStats);
    }

    /** @test */
    public function it_loads_drivers_and_vehicles_count_correctly()
    {
        // Create drivers and vehicles
        UserDriverDetail::factory()->count(5)->create(['carrier_id' => $this->carrier->id]);

        $component = Livewire::actingAs($this->user)
            ->test(CarrierDashboard::class);

        $this->assertEquals(5, $component->driversCount);
        $this->assertEquals(0, $component->vehiclesCount);
    }

    /** @test */
    public function it_loads_document_statistics_correctly()
    {
        $documentType = DocumentType::factory()->create();

        // Create documents with different statuses
        CarrierDocument::factory()->count(3)->create([
            'carrier_id' => $this->carrier->id,
            'document_type_id' => $documentType->id,
            'status' => CarrierDocument::STATUS_APPROVED,
        ]);
        
        CarrierDocument::factory()->count(2)->create([
            'carrier_id' => $this->carrier->id,
            'document_type_id' => $documentType->id,
            'status' => CarrierDocument::STATUS_PENDING,
        ]);
        
        CarrierDocument::factory()->count(1)->create([
            'carrier_id' => $this->carrier->id,
            'document_type_id' => $documentType->id,
            'status' => CarrierDocument::STATUS_REJECTED,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(CarrierDashboard::class);

        $this->assertEquals(6, $component->documentStats['total']);
        $this->assertEquals(3, $component->documentStats['approved']);
        $this->assertEquals(2, $component->documentStats['pending']);
        $this->assertEquals(1, $component->documentStats['rejected']);
    }

    /** @test */
    public function it_loads_document_type_counts_correctly()
    {
        $docType1 = DocumentType::factory()->create(['name' => 'Insurance']);
        $docType2 = DocumentType::factory()->create(['name' => 'License']);

        CarrierDocument::factory()->count(3)->create([
            'carrier_id' => $this->carrier->id,
            'document_type_id' => $docType1->id,
        ]);
        
        CarrierDocument::factory()->count(2)->create([
            'carrier_id' => $this->carrier->id,
            'document_type_id' => $docType2->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(CarrierDashboard::class);

        $this->assertEquals(3, $component->documentTypeCounts['Insurance']);
        $this->assertEquals(2, $component->documentTypeCounts['License']);
    }

    /** @test */
    public function it_loads_document_status_counts_correctly()
    {
        $documentType = DocumentType::factory()->create();

        CarrierDocument::factory()->count(2)->create([
            'carrier_id' => $this->carrier->id,
            'document_type_id' => $documentType->id,
            'status' => CarrierDocument::STATUS_PENDING,
        ]);
        
        CarrierDocument::factory()->count(3)->create([
            'carrier_id' => $this->carrier->id,
            'document_type_id' => $documentType->id,
            'status' => CarrierDocument::STATUS_IN_PROCESS,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(CarrierDashboard::class);

        $this->assertEquals(2, $component->documentStatusCounts['Pendiente']);
        $this->assertEquals(3, $component->documentStatusCounts['En Proceso']);
    }

    /** @test */
    public function it_loads_recent_drivers_limited_to_five()
    {
        // Create 10 drivers
        UserDriverDetail::factory()->count(10)->create([
            'carrier_id' => $this->carrier->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(CarrierDashboard::class);

        $this->assertCount(5, $component->recentDrivers);
    }

    /** @test */
    public function it_loads_recent_documents_limited_to_five()
    {
        $documentType = DocumentType::factory()->create();

        // Create 10 documents
        CarrierDocument::factory()->count(10)->create([
            'carrier_id' => $this->carrier->id,
            'document_type_id' => $documentType->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(CarrierDashboard::class);

        $this->assertCount(5, $component->recentDocuments);
    }

    /** @test */
    public function it_returns_max_drivers_and_max_vehicles_from_membership()
    {
        $component = Livewire::actingAs($this->user)
            ->test(CarrierDashboard::class);

        $limits = $component->membershipLimits;

        $this->assertEquals(20, $limits['maxDrivers']);
        $this->assertEquals(10, $limits['maxVehicles']);
    }

    /** @test */
    public function it_calculates_drivers_percentage_correctly()
    {
        // Create 10 drivers (50% of 20 max)
        UserDriverDetail::factory()->count(10)->create([
            'carrier_id' => $this->carrier->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(CarrierDashboard::class);

        $limits = $component->membershipLimits;

        $this->assertEquals(50, $limits['driversPercentage']);
    }

    /** @test */
    public function it_calculates_vehicles_percentage_correctly()
    {
        // This test assumes vehicles relationship exists
        // For now, we'll test with 0 vehicles
        $component = Livewire::actingAs($this->user)
            ->test(CarrierDashboard::class);

        $limits = $component->membershipLimits;

        $this->assertEquals(0, $limits['vehiclesPercentage']);
    }

    /** @test */
    public function it_handles_division_by_zero_when_max_drivers_is_zero()
    {
        $membership = Membership::factory()->create([
            'max_drivers' => 0,
            'max_vehicles' => 0,
        ]);

        $carrier = Carrier::factory()->create([
            'id_plan' => $membership->id,
        ]);

        UserCarrierDetail::factory()->create([
            'user_id' => $this->user->id,
            'carrier_id' => $carrier->id,
        ]);

        UserDriverDetail::factory()->count(5)->create([
            'carrier_id' => $carrier->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(CarrierDashboard::class, ['carrier' => $carrier]);

        $limits = $component->membershipLimits;

        $this->assertEquals(0, $limits['driversPercentage']);
        $this->assertEquals(0, $limits['vehiclesPercentage']);
    }

    /** @test */
    public function it_calculates_documents_this_month_correctly()
    {
        $documentType = DocumentType::factory()->create();

        // Create documents this month
        CarrierDocument::factory()->count(5)->create([
            'carrier_id' => $this->carrier->id,
            'document_type_id' => $documentType->id,
            'created_at' => Carbon::now(),
        ]);

        // Create documents last month
        CarrierDocument::factory()->count(3)->create([
            'carrier_id' => $this->carrier->id,
            'document_type_id' => $documentType->id,
            'created_at' => Carbon::now()->subMonth(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(CarrierDashboard::class);

        $this->assertEquals(5, $component->advancedMetrics['documentsThisMonth']);
    }

    /** @test */
    public function it_calculates_documents_growth_correctly()
    {
        $documentType = DocumentType::factory()->create();

        // Create 5 documents this month and 2 last month
        // Growth = ((5 - 2) / 2) * 100 = 150%
        CarrierDocument::factory()->count(5)->create([
            'carrier_id' => $this->carrier->id,
            'document_type_id' => $documentType->id,
            'created_at' => Carbon::now(),
        ]);

        CarrierDocument::factory()->count(2)->create([
            'carrier_id' => $this->carrier->id,
            'document_type_id' => $documentType->id,
            'created_at' => Carbon::now()->subMonth(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(CarrierDashboard::class);

        $this->assertEquals(150.0, $component->advancedMetrics['documentsGrowth']);
    }

    /** @test */
    public function it_calculates_avg_approval_days_correctly()
    {
        $documentType = DocumentType::factory()->create();

        // Create approved documents with different approval times
        CarrierDocument::factory()->create([
            'carrier_id' => $this->carrier->id,
            'document_type_id' => $documentType->id,
            'status' => CarrierDocument::STATUS_APPROVED,
            'created_at' => Carbon::now()->subDays(10),
            'updated_at' => Carbon::now()->subDays(5), // 5 days to approve
        ]);

        CarrierDocument::factory()->create([
            'carrier_id' => $this->carrier->id,
            'document_type_id' => $documentType->id,
            'status' => CarrierDocument::STATUS_APPROVED,
            'created_at' => Carbon::now()->subDays(20),
            'updated_at' => Carbon::now()->subDays(5), // 15 days to approve
        ]);

        // Average = (5 + 15) / 2 = 10 days
        $component = Livewire::actingAs($this->user)
            ->test(CarrierDashboard::class);

        $this->assertEquals(10.0, $component->advancedMetrics['avgApprovalDays']);
    }

    /** @test */
    public function it_calculates_active_and_inactive_drivers_correctly()
    {
        // Create 7 active and 3 inactive drivers
        UserDriverDetail::factory()->count(7)->create([
            'carrier_id' => $this->carrier->id,
            'status' => UserDriverDetail::STATUS_ACTIVE,
        ]);

        UserDriverDetail::factory()->count(3)->create([
            'carrier_id' => $this->carrier->id,
            'status' => UserDriverDetail::STATUS_INACTIVE,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(CarrierDashboard::class);

        $this->assertEquals(7, $component->advancedMetrics['activeDrivers']);
        $this->assertEquals(3, $component->advancedMetrics['inactiveDrivers']);
    }

    /** @test */
    public function it_identifies_expiring_documents_correctly()
    {
        $documentType = DocumentType::factory()->create();

        // Create documents older than 11 months (expiring)
        CarrierDocument::factory()->count(2)->create([
            'carrier_id' => $this->carrier->id,
            'document_type_id' => $documentType->id,
            'status' => CarrierDocument::STATUS_APPROVED,
            'created_at' => Carbon::now()->subMonths(12),
        ]);

        // Create recent documents (not expiring)
        CarrierDocument::factory()->count(3)->create([
            'carrier_id' => $this->carrier->id,
            'document_type_id' => $documentType->id,
            'status' => CarrierDocument::STATUS_APPROVED,
            'created_at' => Carbon::now()->subMonths(6),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(CarrierDashboard::class);

        $this->assertEquals(2, $component->advancedMetrics['expiringDocuments']);
    }

    /** @test */
    public function it_calculates_completion_rate_correctly()
    {
        $documentType = DocumentType::factory()->create();

        // Create 7 approved and 3 pending documents
        // Completion rate = (7 / 10) * 100 = 70%
        CarrierDocument::factory()->count(7)->create([
            'carrier_id' => $this->carrier->id,
            'document_type_id' => $documentType->id,
            'status' => CarrierDocument::STATUS_APPROVED,
        ]);

        CarrierDocument::factory()->count(3)->create([
            'carrier_id' => $this->carrier->id,
            'document_type_id' => $documentType->id,
            'status' => CarrierDocument::STATUS_PENDING,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(CarrierDashboard::class);

        $this->assertEquals(70.0, $component->advancedMetrics['completionRate']);
    }

    /** @test */
    public function it_calculates_pending_rate_correctly()
    {
        $documentType = DocumentType::factory()->create();

        // Create 3 pending and 7 approved documents
        // Pending rate = (3 / 10) * 100 = 30%
        CarrierDocument::factory()->count(3)->create([
            'carrier_id' => $this->carrier->id,
            'document_type_id' => $documentType->id,
            'status' => CarrierDocument::STATUS_PENDING,
        ]);

        CarrierDocument::factory()->count(7)->create([
            'carrier_id' => $this->carrier->id,
            'document_type_id' => $documentType->id,
            'status' => CarrierDocument::STATUS_APPROVED,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(CarrierDashboard::class);

        $this->assertEquals(30.0, $component->advancedMetrics['pendingRate']);
    }

    /** @test */
    public function it_generates_alert_when_pending_documents_exist()
    {
        $documentType = DocumentType::factory()->create();

        CarrierDocument::factory()->count(3)->create([
            'carrier_id' => $this->carrier->id,
            'document_type_id' => $documentType->id,
            'status' => CarrierDocument::STATUS_PENDING,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(CarrierDashboard::class);

        $alerts = collect($component->alertsData);
        $pendingAlert = $alerts->firstWhere('type', 'warning');

        $this->assertNotNull($pendingAlert);
        $this->assertEquals('AlertTriangle', $pendingAlert['icon']);
        $this->assertStringContainsString('3', $pendingAlert['message']);
    }

    /** @test */
    public function it_generates_alert_when_driver_usage_exceeds_90_percent()
    {
        // Create 19 drivers (95% of 20 max)
        UserDriverDetail::factory()->count(19)->create([
            'carrier_id' => $this->carrier->id,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(CarrierDashboard::class);

        $alerts = collect($component->alertsData);
        $limitAlert = $alerts->firstWhere('type', 'info');

        $this->assertNotNull($limitAlert);
        $this->assertEquals('Users', $limitAlert['icon']);
    }

    /** @test */
    public function it_generates_alert_when_rejected_documents_exist()
    {
        $documentType = DocumentType::factory()->create();

        CarrierDocument::factory()->count(2)->create([
            'carrier_id' => $this->carrier->id,
            'document_type_id' => $documentType->id,
            'status' => CarrierDocument::STATUS_REJECTED,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(CarrierDashboard::class);

        $alerts = collect($component->alertsData);
        $rejectedAlert = $alerts->firstWhere('type', 'danger');

        $this->assertNotNull($rejectedAlert);
        $this->assertEquals('XCircle', $rejectedAlert['icon']);
        $this->assertStringContainsString('2', $rejectedAlert['message']);
    }

    /** @test */
    public function it_generates_alert_when_expiring_documents_exist()
    {
        $documentType = DocumentType::factory()->create();

        CarrierDocument::factory()->count(3)->create([
            'carrier_id' => $this->carrier->id,
            'document_type_id' => $documentType->id,
            'status' => CarrierDocument::STATUS_APPROVED,
            'created_at' => Carbon::now()->subMonths(12),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(CarrierDashboard::class);

        $alerts = collect($component->alertsData);
        $expiringAlert = $alerts->where('icon', 'Clock')->first();

        $this->assertNotNull($expiringAlert);
        $this->assertEquals('warning', $expiringAlert['type']);
        $this->assertStringContainsString('3', $expiringAlert['message']);
    }

    /** @test */
    public function it_ensures_each_alert_has_required_fields()
    {
        $documentType = DocumentType::factory()->create();

        CarrierDocument::factory()->count(2)->create([
            'carrier_id' => $this->carrier->id,
            'document_type_id' => $documentType->id,
            'status' => CarrierDocument::STATUS_PENDING,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(CarrierDashboard::class);

        foreach ($component->alertsData as $alert) {
            $this->assertArrayHasKey('type', $alert);
            $this->assertArrayHasKey('icon', $alert);
            $this->assertArrayHasKey('title', $alert);
            $this->assertArrayHasKey('message', $alert);
            $this->assertArrayHasKey('action', $alert);
            $this->assertArrayHasKey('url', $alert);
        }
    }

    /** @test */
    public function it_generates_exactly_six_months_of_trends_data()
    {
        $component = Livewire::actingAs($this->user)
            ->test(CarrierDashboard::class);

        $this->assertCount(6, $component->trendsData);
    }

    /** @test */
    public function it_formats_trend_dates_correctly()
    {
        $component = Livewire::actingAs($this->user)
            ->test(CarrierDashboard::class);

        foreach ($component->trendsData as $trend) {
            // Check format is "M Y" (e.g., "Jan 2024")
            $this->assertMatchesRegularExpression('/^[A-Z][a-z]{2} \d{4}$/', $trend['month']);
        }
    }

    /** @test */
    public function it_counts_documents_and_drivers_per_month_correctly()
    {
        $documentType = DocumentType::factory()->create();

        // Create documents in different months
        CarrierDocument::factory()->count(3)->create([
            'carrier_id' => $this->carrier->id,
            'document_type_id' => $documentType->id,
            'created_at' => Carbon::now()->subMonths(2)->startOfMonth(),
        ]);

        UserDriverDetail::factory()->count(2)->create([
            'carrier_id' => $this->carrier->id,
            'created_at' => Carbon::now()->subMonths(2)->startOfMonth(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(CarrierDashboard::class);

        // Find the trend for 2 months ago
        $twoMonthsAgo = Carbon::now()->subMonths(2)->format('M Y');
        $trend = collect($component->trendsData)->firstWhere('month', $twoMonthsAgo);

        $this->assertNotNull($trend);
        $this->assertEquals(3, $trend['documents']);
        $this->assertEquals(2, $trend['drivers']);
    }

    /** @test */
    public function it_orders_trends_from_oldest_to_newest()
    {
        $component = Livewire::actingAs($this->user)
            ->test(CarrierDashboard::class);

        $months = collect($component->trendsData)->pluck('month')->toArray();
        
        // Convert to timestamps for comparison
        $timestamps = array_map(function($month) {
            return Carbon::createFromFormat('M Y', $month)->timestamp;
        }, $months);

        // Check if array is sorted in ascending order
        $sortedTimestamps = $timestamps;
        sort($sortedTimestamps);
        
        $this->assertEquals($sortedTimestamps, $timestamps);
    }

    /** @test */
    public function it_prepares_document_status_chart_data_correctly()
    {
        $documentType = DocumentType::factory()->create();

        CarrierDocument::factory()->count(5)->create([
            'carrier_id' => $this->carrier->id,
            'document_type_id' => $documentType->id,
            'status' => CarrierDocument::STATUS_APPROVED,
        ]);

        CarrierDocument::factory()->count(3)->create([
            'carrier_id' => $this->carrier->id,
            'document_type_id' => $documentType->id,
            'status' => CarrierDocument::STATUS_PENDING,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(CarrierDashboard::class);

        $chartData = $component->chartData['documentStatus'];

        $this->assertArrayHasKey('labels', $chartData);
        $this->assertArrayHasKey('data', $chartData);
        $this->assertArrayHasKey('colors', $chartData);
        
        $this->assertCount(4, $chartData['labels']);
        $this->assertCount(4, $chartData['data']);
        $this->assertCount(4, $chartData['colors']);
    }

    /** @test */
    public function it_prepares_drivers_status_chart_data_correctly()
    {
        UserDriverDetail::factory()->count(8)->create([
            'carrier_id' => $this->carrier->id,
            'status' => UserDriverDetail::STATUS_ACTIVE,
        ]);

        UserDriverDetail::factory()->count(2)->create([
            'carrier_id' => $this->carrier->id,
            'status' => UserDriverDetail::STATUS_INACTIVE,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(CarrierDashboard::class);

        $chartData = $component->chartData['driversStatus'];

        $this->assertArrayHasKey('labels', $chartData);
        $this->assertArrayHasKey('data', $chartData);
        $this->assertArrayHasKey('colors', $chartData);
        
        $this->assertEquals(['Activos', 'Inactivos'], $chartData['labels']);
        $this->assertEquals([8, 2], $chartData['data']);
    }

    /** @test */
    public function it_assigns_correct_colors_to_chart_data()
    {
        $component = Livewire::actingAs($this->user)
            ->test(CarrierDashboard::class);

        $documentColors = $component->chartData['documentStatus']['colors'];
        
        // Green for approved, Yellow for pending, Red for rejected, Blue for in process
        $this->assertEquals('#10B981', $documentColors[0]); // Green
        $this->assertEquals('#F59E0B', $documentColors[1]); // Yellow
        $this->assertEquals('#EF4444', $documentColors[2]); // Red
        $this->assertEquals('#3B82F6', $documentColors[3]); // Blue

        $driverColors = $component->chartData['driversStatus']['colors'];
        
        $this->assertEquals('#10B981', $driverColors[0]); // Green for active
        $this->assertEquals('#6B7280', $driverColors[1]); // Gray for inactive
    }

    /** @test */
    public function it_reloads_all_data_when_refresh_data_is_called()
    {
        $documentType = DocumentType::factory()->create();

        $component = Livewire::actingAs($this->user)
            ->test(CarrierDashboard::class);

        $initialDocCount = $component->documentStats['total'];

        // Add new documents
        CarrierDocument::factory()->count(3)->create([
            'carrier_id' => $this->carrier->id,
            'document_type_id' => $documentType->id,
        ]);

        $component->call('refreshData');

        $this->assertEquals($initialDocCount + 3, $component->documentStats['total']);
    }

    /** @test */
    public function it_emits_data_refreshed_event_after_refresh()
    {
        $component = Livewire::actingAs($this->user)
            ->test(CarrierDashboard::class)
            ->call('refreshData')
            ->assertDispatched('dataRefreshed');
    }
}
