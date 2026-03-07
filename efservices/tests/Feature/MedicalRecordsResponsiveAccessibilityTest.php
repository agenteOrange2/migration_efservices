<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\UserDriverDetail;
use App\Models\DriverMedicalQualification;
use App\Models\Carrier;
use Spatie\Permission\Models\Role;

class MedicalRecordsResponsiveAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create superadmin role if it doesn't exist
        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        
        // Create admin user with superadmin role
        $this->admin = User::factory()->create([
            'email' => 'admin@test.com',
        ]);
        
        // Assign superadmin role
        $this->admin->assignRole('superadmin');
    }

    /** @test */
    public function filter_cards_have_correct_responsive_classes()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.medical-records.index'));

        $response->assertStatus(200);
        
        // Verify each card has responsive grid classes
        // col-span-4 (mobile), md:col-span-2 (medium), xl:col-span-1 (extra-large)
        $response->assertSee('col-span-4', false);
        $response->assertSee('md:col-span-2', false);
        $response->assertSee('xl:col-span-1', false);
    }

    /** @test */
    public function filter_cards_are_keyboard_accessible()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.medical-records.index'));

        $response->assertStatus(200);
        
        // Verify all filter cards are anchor tags (keyboard navigable)
        $content = $response->getContent();
        
        // Check for anchor tags with proper href attributes
        $this->assertStringContainsString('href="' . route('admin.medical-records.index', ['tab' => 'all']), $content);
        $this->assertStringContainsString('href="' . route('admin.medical-records.index', ['tab' => 'active']), $content);
        $this->assertStringContainsString('href="' . route('admin.medical-records.index', ['tab' => 'expiring']), $content);
        $this->assertStringContainsString('href="' . route('admin.medical-records.index', ['tab' => 'expired']), $content);
    }

    /** @test */
    public function status_badges_have_proper_positioning_classes()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.medical-records.index'));

        $response->assertStatus(200);
        
        // Verify badges use absolute positioning with proper classes
        $response->assertSee('absolute inset-y-0 right-0', false);
        $response->assertSee('flex flex-col justify-center', false);
    }

    /** @test */
    public function filter_cards_have_hover_and_transition_effects()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.medical-records.index'));

        $response->assertStatus(200);
        
        // Verify hover classes
        $response->assertSee('hover:border-primary/60', false);
        $response->assertSee('hover:bg-primary/5', false);
        
        // Verify transition classes
        $response->assertSee('transition-all duration-150 ease-in-out', false);
        
        // Verify cursor pointer
        $response->assertSee('cursor-pointer', false);
    }

    /** @test */
    public function active_tab_has_proper_styling()
    {
        // Test with 'all' tab (default)
        $response = $this->actingAs($this->admin)->get(route('admin.medical-records.index', ['tab' => 'all']));
        $response->assertStatus(200);
        $response->assertSee('border-primary/80 bg-primary/5', false);
        
        // Test with 'active' tab
        $response = $this->actingAs($this->admin)->get(route('admin.medical-records.index', ['tab' => 'active']));
        $response->assertStatus(200);
        $response->assertSee('border-primary/80 bg-primary/5', false);
    }

    /** @test */
    public function inactive_tabs_have_proper_styling()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.medical-records.index', ['tab' => 'all']));
        
        $response->assertStatus(200);
        
        // Inactive cards should have slate border
        $response->assertSee('border-slate-300/80', false);
    }

    /** @test */
    public function filter_cards_preserve_query_parameters()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.medical-records.index', [
            'search_term' => 'test',
            'carrier_filter' => '1',
            'tab' => 'all'
        ]));

        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // Verify that links preserve search_term and carrier_filter
        $this->assertStringContainsString('search_term=test', $content);
        $this->assertStringContainsString('carrier_filter=1', $content);
    }

    /** @test */
    public function all_four_filter_cards_are_rendered()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.medical-records.index'));

        $response->assertStatus(200);
        
        // Verify all four card titles are present
        $response->assertSee('Total Records');
        $response->assertSee('Active Records');
        $response->assertSee('Expiring Soon');
        $response->assertSee('Expired');
    }

    /** @test */
    public function filter_cards_display_correct_icons()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.medical-records.index'));

        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // Verify Lucide icon components are present in the filter cards
        // The icons may be rendered as SVG or data attributes, so we check for the icon names
        $this->assertStringContainsString('file-text', $content);  // Total Records
        $this->assertStringContainsString('check-circle', $content);  // Active Records  
        $this->assertStringContainsString('clock', $content);  // Expiring Soon
        $this->assertStringContainsString('alert-circle', $content);  // Expired
    }

    /** @test */
    public function filter_cards_have_proper_badge_colors()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.medical-records.index'));

        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // Success badges (green) for Total and Active
        $this->assertStringContainsString('border-success/10 bg-success/10', $content);
        $this->assertStringContainsString('text-success', $content);
        
        // Warning badge (yellow) for Expiring Soon
        $this->assertStringContainsString('border-warning/10 bg-warning/10', $content);
        $this->assertStringContainsString('text-warning', $content);
        
        // Danger badge (red) for Expired
        $this->assertStringContainsString('border-danger/10 bg-danger/10', $content);
        $this->assertStringContainsString('text-danger', $content);
    }

    /** @test */
    public function grid_container_has_proper_responsive_layout()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.medical-records.index'));

        $response->assertStatus(200);
        
        // Verify grid container has 4 columns with gap
        $response->assertSee('grid grid-cols-4 gap-5', false);
    }

    /** @test */
    public function filter_cards_have_relative_positioning_for_badges()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.medical-records.index'));

        $response->assertStatus(200);
        
        // Cards need relative positioning for absolute badge positioning
        $response->assertSee('relative', false);
    }
}
