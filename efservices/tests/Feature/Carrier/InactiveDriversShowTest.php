<?php

namespace Tests\Feature\Carrier;

use App\Models\Carrier;
use App\Models\DriverArchive;
use App\Models\User;
use App\Models\UserCarrierDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InactiveDriversShowTest extends TestCase
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
            'documents_completed' => true, // Mark documents as completed
        ]);

        // Create banking details for the carrier (required by CheckCarrierStatus middleware)
        \App\Models\CarrierBankingDetail::create([
            'carrier_id' => $this->carrier->id,
            'status' => 'approved', // Banking must be approved
            'account_holder_name' => 'Test Holder',
            'account_number' => '1234567890',
            'banking_routing_number' => '123456789',
        ]);

        // Create a user with carrier details
        $this->user = User::factory()->create([
            'status' => 1, // Active status
        ]);
        $this->user->assignRole('user_carrier');
        
        UserCarrierDetail::factory()->create([
            'user_id' => $this->user->id,
            'carrier_id' => $this->carrier->id,
            'status' => 1, // Active status
        ]);
    }

    /** @test */
    public function carrier_can_view_their_own_inactive_driver_details()
    {
        $this->actingAs($this->user);

        // Create an archived driver for this carrier
        $archive = DriverArchive::factory()->create([
            'carrier_id' => $this->carrier->id,
            'status' => DriverArchive::STATUS_ARCHIVED,
        ]);

        $response = $this->get(route('carrier.drivers.inactive.show', $archive));

        $response->assertStatus(200);
        $response->assertViewIs('carrier.drivers.inactive.show');
        $response->assertViewHas('archive');
        $response->assertSee($archive->full_name);
    }

    /** @test */
    public function carrier_cannot_view_another_carriers_inactive_driver()
    {
        $this->actingAs($this->user);

        // Create another carrier
        $otherCarrier = Carrier::factory()->create();

        // Create an archived driver for the other carrier
        $archive = DriverArchive::factory()->create([
            'carrier_id' => $otherCarrier->id,
            'status' => DriverArchive::STATUS_ARCHIVED,
        ]);

        $response = $this->get(route('carrier.drivers.inactive.show', $archive));

        $response->assertStatus(403);
    }

    /** @test */
    public function show_view_displays_all_tabs()
    {
        $this->actingAs($this->user);

        $archive = DriverArchive::factory()->create([
            'carrier_id' => $this->carrier->id,
            'status' => DriverArchive::STATUS_ARCHIVED,
        ]);

        $response = $this->get(route('carrier.drivers.inactive.show', $archive));

        $response->assertStatus(200);
        
        // Check that all tab buttons are present
        $response->assertSee('Personal Information');
        $response->assertSee('Employment');
        $response->assertSee('Licenses');
        $response->assertSee('Medical');
        $response->assertSee('Certifications', false); // Check without escaping
        $response->assertSee('Training', false);
        $response->assertSee('Testing');
        $response->assertSee('Accidents', false); // Check without escaping
        $response->assertSee('Violations', false);
        $response->assertSee('Inspections');
        $response->assertSee('Vehicle Assignments');
        $response->assertSee('Documents');
    }

    /** @test */
    public function show_view_displays_archive_banner()
    {
        $this->actingAs($this->user);

        $archive = DriverArchive::factory()->create([
            'carrier_id' => $this->carrier->id,
            'status' => DriverArchive::STATUS_ARCHIVED,
            'archive_reason' => DriverArchive::REASON_MIGRATION,
        ]);

        $response = $this->get(route('carrier.drivers.inactive.show', $archive));

        $response->assertStatus(200);
        $response->assertSee('Archived Driver Record');
        $response->assertSee('historical, read-only record');
    }

    /** @test */
    public function show_view_displays_download_button()
    {
        $this->actingAs($this->user);

        $archive = DriverArchive::factory()->create([
            'carrier_id' => $this->carrier->id,
            'status' => DriverArchive::STATUS_ARCHIVED,
        ]);

        $response = $this->get(route('carrier.drivers.inactive.show', $archive));

        $response->assertStatus(200);
        $response->assertSee('Download Archive', false);
    }

    /** @test */
    public function show_view_displays_personal_information()
    {
        $this->actingAs($this->user);

        $archive = DriverArchive::factory()->create([
            'carrier_id' => $this->carrier->id,
            'status' => DriverArchive::STATUS_ARCHIVED,
            'driver_data_snapshot' => [
                'name' => 'John',
                'middle_name' => 'Michael',
                'last_name' => 'Doe',
                'email' => 'john.doe@example.com',
                'phone' => '555-1234',
            ],
        ]);

        $response = $this->get(route('carrier.drivers.inactive.show', $archive));

        $response->assertStatus(200);
        $response->assertSee('john.doe@example.com');
        $response->assertSee('555-1234');
    }

    /** @test */
    public function unauthenticated_user_cannot_access_show_view()
    {
        $archive = DriverArchive::factory()->create([
            'carrier_id' => $this->carrier->id,
            'status' => DriverArchive::STATUS_ARCHIVED,
        ]);

        $response = $this->get(route('carrier.drivers.inactive.show', $archive));

        $response->assertRedirect(route('login'));
    }
}
