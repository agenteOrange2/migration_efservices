<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Carrier;
use App\Models\Membership;
use App\Models\UserDriverDetail;
use App\Models\Admin\Driver\DriverTesting;
use Barryvdh\DomPDF\Facade\Pdf;

class DriverTestingControllerTest extends AdminTestCase
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
    public function superadmin_can_access_driver_testings_index()
    {
        DriverTesting::factory()->count(10)->create([
            'user_driver_detail_id' => $this->driver->id
        ]);

        $response = $this->actingAsSuperAdmin()
            ->get(route('driver-testings.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.driver-testings.index');
    }

    /** @test */
    public function driver_testings_index_displays_tests()
    {
        DriverTesting::factory()->count(5)->create([
            'user_driver_detail_id' => $this->driver->id
        ]);

        $response = $this->actingAsSuperAdmin()
            ->get(route('driver-testings.index'));

        $response->assertStatus(200);
        $response->assertViewHas('tests');
    }

    /** @test */
    public function superadmin_can_view_driver_testing_create_form()
    {
        $response = $this->actingAsSuperAdmin()
            ->get(route('driver-testings.create'));

        $response->assertStatus(200);
    }

    /** @test */
    public function superadmin_can_create_driver_testing()
    {
        $testingData = [
            'user_driver_detail_id' => $this->driver->id,
            'test_type' => 'Pre-Employment Drug Screen',
            'test_date' => now()->format('Y-m-d'),
            'test_result' => 'Negative',
            'laboratory' => 'LabCorp',
            'specimen_type' => 'Urine',
            'status' => 1,
        ];

        $response = $this->actingAsSuperAdmin()
            ->post(route('driver-testings.store'), $testingData);

        $response->assertRedirect();
        $this->assertDatabaseHas('admin_driver_testings', [
            'user_driver_detail_id' => $this->driver->id,
            'test_result' => 'Negative',
        ]);
    }

    /** @test */
    public function testing_creation_requires_driver()
    {
        $testingData = [
            'test_type' => 'Random Drug Test',
            'test_date' => now()->format('Y-m-d'),
            'test_result' => 'Negative',
            'status' => 1,
        ];

        $response = $this->actingAsSuperAdmin()
            ->post(route('driver-testings.store'), $testingData);

        $response->assertSessionHasErrors('user_driver_detail_id');
    }

    /** @test */
    public function testing_creation_requires_test_result()
    {
        $testingData = [
            'user_driver_detail_id' => $this->driver->id,
            'test_type' => 'Random Drug Test',
            'test_date' => now()->format('Y-m-d'),
            'status' => 1,
        ];

        $response = $this->actingAsSuperAdmin()
            ->post(route('driver-testings.store'), $testingData);

        $response->assertSessionHasErrors('test_result');
    }

    /** @test */
    public function superadmin_can_view_driver_testing_details()
    {
        $testing = DriverTesting::factory()->create([
            'user_driver_detail_id' => $this->driver->id
        ]);

        $response = $this->actingAsSuperAdmin()
            ->get(route('driver-testings.show', $testing));

        $response->assertStatus(200);
    }

    /** @test */
    public function superadmin_can_edit_driver_testing()
    {
        $testing = DriverTesting::factory()->create([
            'user_driver_detail_id' => $this->driver->id,
            'test_result' => 'Pending'
        ]);

        $updateData = [
            'user_driver_detail_id' => $this->driver->id,
            'test_type' => $testing->test_type,
            'test_date' => $testing->test_date->format('Y-m-d'),
            'test_result' => 'Negative',
            'laboratory' => $testing->laboratory,
            'specimen_type' => $testing->specimen_type,
            'status' => $testing->status,
        ];

        $response = $this->actingAsSuperAdmin()
            ->put(route('driver-testings.update', $testing), $updateData);

        $response->assertRedirect();
        $testing->refresh();
        $this->assertEquals('Negative', $testing->test_result);
    }

    /** @test */
    public function superadmin_can_delete_driver_testing()
    {
        $testing = DriverTesting::factory()->create([
            'user_driver_detail_id' => $this->driver->id
        ]);

        $response = $this->actingAsSuperAdmin()
            ->delete(route('driver-testings.destroy', $testing));

        $response->assertRedirect(route('driver-testings.index'));
        $this->assertDatabaseMissing('admin_driver_testings', ['id' => $testing->id]);
    }

    /** @test */
    public function superadmin_can_download_testing_pdf()
    {
        $testing = DriverTesting::factory()->create([
            'user_driver_detail_id' => $this->driver->id
        ]);

        $response = $this->actingAsSuperAdmin()
            ->get(route('driver-testings.download-pdf', $testing));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    /** @test */
    public function superadmin_can_view_testing_documents()
    {
        $testing = DriverTesting::factory()->create([
            'user_driver_detail_id' => $this->driver->id
        ]);

        $response = $this->actingAsSuperAdmin()
            ->get(route('testings.docs', $testing));

        $response->assertStatus(200);
    }

    /** @test */
    public function superadmin_can_upload_testing_documents()
    {
        $testing = DriverTesting::factory()->create([
            'user_driver_detail_id' => $this->driver->id
        ]);

        $file = \Illuminate\Http\UploadedFile::fake()->create('test_report.pdf', 1000, 'application/pdf');

        $response = $this->actingAsSuperAdmin()
            ->post(route('testings.docs.store', $testing), [
                'files' => [$file],
            ]);

        $response->assertRedirect();
    }

    /** @test */
    public function superadmin_can_delete_testing_document()
    {
        $testing = DriverTesting::factory()->create([
            'user_driver_detail_id' => $this->driver->id
        ]);

        $response = $this->actingAsSuperAdmin()
            ->delete(route('testings.docs.destroy', 'test_media_id'));

        $response->assertStatus(404);
    }

    /** @test */
    public function driver_testings_index_filters_by_driver()
    {
        $otherDriver = UserDriverDetail::factory()->create([
            'carrier_id' => Carrier::factory()->create(['id_plan' => Membership::factory()->create()->id])->id
        ]);
        
        DriverTesting::factory()->count(3)->create([
            'user_driver_detail_id' => $this->driver->id
        ]);
        DriverTesting::factory()->count(2)->create([
            'user_driver_detail_id' => $otherDriver->id
        ]);

        $response = $this->actingAsSuperAdmin()
            ->get(route('driver-testings.index', ['driver' => $this->driver->id]));

        $response->assertStatus(200);
    }

    /** @test */
    public function driver_testings_index_filters_by_result()
    {
        DriverTesting::factory()->count(4)->create([
            'user_driver_detail_id' => $this->driver->id,
            'test_result' => 'Negative'
        ]);
        DriverTesting::factory()->count(3)->create([
            'user_driver_detail_id' => $this->driver->id,
            'test_result' => 'Positive'
        ]);

        $response = $this->actingAsSuperAdmin()
            ->get(route('driver-testings.index', ['test_result' => 'Negative']));

        $response->assertStatus(200);
    }

    /** @test */
    public function guest_cannot_access_driver_testings()
    {
        $response = $this->get(route('driver-testings.index'));

        $response->assertRedirect('/login');
    }
}
