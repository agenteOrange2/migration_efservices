<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Carrier;
use App\Models\Membership;
use App\Models\Admin\Driver\DriverTraining;
use App\Http\Controllers\Admin\TrainingsController;
use App\Http\Controllers\Admin\TrainingAssignmentsController;

class TrainingControllerTest extends AdminTestCase
{
    protected $carrier;

    protected function setUp(): void
    {
        parent::setUp();

        $membership = Membership::factory()->create();
        $this->carrier = Carrier::factory()->create(['id_plan' => $membership->id]);
    }

    /** @test */
    public function superadmin_can_access_trainings_index()
    {
        $response = $this->actingAsSuperAdmin()
            ->get(route('trainings.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.trainings.index');
    }

    /** @test */
    public function superadmin_can_view_training_create_form()
    {
        $response = $this->actingAsSuperAdmin()
            ->get(route('trainings.create'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.trainings.create');
    }

    /** @test */
    public function superadmin_can_create_training()
    {
        $trainingData = [
            'name' => 'Defensive Driving Course',
            'description' => 'Advanced defensive driving techniques',
            'duration_hours' => 8,
            'validity_period_months' => 12,
            'is_required' => true,
            'status' => 1,
        ];

        $response = $this->actingAsSuperAdmin()
            ->post(route('trainings.store'), $trainingData);

        $response->assertRedirect();
        $this->assertDatabaseHas('admin_driver_trainings', [
            'name' => 'Defensive Driving Course',
        ]);
    }

    /** @test */
    public function training_creation_requires_name()
    {
        $trainingData = [
            'description' => 'Course description',
            'duration_hours' => 4,
            'status' => 1,
        ];

        $response = $this->actingAsSuperAdmin()
            ->post(route('trainings.store'), $trainingData);

        $response->assertSessionHasErrors('name');
    }

    /** @test */
    public function superadmin_can_view_training_details()
    {
        $training = DriverTraining::factory()->create();

        $response = $this->actingAsSuperAdmin()
            ->get(route('trainings.show', $training));

        $response->assertStatus(200);
    }

    /** @test */
    public function superadmin_can_view_training_edit_form()
    {
        $training = DriverTraining::factory()->create();

        $response = $this->actingAsSuperAdmin()
            ->get(route('trainings.edit', $training));

        $response->assertStatus(200);
    }

    /** @test */
    public function superadmin_can_update_training()
    {
        $training = DriverTraining::factory()->create();

        $updateData = [
            'name' => 'Updated Training Name',
            'description' => $training->description,
            'duration_hours' => $training->duration_hours,
            'validity_period_months' => $training->validity_period_months,
            'is_required' => $training->is_required,
            'status' => $training->status,
        ];

        $response = $this->actingAsSuperAdmin()
            ->put(route('trainings.update', $training), $updateData);

        $response->assertRedirect();
        $training->refresh();
        $this->assertEquals('Updated Training Name', $training->name);
    }

    /** @test */
    public function superadmin_can_delete_training()
    {
        $training = DriverTraining::factory()->create();

        $response = $this->actingAsSuperAdmin()
            ->delete(route('trainings.destroy', $training));

        $response->assertRedirect(route('trainings.index'));
        $this->assertDatabaseMissing('admin_driver_trainings', ['id' => $training->id]);
    }

    /** @test */
    public function superadmin_can_access_training_dashboard()
    {
        $response = $this->actingAsSuperAdmin()
            ->get(route('training-dashboard.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function superadmin_can_view_training_assignments_index()
    {
        $response = $this->actingAsSuperAdmin()
            ->get(route('training-assignments.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function superadmin_can_view_training_assignment_form()
    {
        $training = DriverTraining::factory()->create();

        $response = $this->actingAsSuperAdmin()
            ->get(route('trainings.assign.form', $training));

        $response->assertStatus(200);
    }

    /** @test */
    public function guest_cannot_access_trainings()
    {
        $response = $this->get(route('trainings.index'));

        $response->assertRedirect('/login');
    }
}
