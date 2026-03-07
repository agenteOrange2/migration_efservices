<?php

namespace Tests\Unit\Livewire\Carrier;

use App\Livewire\Carrier\InactiveDriversList;
use App\Models\Carrier;
use App\Models\DriverArchive;
use App\Models\User;
use App\Models\UserCarrierDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InactiveDriversListTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Carrier $carrier;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a carrier
        $this->carrier = Carrier::factory()->create();

        // Create a user with carrier details
        $this->user = User::factory()->create();
        UserCarrierDetail::factory()->create([
            'user_id' => $this->user->id,
            'carrier_id' => $this->carrier->id,
        ]);
    }

    /** @test */
    public function component_can_be_rendered()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(InactiveDriversList::class);

        $component->assertStatus(200);
    }

    /** @test */
    public function component_displays_inactive_drivers_for_carrier()
    {
        $this->actingAs($this->user);

        // Create archived drivers for this carrier
        $archive1 = DriverArchive::factory()->create([
            'carrier_id' => $this->carrier->id,
            'status' => DriverArchive::STATUS_ARCHIVED,
            'driver_data_snapshot' => [
                'name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john.doe@example.com',
            ],
        ]);

        $archive2 = DriverArchive::factory()->create([
            'carrier_id' => $this->carrier->id,
            'status' => DriverArchive::STATUS_ARCHIVED,
            'driver_data_snapshot' => [
                'name' => 'Jane',
                'last_name' => 'Smith',
                'email' => 'jane.smith@example.com',
            ],
        ]);

        // Create an archive for a different carrier (should not be displayed)
        $otherCarrier = Carrier::factory()->create();
        DriverArchive::factory()->create([
            'carrier_id' => $otherCarrier->id,
            'status' => DriverArchive::STATUS_ARCHIVED,
        ]);

        $component = Livewire::test(InactiveDriversList::class);

        $inactiveDrivers = $component->get('inactiveDrivers');

        // Should only show 2 drivers from this carrier
        $this->assertEquals(2, $inactiveDrivers->total());
    }

    /** @test */
    public function component_filters_by_search_term()
    {
        $this->actingAs($this->user);

        DriverArchive::factory()->create([
            'carrier_id' => $this->carrier->id,
            'status' => DriverArchive::STATUS_ARCHIVED,
            'driver_data_snapshot' => [
                'name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john.doe@example.com',
            ],
        ]);

        DriverArchive::factory()->create([
            'carrier_id' => $this->carrier->id,
            'status' => DriverArchive::STATUS_ARCHIVED,
            'driver_data_snapshot' => [
                'name' => 'Jane',
                'last_name' => 'Smith',
                'email' => 'jane.smith@example.com',
            ],
        ]);

        $component = Livewire::test(InactiveDriversList::class)
            ->set('search', 'John');

        $inactiveDrivers = $component->get('inactiveDrivers');

        // Should only show John Doe
        $this->assertEquals(1, $inactiveDrivers->total());
    }

    /** @test */
    public function component_filters_by_date_range()
    {
        $this->actingAs($this->user);

        DriverArchive::factory()->create([
            'carrier_id' => $this->carrier->id,
            'status' => DriverArchive::STATUS_ARCHIVED,
            'archived_at' => now()->subDays(10),
        ]);

        DriverArchive::factory()->create([
            'carrier_id' => $this->carrier->id,
            'status' => DriverArchive::STATUS_ARCHIVED,
            'archived_at' => now()->subDays(5),
        ]);

        DriverArchive::factory()->create([
            'carrier_id' => $this->carrier->id,
            'status' => DriverArchive::STATUS_ARCHIVED,
            'archived_at' => now()->subDays(1),
        ]);

        $component = Livewire::test(InactiveDriversList::class)
            ->set('dateFrom', now()->subDays(6)->format('Y-m-d'))
            ->set('dateTo', now()->format('Y-m-d'));

        $inactiveDrivers = $component->get('inactiveDrivers');

        // Should show 2 drivers within the date range
        $this->assertEquals(2, $inactiveDrivers->total());
    }

    /** @test */
    public function component_sorts_by_field()
    {
        $this->actingAs($this->user);

        DriverArchive::factory()->create([
            'carrier_id' => $this->carrier->id,
            'status' => DriverArchive::STATUS_ARCHIVED,
            'archived_at' => now()->subDays(5),
        ]);

        DriverArchive::factory()->create([
            'carrier_id' => $this->carrier->id,
            'status' => DriverArchive::STATUS_ARCHIVED,
            'archived_at' => now()->subDays(10),
        ]);

        $component = Livewire::test(InactiveDriversList::class)
            ->call('sortBy', 'archived_at');

        // Should toggle to ascending
        $component->assertSet('sortDirection', 'asc');
    }

    /** @test */
    public function component_clears_filters()
    {
        $this->actingAs($this->user);

        $component = Livewire::test(InactiveDriversList::class)
            ->set('search', 'test')
            ->set('dateFrom', now()->format('Y-m-d'))
            ->set('dateTo', now()->format('Y-m-d'))
            ->call('clearFilters');

        $component->assertSet('search', '');
        $component->assertSet('dateFrom', null);
        $component->assertSet('dateTo', null);
    }

    /** @test */
    public function component_paginates_results()
    {
        $this->actingAs($this->user);

        // Create 20 archived drivers
        DriverArchive::factory()->count(20)->create([
            'carrier_id' => $this->carrier->id,
            'status' => DriverArchive::STATUS_ARCHIVED,
        ]);

        $component = Livewire::test(InactiveDriversList::class);

        $inactiveDrivers = $component->get('inactiveDrivers');

        // Should paginate to 15 per page
        $this->assertEquals(15, $inactiveDrivers->perPage());
        $this->assertEquals(20, $inactiveDrivers->total());
    }

    /** @test */
    public function component_resets_page_when_search_updated()
    {
        $this->actingAs($this->user);

        DriverArchive::factory()->count(20)->create([
            'carrier_id' => $this->carrier->id,
            'status' => DriverArchive::STATUS_ARCHIVED,
        ]);

        $component = Livewire::test(InactiveDriversList::class);
        
        // Navigate to page 2
        $component->call('gotoPage', 2);
        
        // Update search - this should reset to page 1
        $component->set('search', 'test');

        // Verify we're back on page 1 by checking the paginator
        $inactiveDrivers = $component->get('inactiveDrivers');
        $this->assertEquals(1, $inactiveDrivers->currentPage());
    }
}
