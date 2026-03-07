<?php

namespace Tests\Unit\Livewire\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Carrier;
use App\Models\Membership;
use App\Livewire\Admin\DashboardStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

class DashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    protected $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->superAdmin = User::factory()->create();
        Role::create(['name' => 'superadmin']);
        $this->superAdmin->assignRole('superadmin');
    }

    /** @test */
    public function dashboard_stats_component_can_be_rendered()
    {
        $component = Livewire::test(DashboardStats::class);

        $component->assertStatus(200);
    }

    /** @test */
    public function dashboard_stats_loads_carriers_count()
    {
        $membership = Membership::factory()->create();
        Carrier::factory()->count(5)->create(['id_plan' => $membership->id]);

        $component = Livewire::test(DashboardStats::class);

        $component->assertSet('totalCarriers', 5);
    }

    /** @test */
    public function dashboard_stats_has_recent_carriers()
    {
        $membership = Membership::factory()->create();
        Carrier::factory()->count(3)->create(['id_plan' => $membership->id]);

        $component = Livewire::test(DashboardStats::class);

        $recentCarriers = $component->get('recentCarriers');
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $recentCarriers);
        $this->assertCount(3, $recentCarriers);
    }

    /** @test */
    public function dashboard_stats_has_chart_data()
    {
        $membership = Membership::factory()->create();
        Carrier::factory()->count(5)->create(['id_plan' => $membership->id]);

        $component = Livewire::test(DashboardStats::class);

        $chartData = $component->get('chartData');
        $this->assertIsArray($chartData);
        $this->assertArrayHasKey('label', $chartData);
        $this->assertArrayHasKey('values', $chartData);
    }

    /** @test */
    public function dashboard_stats_loads_vehicles_data()
    {
        $membership = Membership::factory()->create();
        $carrier = Carrier::factory()->create(['id_plan' => $membership->id]);
        \App\Models\Admin\Vehicle\Vehicle::factory()->count(5)->create(['carrier_id' => $carrier->id]);

        $component = Livewire::test(DashboardStats::class);

        $component->assertSet('totalVehicles', 5);
    }

    /** @test */
    public function dashboard_stats_sets_default_date_range()
    {
        $component = Livewire::test(DashboardStats::class);

        $component->assertSet('dateRange', 'daily');
        $this->assertNotNull($component->get('customDateStart'));
        $this->assertNotNull($component->get('customDateEnd'));
    }

    /** @test */
    public function dashboard_stats_updates_date_range()
    {
        $component = Livewire::test(DashboardStats::class);

        $component->set('dateRange', 'weekly');

        $component->assertSet('dateRange', 'weekly');
    }

    /** @test */
    public function dashboard_stats_shows_custom_date_filter_when_selected()
    {
        $component = Livewire::test(DashboardStats::class);

        $component->set('dateRange', 'custom');

        $component->assertSet('showCustomDateFilter', true);
    }

    /** @test */
    public function dashboard_stats_hides_custom_date_filter_when_not_custom()
    {
        $component = Livewire::test(DashboardStats::class);

        $component->set('dateRange', 'weekly');

        $component->assertSet('showCustomDateFilter', false);
    }
}
