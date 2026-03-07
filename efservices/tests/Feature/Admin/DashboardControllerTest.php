<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Carrier;
use App\Models\Membership;
use App\Models\UserDriverDetail;

class DashboardControllerTest extends AdminTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function superadmin_can_access_dashboard()
    {
        $response = $this->actingAsSuperAdmin()
            ->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard');
    }

    /** @test */
    public function authenticated_user_can_access_dashboard()
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $response = $this->actingAs($user)
            ->get(route('dashboard'));

        $response->assertStatus(200);
    }

    /** @test */
    public function guest_cannot_access_dashboard()
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect('/login');
    }

    /** @test */
    public function dashboard_displays_statistics()
    {
        Carrier::factory()->count(5)->create();
        UserDriverDetail::factory()->count(10)->create();

        $response = $this->actingAsSuperAdmin()
            ->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewHasAll([
            'statistics',
            'chartData',
            'recentRecords',
            'systemAlerts',
            'performanceMetrics'
        ]);
    }

    /** @test */
    public function dashboard_works_with_date_range_filter()
    {
        $response = $this->actingAsSuperAdmin()
            ->get(route('dashboard', ['date_range' => 'monthly']));

        $response->assertStatus(200);
    }

    /** @test */
    public function dashboard_works_with_custom_date_range()
    {
        $response = $this->actingAsSuperAdmin()
            ->get(route('dashboard', [
                'date_range' => 'custom',
                'custom_date_start' => now()->startOfMonth()->format('Y-m-d'),
                'custom_date_end' => now()->endOfMonth()->format('Y-m-d')
            ]));

        $response->assertStatus(200);
    }

    /** @test */
    public function dashboard_ajax_update_works()
    {
        $response = $this->actingAsSuperAdmin()
            ->post(route('dashboard.ajax-update'));

        $response->assertStatus(200);
        $response->assertJsonStructure(['statistics', 'chartData']);
    }

    /** @test */
    public function dashboard_export_pdf_works()
    {
        $response = $this->actingAsSuperAdmin()
            ->post(route('dashboard.export-pdf'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }
}
