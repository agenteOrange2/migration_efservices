<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Carrier;
use App\Models\UserCarrierDetail;
use App\Models\UserDriverDetail;
use App\Models\Admin\Driver\DriverInspection;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CarrierInspectionsBasicTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function inspection_model_has_new_fields_in_fillable()
    {
        $inspection = new DriverInspection();
        $fillable = $inspection->getFillable();
        
        $this->assertContains('inspection_level', $fillable);
        $this->assertContains('inspector_number', $fillable);
    }

    /** @test */
    public function can_create_inspection_with_new_fields_directly()
    {
        $driver = UserDriverDetail::factory()->create();
        
        $inspection = DriverInspection::create([
            'user_driver_detail_id' => $driver->id,
            'inspection_date' => '2024-01-15',
            'inspection_type' => 'DOT Roadside',
            'inspection_level' => 'Level I',
            'inspector_name' => 'John Inspector',
            'inspector_number' => 'INS-12345',
            'status' => 'Pass',
            'is_vehicle_safe_to_operate' => true,
        ]);

        $this->assertDatabaseHas('driver_inspections', [
            'id' => $inspection->id,
            'inspection_level' => 'Level I',
            'inspector_number' => 'INS-12345',
        ]);
    }

    /** @test */
    public function can_update_inspection_with_new_fields()
    {
        $driver = UserDriverDetail::factory()->create();
        
        $inspection = DriverInspection::factory()->create([
            'user_driver_detail_id' => $driver->id,
            'inspection_level' => 'Level II',
            'inspector_number' => 'OLD-123',
        ]);

        $inspection->update([
            'inspection_level' => 'Level I',
            'inspector_number' => 'NEW-456',
        ]);

        $this->assertDatabaseHas('driver_inspections', [
            'id' => $inspection->id,
            'inspection_level' => 'Level I',
            'inspector_number' => 'NEW-456',
        ]);
    }

    /** @test */
    public function new_fields_are_nullable()
    {
        $driver = UserDriverDetail::factory()->create();
        
        $inspection = DriverInspection::create([
            'user_driver_detail_id' => $driver->id,
            'inspection_date' => '2024-01-15',
            'inspection_type' => 'Pre-trip',
            'inspection_level' => null,
            'inspector_name' => 'John Inspector',
            'inspector_number' => null,
            'status' => 'Pass',
            'is_vehicle_safe_to_operate' => true,
        ]);

        $this->assertDatabaseHas('driver_inspections', [
            'id' => $inspection->id,
            'inspection_level' => null,
            'inspector_number' => null,
        ]);
    }

    /** @test */
    public function controller_validation_includes_new_fields()
    {
        // This test verifies the validation rules exist in the controller
        // by checking the controller source code
        $controllerPath = app_path('Http/Controllers/Carrier/CarrierDriverInspectionsController.php');
        $controllerContent = file_get_contents($controllerPath);
        
        $this->assertStringContainsString("'inspection_level'", $controllerContent);
        $this->assertStringContainsString("'inspector_number'", $controllerContent);
        $this->assertStringContainsString('nullable', $controllerContent);
        $this->assertStringContainsString('max:50', $controllerContent);
    }

    /** @test */
    public function create_view_contains_new_fields()
    {
        $viewPath = resource_path('views/carrier/drivers/inspections/create.blade.php');
        $viewContent = file_get_contents($viewPath);
        
        $this->assertStringContainsString('inspection_level', $viewContent);
        $this->assertStringContainsString('inspector_number', $viewContent);
        $this->assertStringContainsString('Inspection Level', $viewContent);
        $this->assertStringContainsString('Inspector Number', $viewContent);
    }

    /** @test */
    public function edit_view_contains_new_fields()
    {
        $viewPath = resource_path('views/carrier/drivers/inspections/edit.blade.php');
        $viewContent = file_get_contents($viewPath);
        
        $this->assertStringContainsString('inspection_level', $viewContent);
        $this->assertStringContainsString('inspector_number', $viewContent);
        $this->assertStringContainsString('Inspection Level', $viewContent);
        $this->assertStringContainsString('Inspector Number', $viewContent);
    }
}
