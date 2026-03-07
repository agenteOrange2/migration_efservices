<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Carrier;
use App\Models\UserCarrierDetail;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Admin\Vehicle\VehicleDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CarrierVehicleDocumentsOverviewTest extends TestCase
{
    use RefreshDatabase;

    protected $carrier;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'user_carrier']);

        // Create a carrier with approved banking
        $this->carrier = Carrier::factory()->create([
            'status' => Carrier::STATUS_ACTIVE,
            'documents_completed' => true
        ]);

        // Create approved banking details
        \App\Models\CarrierBankingDetail::create([
            'carrier_id' => $this->carrier->id,
            'status' => 'approved',
            'account_holder_name' => encrypt('Test Holder'),
            'account_number' => encrypt('1234567890'),
            'banking_routing_number' => encrypt('987654321')
        ]);

        // Create a user with carrier details
        $this->user = User::factory()->create([
            'status' => 1
        ]);
        
        // Assign carrier role to user
        $this->user->assignRole('user_carrier');
        
        UserCarrierDetail::factory()->create([
            'user_id' => $this->user->id,
            'carrier_id' => $this->carrier->id,
            'status' => 1
        ]);
    }

    /** @test */
    public function it_calculates_summary_statistics_correctly()
    {
        // Create vehicles with documents
        $vehicle1 = Vehicle::factory()->create(['carrier_id' => $this->carrier->id]);
        $vehicle2 = Vehicle::factory()->create(['carrier_id' => $this->carrier->id]);

        // Create documents with different statuses
        VehicleDocument::factory()->create([
            'vehicle_id' => $vehicle1->id,
            'status' => 'active',
        ]);
        VehicleDocument::factory()->create([
            'vehicle_id' => $vehicle1->id,
            'status' => 'active',
        ]);
        VehicleDocument::factory()->create([
            'vehicle_id' => $vehicle2->id,
            'status' => 'expired',
        ]);
        VehicleDocument::factory()->create([
            'vehicle_id' => $vehicle2->id,
            'status' => 'pending',
        ]);

        // Act as the carrier user and visit the overview page
        $response = $this->actingAs($this->user)->get(route('carrier.vehicles-documents.index'));

        // Assert the response is successful
        $response->assertStatus(200);

        // Assert statistics are passed to the view
        $response->assertViewHas('statistics');
        $statistics = $response->viewData('statistics');
        
        $this->assertEquals(2, $statistics['active']);
        $this->assertEquals(1, $statistics['expired']);
        $this->assertEquals(1, $statistics['pending']);
        $this->assertEquals(4, $statistics['total']);
    }

    /** @test */
    public function it_only_counts_documents_for_carrier_vehicles()
    {
        // Create vehicles for this carrier
        $vehicle1 = Vehicle::factory()->create(['carrier_id' => $this->carrier->id]);
        
        // Create documents for this carrier's vehicle
        VehicleDocument::factory()->create([
            'vehicle_id' => $vehicle1->id,
            'status' => 'active',
        ]);

        // Create another carrier with vehicles and documents
        $otherCarrier = Carrier::factory()->create();
        $otherVehicle = Vehicle::factory()->create(['carrier_id' => $otherCarrier->id]);
        VehicleDocument::factory()->create([
            'vehicle_id' => $otherVehicle->id,
            'status' => 'active',
        ]);
        VehicleDocument::factory()->create([
            'vehicle_id' => $otherVehicle->id,
            'status' => 'expired',
        ]);

        // Act as the carrier user and visit the overview page
        $response = $this->actingAs($this->user)->get(route('carrier.vehicles-documents.index'));

        // Assert only this carrier's documents are counted
        $response->assertViewHas('statistics');
        $statistics = $response->viewData('statistics');
        
        $this->assertEquals(1, $statistics['active']);
        $this->assertEquals(0, $statistics['expired']);
        $this->assertEquals(0, $statistics['pending']);
        $this->assertEquals(1, $statistics['total']);
    }

    /** @test */
    public function it_returns_zero_counts_when_no_documents_exist()
    {
        // Create a vehicle but no documents
        Vehicle::factory()->create(['carrier_id' => $this->carrier->id]);

        // Act as the carrier user and visit the overview page
        $response = $this->actingAs($this->user)->get(route('carrier.vehicles-documents.index'));

        // Assert all counts are zero
        $response->assertViewHas('statistics');
        $statistics = $response->viewData('statistics');
        
        $this->assertEquals(0, $statistics['active']);
        $this->assertEquals(0, $statistics['expired']);
        $this->assertEquals(0, $statistics['pending']);
        $this->assertEquals(0, $statistics['total']);
    }

    /** @test */
    public function it_paginates_vehicles_with_10_per_page()
    {
        // Create 15 vehicles for this carrier
        Vehicle::factory()->count(15)->create(['carrier_id' => $this->carrier->id]);

        // Act as the carrier user and visit the overview page
        $response = $this->actingAs($this->user)->get(route('carrier.vehicles-documents.index'));

        // Assert the response is successful
        $response->assertStatus(200);

        // Assert vehicles are paginated
        $response->assertViewHas('vehicles');
        $vehicles = $response->viewData('vehicles');
        
        // Should show 10 vehicles on first page
        $this->assertEquals(10, $vehicles->count());
        $this->assertEquals(15, $vehicles->total());
        $this->assertEquals(2, $vehicles->lastPage());
    }

    /** @test */
    public function it_preserves_filters_in_pagination_links()
    {
        // Create 15 vehicles for this carrier
        Vehicle::factory()->count(15)->create([
            'carrier_id' => $this->carrier->id,
            'out_of_service' => false,
            'suspended' => false
        ]);

        // Act as the carrier user with filters
        $response = $this->actingAs($this->user)->get(route('carrier.vehicles-documents.index', [
            'vehicle_status' => 'active',
            'page' => 1
        ]));

        // Assert the response is successful
        $response->assertStatus(200);

        // Assert vehicles are paginated
        $response->assertViewHas('vehicles');
        $vehicles = $response->viewData('vehicles');
        
        // Check that pagination links preserve the filter
        $this->assertStringContainsString('vehicle_status=active', $vehicles->url(2));
    }

    /** @test */
    public function it_shows_correct_page_when_navigating_pagination()
    {
        // Create 25 vehicles for this carrier
        Vehicle::factory()->count(25)->create(['carrier_id' => $this->carrier->id]);

        // Visit page 2
        $response = $this->actingAs($this->user)->get(route('carrier.vehicles-documents.index', ['page' => 2]));

        // Assert the response is successful
        $response->assertStatus(200);

        // Assert we're on page 2
        $response->assertViewHas('vehicles');
        $vehicles = $response->viewData('vehicles');
        
        $this->assertEquals(2, $vehicles->currentPage());
        $this->assertEquals(10, $vehicles->count());
        $this->assertEquals(25, $vehicles->total());
    }

    /** @test */
    public function it_requires_authentication_to_access_overview()
    {
        // Attempt to access without authentication
        $response = $this->get(route('carrier.vehicles-documents.index'));

        // Assert redirect to login
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function it_only_shows_vehicles_belonging_to_authenticated_carrier()
    {
        // Create vehicles for this carrier
        $vehicle1 = Vehicle::factory()->create(['carrier_id' => $this->carrier->id]);
        $vehicle2 = Vehicle::factory()->create(['carrier_id' => $this->carrier->id]);
        
        // Create another carrier with vehicles
        $otherCarrier = Carrier::factory()->create(['status' => Carrier::STATUS_ACTIVE]);
        $otherVehicle1 = Vehicle::factory()->create(['carrier_id' => $otherCarrier->id]);
        $otherVehicle2 = Vehicle::factory()->create(['carrier_id' => $otherCarrier->id]);
        $otherVehicle3 = Vehicle::factory()->create(['carrier_id' => $otherCarrier->id]);

        // Act as the carrier user and visit the overview page
        $response = $this->actingAs($this->user)->get(route('carrier.vehicles-documents.index'));

        // Assert the response is successful
        $response->assertStatus(200);

        // Assert only this carrier's vehicles are shown
        $response->assertViewHas('vehicles');
        $vehicles = $response->viewData('vehicles');
        
        // Should only show 2 vehicles (this carrier's vehicles)
        $this->assertEquals(2, $vehicles->total());
        
        // Verify all vehicles belong to this carrier
        foreach ($vehicles as $vehicle) {
            $this->assertEquals($this->carrier->id, $vehicle->carrier_id);
        }
    }

    /** @test */
    public function it_prevents_cross_carrier_data_access()
    {
        // Create another carrier with vehicles and documents
        $otherCarrier = Carrier::factory()->create(['status' => Carrier::STATUS_ACTIVE]);
        $otherVehicle = Vehicle::factory()->create(['carrier_id' => $otherCarrier->id]);
        VehicleDocument::factory()->count(5)->create([
            'vehicle_id' => $otherVehicle->id,
            'status' => 'active',
        ]);

        // Create vehicles for this carrier (no documents)
        Vehicle::factory()->create(['carrier_id' => $this->carrier->id]);

        // Act as the carrier user and visit the overview page
        $response = $this->actingAs($this->user)->get(route('carrier.vehicles-documents.index'));

        // Assert the response is successful
        $response->assertStatus(200);

        // Assert statistics only count this carrier's documents (should be 0)
        $response->assertViewHas('statistics');
        $statistics = $response->viewData('statistics');
        
        $this->assertEquals(0, $statistics['active']);
        $this->assertEquals(0, $statistics['expired']);
        $this->assertEquals(0, $statistics['pending']);
        $this->assertEquals(0, $statistics['total']);
    }

    /** @test */
    public function it_enforces_carrier_id_filter_at_query_level()
    {
        // Create vehicles for this carrier
        $vehicle1 = Vehicle::factory()->create(['carrier_id' => $this->carrier->id]);
        
        // Create another carrier with many vehicles
        $otherCarrier = Carrier::factory()->create(['status' => Carrier::STATUS_ACTIVE]);
        Vehicle::factory()->count(20)->create(['carrier_id' => $otherCarrier->id]);

        // Act as the carrier user and visit the overview page
        $response = $this->actingAs($this->user)->get(route('carrier.vehicles-documents.index'));

        // Assert the response is successful
        $response->assertStatus(200);

        // Assert only 1 vehicle is returned (this carrier's vehicle)
        $response->assertViewHas('vehicles');
        $vehicles = $response->viewData('vehicles');
        
        $this->assertEquals(1, $vehicles->total());
        $this->assertEquals($this->carrier->id, $vehicles->first()->carrier_id);
    }
}
