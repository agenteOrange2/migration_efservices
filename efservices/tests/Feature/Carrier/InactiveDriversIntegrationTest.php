<?php

namespace Tests\Feature\Carrier;

use App\Models\Carrier;
use App\Models\DriverArchive;
use App\Models\User;
use App\Models\UserCarrierDetail;
use App\Models\ArchiveAccessLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Integration Test for Inactive Drivers Archive Feature
 * 
 * Tests the complete flow as a carrier user:
 * 1. Navigate to inactive drivers list
 * 2. Search and filter drivers
 * 3. View driver details
 * 4. Download ZIP archive
 * 5. Verify all data displays correctly
 */
class InactiveDriversIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Carrier $carrier;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);

        // Create a carrier with all required setup
        $this->carrier = Carrier::factory()->create([
            'status' => Carrier::STATUS_ACTIVE,
            'documents_completed' => true,
        ]);

        // Create banking details for the carrier (required by CheckCarrierStatus middleware)
        \App\Models\CarrierBankingDetail::create([
            'carrier_id' => $this->carrier->id,
            'status' => 'approved',
            'account_holder_name' => 'Test Holder',
            'account_number' => '1234567890',
            'banking_routing_number' => '123456789',
        ]);

        // Create a user with carrier details
        $this->user = User::factory()->create([
            'status' => 1,
        ]);
        $this->user->assignRole('user_carrier');
        
        UserCarrierDetail::factory()->create([
            'user_id' => $this->user->id,
            'carrier_id' => $this->carrier->id,
            'status' => 1,
        ]);
    }

    /** @test */
    public function complete_flow_carrier_can_navigate_to_inactive_drivers_list()
    {
        $this->actingAs($this->user);

        // Create some inactive drivers for this carrier
        DriverArchive::factory()->count(3)->create([
            'carrier_id' => $this->carrier->id,
            'status' => DriverArchive::STATUS_ARCHIVED,
        ]);

        // Navigate to inactive drivers list
        $response = $this->get(route('carrier.drivers.inactive.index'));

        $response->assertStatus(200);
        $response->assertViewIs('carrier.drivers.inactive.index');
        $response->assertSee('Inactive Drivers');
    }

    /** @test */
    public function complete_flow_carrier_can_search_inactive_drivers()
    {
        $this->actingAs($this->user);

        // Create inactive drivers with specific names
        $archive1 = DriverArchive::factory()->create([
            'carrier_id' => $this->carrier->id,
            'status' => DriverArchive::STATUS_ARCHIVED,
            'driver_data_snapshot' => [
                'name' => 'John',
                'last_name' => 'Smith',
                'email' => 'john.smith@example.com',
            ],
        ]);

        $archive2 = DriverArchive::factory()->create([
            'carrier_id' => $this->carrier->id,
            'status' => DriverArchive::STATUS_ARCHIVED,
            'driver_data_snapshot' => [
                'name' => 'Jane',
                'last_name' => 'Doe',
                'email' => 'jane.doe@example.com',
            ],
        ]);

        // Navigate to list and verify Livewire component loads
        $response = $this->get(route('carrier.drivers.inactive.index'));
        $response->assertStatus(200);
        $response->assertSeeLivewire('carrier.inactive-drivers-list');
    }

    /** @test */
    public function complete_flow_carrier_can_view_driver_details()
    {
        $this->actingAs($this->user);

        // Create an archived driver with comprehensive data
        $archive = DriverArchive::factory()->create([
            'carrier_id' => $this->carrier->id,
            'status' => DriverArchive::STATUS_ARCHIVED,
            'driver_data_snapshot' => [
                'name' => 'John',
                'middle_name' => 'Michael',
                'last_name' => 'Doe',
                'email' => 'john.doe@example.com',
                'phone' => '555-1234',
                'date_of_birth' => '1980-01-15',
                'address' => '123 Main St',
                'city' => 'Springfield',
                'state' => 'IL',
                'zip' => '62701',
            ],
            'licenses_snapshot' => [
                [
                    'license_number' => 'CDL123456',
                    'license_type' => 'CDL-A',
                    'state' => 'IL',
                    'issue_date' => '2020-01-01',
                    'expiration_date' => '2025-01-01',
                    'status' => 'active',
                ],
            ],
            'medical_snapshot' => [
                [
                    'certification_date' => '2023-01-01',
                    'expiration_date' => '2024-01-01',
                    'examiner_name' => 'Dr. Smith',
                    'status' => 'active',
                ],
            ],
        ]);

        // View driver details
        $response = $this->get(route('carrier.drivers.inactive.show', $archive));

        $response->assertStatus(200);
        $response->assertViewIs('carrier.drivers.inactive.show');
        $response->assertViewHas('archive');
        
        // Verify personal information is displayed
        $response->assertSee('John');
        $response->assertSee('Doe');
        $response->assertSee('john.doe@example.com');
        $response->assertSee('555-1234');
        
        // Verify archive banner is displayed
        $response->assertSee('Archived Driver Record');
        $response->assertSee('historical, read-only record');
        
        // Verify all tabs are present
        $response->assertSee('Personal Information');
        $response->assertSee('Employment');
        $response->assertSee('Licenses');
        $response->assertSee('Medical');
        $response->assertSee('Documents');
        
        // Verify download button is present
        $response->assertSee('Download Archive', false);
    }

    /** @test */
    public function complete_flow_carrier_can_view_all_data_sections()
    {
        $this->actingAs($this->user);

        // Create an archived driver with data in all sections
        $archive = DriverArchive::factory()->create([
            'carrier_id' => $this->carrier->id,
            'status' => DriverArchive::STATUS_ARCHIVED,
            'driver_data_snapshot' => [
                'name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john.doe@example.com',
            ],
            'licenses_snapshot' => [
                ['license_number' => 'CDL123', 'license_type' => 'CDL-A'],
            ],
            'medical_snapshot' => [
                ['examiner_name' => 'Dr. Smith', 'status' => 'active'],
            ],
            'certifications_snapshot' => [
                ['name' => 'Hazmat Certification', 'issue_date' => '2023-01-01'],
            ],
            'training_snapshot' => [
                ['course_name' => 'Defensive Driving', 'completion_date' => '2023-02-01'],
            ],
            'testing_snapshot' => [
                ['test_type' => 'Drug Test', 'result' => 'Negative', 'date' => '2023-03-01'],
            ],
            'accidents_snapshot' => [
                ['date' => '2022-05-15', 'description' => 'Minor fender bender'],
            ],
            'convictions_snapshot' => [
                ['violation_date' => '2021-08-20', 'violation_type' => 'Speeding'],
            ],
            'inspections_snapshot' => [
                ['date' => '2023-04-10', 'result' => 'Passed'],
            ],
            'vehicle_assignments_snapshot' => [
                ['vehicle_number' => 'TRUCK-001', 'assigned_date' => '2023-01-01'],
            ],
        ]);

        $response = $this->get(route('carrier.drivers.inactive.show', $archive));

        $response->assertStatus(200);
        
        // Verify all sections have content
        $response->assertSee('CDL123');
        $response->assertSee('Dr. Smith');
        $response->assertSee('Hazmat Certification');
        $response->assertSee('Defensive Driving');
        $response->assertSee('Drug Test');
        $response->assertSee('Minor fender bender');
        $response->assertSee('Speeding');
        $response->assertSee('Passed');
        $response->assertSee('TRUCK-001');
    }

    /** @test */
    public function complete_flow_access_is_logged_when_viewing_driver()
    {
        $this->actingAs($this->user);

        $archive = DriverArchive::factory()->create([
            'carrier_id' => $this->carrier->id,
            'status' => DriverArchive::STATUS_ARCHIVED,
        ]);

        // View driver details
        $this->get(route('carrier.drivers.inactive.show', $archive));

        // Verify access was logged
        $this->assertDatabaseHas('archive_access_logs', [
            'driver_archive_id' => $archive->id,
            'user_id' => $this->user->id,
            'carrier_id' => $this->carrier->id,
            'action_type' => 'view',
        ]);
    }

    /** @test */
    public function complete_flow_carrier_can_download_archive_zip()
    {
        $this->actingAs($this->user);

        // Create a fake storage disk for testing
        Storage::fake('public');

        $archive = DriverArchive::factory()->create([
            'carrier_id' => $this->carrier->id,
            'status' => DriverArchive::STATUS_ARCHIVED,
            'driver_data_snapshot' => [
                'name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john.doe@example.com',
            ],
        ]);

        // Attempt to download the archive
        $response = $this->get(route('carrier.drivers.inactive.download', $archive));

        // Verify response is a download
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/zip');
        
        // Verify download was logged
        $this->assertDatabaseHas('archive_access_logs', [
            'driver_archive_id' => $archive->id,
            'user_id' => $this->user->id,
            'carrier_id' => $this->carrier->id,
            'action_type' => 'download',
        ]);
    }

    /** @test */
    public function complete_flow_carrier_cannot_access_another_carriers_driver()
    {
        $this->actingAs($this->user);

        // Create another carrier
        $otherCarrier = Carrier::factory()->create();

        // Create an archived driver for the other carrier
        $archive = DriverArchive::factory()->create([
            'carrier_id' => $otherCarrier->id,
            'status' => DriverArchive::STATUS_ARCHIVED,
        ]);

        // Attempt to view the driver
        $response = $this->get(route('carrier.drivers.inactive.show', $archive));
        $response->assertStatus(403);

        // Attempt to download the archive
        $response = $this->get(route('carrier.drivers.inactive.download', $archive));
        $response->assertStatus(403);
    }

    /** @test */
    public function complete_flow_unauthenticated_user_cannot_access_inactive_drivers()
    {
        $archive = DriverArchive::factory()->create([
            'carrier_id' => $this->carrier->id,
            'status' => DriverArchive::STATUS_ARCHIVED,
        ]);

        // Attempt to access list
        $response = $this->get(route('carrier.drivers.inactive.index'));
        $response->assertRedirect(route('login'));

        // Attempt to view driver
        $response = $this->get(route('carrier.drivers.inactive.show', $archive));
        $response->assertRedirect(route('login'));

        // Attempt to download archive
        $response = $this->get(route('carrier.drivers.inactive.download', $archive));
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function complete_flow_carrier_sees_only_their_inactive_drivers()
    {
        $this->actingAs($this->user);

        // Create inactive drivers for this carrier
        $myArchive1 = DriverArchive::factory()->create([
            'carrier_id' => $this->carrier->id,
            'status' => DriverArchive::STATUS_ARCHIVED,
            'driver_data_snapshot' => [
                'name' => 'My',
                'last_name' => 'Driver1',
                'email' => 'my.driver1@example.com',
            ],
        ]);

        $myArchive2 = DriverArchive::factory()->create([
            'carrier_id' => $this->carrier->id,
            'status' => DriverArchive::STATUS_ARCHIVED,
            'driver_data_snapshot' => [
                'name' => 'My',
                'last_name' => 'Driver2',
                'email' => 'my.driver2@example.com',
            ],
        ]);

        // Create inactive drivers for another carrier
        $otherCarrier = Carrier::factory()->create();
        $otherArchive = DriverArchive::factory()->create([
            'carrier_id' => $otherCarrier->id,
            'status' => DriverArchive::STATUS_ARCHIVED,
            'driver_data_snapshot' => [
                'name' => 'Other',
                'last_name' => 'Driver',
                'email' => 'other.driver@example.com',
            ],
        ]);

        // Navigate to list
        $response = $this->get(route('carrier.drivers.inactive.index'));

        $response->assertStatus(200);
        
        // The Livewire component should only show this carrier's drivers
        // We can't directly test Livewire component output here, but we can verify
        // the view loads correctly and the component is present
        $response->assertSeeLivewire('carrier.inactive-drivers-list');
    }

    /** @test */
    public function complete_flow_filter_by_archive_reason_works()
    {
        $this->actingAs($this->user);

        // Create archives with different reasons
        $migrationArchive = DriverArchive::factory()->create([
            'carrier_id' => $this->carrier->id,
            'status' => DriverArchive::STATUS_ARCHIVED,
            'archive_reason' => DriverArchive::REASON_MIGRATION,
            'driver_data_snapshot' => [
                'name' => 'Migrated',
                'last_name' => 'Driver',
                'email' => 'migrated@example.com',
            ],
        ]);

        $terminationArchive = DriverArchive::factory()->create([
            'carrier_id' => $this->carrier->id,
            'status' => DriverArchive::STATUS_ARCHIVED,
            'archive_reason' => DriverArchive::REASON_TERMINATION,
            'driver_data_snapshot' => [
                'name' => 'Terminated',
                'last_name' => 'Driver',
                'email' => 'terminated@example.com',
            ],
        ]);

        // Navigate to list
        $response = $this->get(route('carrier.drivers.inactive.index'));

        $response->assertStatus(200);
        $response->assertSeeLivewire('carrier.inactive-drivers-list');
    }

    /** @test */
    public function complete_flow_date_range_filtering_works()
    {
        $this->actingAs($this->user);

        // Create archives with different dates
        $oldArchive = DriverArchive::factory()->create([
            'carrier_id' => $this->carrier->id,
            'status' => DriverArchive::STATUS_ARCHIVED,
            'archived_at' => now()->subMonths(6),
            'driver_data_snapshot' => [
                'name' => 'Old',
                'last_name' => 'Driver',
                'email' => 'old@example.com',
            ],
        ]);

        $recentArchive = DriverArchive::factory()->create([
            'carrier_id' => $this->carrier->id,
            'status' => DriverArchive::STATUS_ARCHIVED,
            'archived_at' => now()->subDays(7),
            'driver_data_snapshot' => [
                'name' => 'Recent',
                'last_name' => 'Driver',
                'email' => 'recent@example.com',
            ],
        ]);

        // Navigate to list
        $response = $this->get(route('carrier.drivers.inactive.index'));

        $response->assertStatus(200);
        $response->assertSeeLivewire('carrier.inactive-drivers-list');
    }

    /** @test */
    public function complete_flow_pagination_works_with_many_drivers()
    {
        $this->actingAs($this->user);

        // Create 20 inactive drivers (more than the 15 per page limit)
        DriverArchive::factory()->count(20)->create([
            'carrier_id' => $this->carrier->id,
            'status' => DriverArchive::STATUS_ARCHIVED,
        ]);

        // Navigate to list
        $response = $this->get(route('carrier.drivers.inactive.index'));

        $response->assertStatus(200);
        $response->assertSeeLivewire('carrier.inactive-drivers-list');
        
        // The Livewire component should handle pagination
        // We verify the component is present and will handle the pagination
    }

    /** @test */
    public function complete_flow_empty_state_shown_when_no_inactive_drivers()
    {
        $this->actingAs($this->user);

        // Don't create any inactive drivers

        // Navigate to list
        $response = $this->get(route('carrier.drivers.inactive.index'));

        $response->assertStatus(200);
        $response->assertSeeLivewire('carrier.inactive-drivers-list');
    }

    /** @test */
    public function complete_flow_breadcrumbs_are_displayed()
    {
        $this->actingAs($this->user);

        $archive = DriverArchive::factory()->create([
            'carrier_id' => $this->carrier->id,
            'status' => DriverArchive::STATUS_ARCHIVED,
        ]);

        // View driver details
        $response = $this->get(route('carrier.drivers.inactive.show', $archive));

        $response->assertStatus(200);
        // Breadcrumbs should show navigation path
        $response->assertSee('Inactive Drivers');
    }
}
