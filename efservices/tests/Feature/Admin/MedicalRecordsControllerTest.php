<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Carrier;
use App\Models\Membership;
use App\Models\UserDriverDetail;
use App\Models\Admin\Driver\DriverLicense;
use App\Models\Admin\Driver\DriverMedicalQualification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MedicalRecordsControllerTest extends AdminTestCase
{
    protected $carrier;
    protected $driver;

    protected function setUp(): void
    {
        parent::setUp();

        $membership = Membership::factory()->create();
        $this->carrier = Carrier::factory()->create(['id_plan' => $membership->id]);
        $this->driver = UserDriverDetail::factory()->create([
            'carrier_id' => $this->carrier->id
        ]);
    }

    /** @test */
    public function superadmin_can_access_medical_records_index()
    {
        DriverMedicalQualification::factory()->count(5)->create([
            'user_driver_detail_id' => $this->driver->id
        ]);

        $response = $this->actingAsSuperAdmin()
            ->get(route('medical-records.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.medical-records.index');
    }

    /** @test */
    public function superadmin_can_view_medical_record_create_form()
    {
        $response = $this->actingAsSuperAdmin()
            ->get(route('medical-records.create'));

        $response->assertStatus(200);
    }

    /** @test */
    public function superadmin_can_create_medical_record()
    {
        $medicalData = [
            'user_driver_detail_id' => $this->driver->id,
            'exam_date' => now()->format('Y-m-d'),
            'expiration_date' => now()->addYears(2)->format('Y-m-d'),
            'result' => 'Qualified',
            'examination_type' => 'DOT Physical',
            'examiner_name' => 'Dr. John Smith',
            'status' => 1,
        ];

        $response = $this->actingAsSuperAdmin()
            ->post(route('medical-records.store'), $medicalData);

        $response->assertRedirect();
        $this->assertDatabaseHas('admin_driver_medical_qualifications', [
            'user_driver_detail_id' => $this->driver->id,
            'result' => 'Qualified',
        ]);
    }

    /** @test */
    public function medical_record_creation_requires_driver()
    {
        $medicalData = [
            'exam_date' => now()->format('Y-m-d'),
            'expiration_date' => now()->addYears(2)->format('Y-m-d'),
            'result' => 'Qualified',
            'status' => 1,
        ];

        $response = $this->actingAsSuperAdmin()
            ->post(route('medical-records.store'), $medicalData);

        $response->assertSessionHasErrors('user_driver_detail_id');
    }

    /** @test */
    public function superadmin_can_view_medical_record_details()
    {
        $medical = DriverMedicalQualification::factory()->create([
            'user_driver_detail_id' => $this->driver->id
        ]);

        $response = $this->actingAsSuperAdmin()
            ->get(route('medical-records.show', $medical));

        $response->assertStatus(200);
    }

    /** @test */
    public function superadmin_can_edit_medical_record()
    {
        $medical = DriverMedicalQualification::factory()->create([
            'user_driver_detail_id' => $this->driver->id,
            'result' => 'Pending'
        ]);

        $updateData = [
            'user_driver_detail_id' => $this->driver->id,
            'exam_date' => $medical->exam_date->format('Y-m-d'),
            'expiration_date' => $medical->expiration_date->format('Y-m-d'),
            'result' => 'Qualified',
            'examination_type' => $medical->examination_type,
            'examiner_name' => $medical->examiner_name,
            'status' => $medical->status,
        ];

        $response = $this->actingAsSuperAdmin()
            ->put(route('medical-records.update', $medical), $updateData);

        $response->assertRedirect();
        $medical->refresh();
        $this->assertEquals('Qualified', $medical->result);
    }

    /** @test */
    public function superadmin_can_delete_medical_record()
    {
        $medical = DriverMedicalQualification::factory()->create([
            'user_driver_detail_id' => $this->driver->id
        ]);

        $response = $this->actingAsSuperAdmin()
            ->delete(route('medical-records.destroy', $medical));

        $response->assertRedirect(route('medical-records.index'));
        $this->assertDatabaseMissing('admin_driver_medical_qualifications', ['id' => $medical->id]);
    }

    /** @test */
    public function superadmin_can_contact_driver_about_medical_record()
    {
        Storage::fake('public');
        
        $medical = DriverMedicalQualification::factory()->create([
            'user_driver_detail_id' => $this->driver->id,
            'expiration_date' => now()->addDays(30)->format('Y-m-d')
        ]);

        $contactData = [
            'message' => 'Your medical certificate will expire soon. Please schedule a new exam.',
        ];

        $response = $this->actingAsSuperAdmin()
            ->post(route('medical-records.send-contact', $medical), $contactData);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    /** @test */
    public function medical_records_index_filters_by_carrier()
    {
        $otherCarrier = Carrier::factory()->create(['id_plan' => Membership::factory()->create()->id]);
        
        DriverMedicalQualification::factory()->count(3)->create([
            'user_driver_detail_id' => $this->driver->id
        ]);
        
        $otherDriver = UserDriverDetail::factory()->create(['carrier_id' => $otherCarrier->id]);
        DriverMedicalQualification::factory()->count(2)->create([
            'user_driver_detail_id' => $otherDriver->id
        ]);

        $response = $this->actingAsSuperAdmin()
            ->get(route('medical-records.index', ['carrier' => $this->carrier->id]));

        $response->assertStatus(200);
    }

    /** @test */
    public function medical_records_index_shows_expiring_records()
    {
        DriverMedicalQualification::factory()->create([
            'user_driver_detail_id' => $this->driver->id,
            'expiration_date' => now()->addDays(15)->format('Y-m-d')
        ]);

        DriverMedicalQualification::factory()->create([
            'user_driver_detail_id' => $this->driver->id,
            'expiration_date' => now()->addYears(2)->format('Y-m-d')
        ]);

        $response = $this->actingAsSuperAdmin()
            ->get(route('medical-records.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function guest_cannot_access_medical_records()
    {
        $response = $this->get(route('medical-records.index'));

        $response->assertRedirect('/login');
    }
}
