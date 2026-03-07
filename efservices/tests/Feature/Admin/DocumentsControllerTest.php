<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Carrier;
use App\Models\Membership;
use App\Models\UserDriverDetail;
use App\Models\CarrierDocument;
use App\Models\DocumentType;
use App\Models\Admin\Vehicle\VehicleDocument;
use App\Models\Admin\Driver\DriverLicense;
use App\Models\Admin\Driver\DriverAccident;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class DocumentsControllerTest extends AdminTestCase
{
    protected $carrier;
    protected $driver;
    protected $documentType;

    protected function setUp(): void
    {
        parent::setUp();

        $membership = Membership::factory()->create();
        $this->carrier = Carrier::factory()->create(['id_plan' => $membership->id]);
        $this->driver = UserDriverDetail::factory()->create([
            'carrier_id' => $this->carrier->id
        ]);
        $this->documentType = DocumentType::factory()->create();
    }

    /** @test */
    public function superadmin_can_access_carrier_documents()
    {
        CarrierDocument::factory()->count(5)->create([
            'carrier_id' => $this->carrier->id,
            'document_type_id' => $this->documentType->id
        ]);

        $response = $this->actingAsSuperAdmin()
            ->get(route('admin.carrier.documents', $this->carrier));

        $response->assertStatus(200);
        $response->assertViewIs('admin.carrier.documents');
    }

    /** @test */
    public function superadmin_can_upload_carrier_document()
    {
        Storage::fake('public');
        
        $file = UploadedFile::fake()->image('document.pdf');

        $response = $this->actingAsSuperAdmin()
            ->post(route('admin.carrier.admin_documents.upload', [$this->carrier, $this->documentType]), [
                'file' => $file,
            ]);

        $response->assertRedirect();
        $this->assertTrue(Storage::disk('public')->exists('carrier-documents/' . $file->hashName()));
    }

    /** @test */
    public function superadmin_can_update_carrier_document_status()
    {
        $document = CarrierDocument::factory()->create([
            'carrier_id' => $this->carrier->id,
            'document_type_id' => $this->documentType->id,
            'status' => 'pending'
        ]);

        $response = $this->actingAsSuperAdmin()
            ->put(route('carrier.document.update-status', $document), [
                'status' => 'approved'
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('carrier_documents', [
            'id' => $document->id,
            'status' => 'approved'
        ]);
    }

    /** @test */
    public function superadmin_can_approve_carrier_document()
    {
        $document = CarrierDocument::factory()->create([
            'carrier_id' => $this->carrier->id,
            'document_type_id' => $this->documentType->id,
            'status' => 'pending'
        ]);

        $response = $this->actingAsSuperAdmin()
            ->post(route('carrier.approve_document', [$this->carrier, $document]), [
                'approved' => true
            ]);

        $response->assertStatus(200);
        $document->refresh();
        $this->assertEquals('approved', $document->status);
    }

    /** @test */
    public function superadmin_can_generate_missing_documents()
    {
        DocumentType::factory()->count(5)->create();

        $response = $this->actingAsSuperAdmin()
            ->get(route('admin.carrier.generate-missing-documents', $this->carrier));

        $response->assertRedirect();
        $this->assertCount(5, $this->carrier->documents);
    }

    /** @test */
    public function superadmin_can_access_driver_documents()
    {
        $response = $this->actingAsSuperAdmin()
            ->get(route('licenses.docs.show', $this->driver));

        $response->assertStatus(200);
    }

    /** @test */
    public function superadmin_can_upload_driver_document()
    {
        Storage::fake('public');
        
        $license = DriverLicense::factory()->create([
            'user_driver_detail_id' => $this->driver->id
        ]);
        
        $file = UploadedFile::fake()->create('license.pdf', 1000, 'application/pdf');

        $response = $this->actingAsSuperAdmin()
            ->post(route('licenses.upload.documents', $license), [
                'file' => $file,
            ]);

        $response->assertRedirect();
        $this->assertTrue(Storage::disk('public')->exists('driver-documents/' . $file->hashName()));
    }

    /** @test */
    public function superadmin_can_delete_driver_document()
    {
        Storage::fake('public');
        
        $license = DriverLicense::factory()->create([
            'user_driver_detail_id' => $this->driver->id
        ]);
        
        $license->addMedia(UploadedFile::fake()->create('license.pdf', 1000, 'application/pdf'))
            ->toMediaCollection('license_files');

        $media = $license->getFirstMedia('license_files');

        $response = $this->actingAsSuperAdmin()
            ->delete(route('licenses.documents.delete', $media));

        $response->assertStatus(200);
        $this->assertFalse(Storage::disk('public')->exists($media->getPath()));
    }

    /** @test */
    public function superadmin_can_access_accident_documents()
    {
        $accident = DriverAccident::factory()->create([
            'user_driver_detail_id' => $this->driver->id
        ]);

        $response = $this->actingAsSuperAdmin()
            ->get(route('accidents.documents', $accident));

        $response->assertStatus(200);
    }

    /** @test */
    public function superadmin_can_upload_accident_documents()
    {
        Storage::fake('public');
        
        $accident = DriverAccident::factory()->create([
            'user_driver_detail_id' => $this->driver->id
        ]);
        
        $file = UploadedFile::fake()->create('accident.pdf', 1000, 'application/pdf');

        $response = $this->actingAsSuperAdmin()
            ->post(route('accidents.documents.store', $accident), [
                'files' => [$file],
            ]);

        $response->assertRedirect();
        $this->assertTrue(Storage::disk('public')->exists('accident-documents/' . $file->hashName()));
    }

    /** @test */
    public function superadmin_can_preview_accident_document()
    {
        Storage::fake('public');
        
        $accident = DriverAccident::factory()->create([
            'user_driver_detail_id' => $this->driver->id
        ]);
        
        $accident->addMedia(UploadedFile::fake()->create('accident.pdf', 1000, 'application/pdf'))
            ->toMediaCollection('accident_files');

        $media = $accident->getFirstMedia('accident_files');

        $response = $this->actingAsSuperAdmin()
            ->get(route('accidents.documents.preview', $media));

        $response->assertStatus(200);
    }

    /** @test */
    public function superadmin_can_delete_accident_document()
    {
        Storage::fake('public');
        
        $accident = DriverAccident::factory()->create([
            'user_driver_detail_id' => $this->driver->id
        ]);
        
        $accident->addMedia(UploadedFile::fake()->create('accident.pdf', 1000, 'application/pdf'))
            ->toMediaCollection('accident_files');

        $media = $accident->getFirstMedia('accident_files');

        $response = $this->actingAsSuperAdmin()
            ->delete(route('accidents.documents.destroy', $media));

        $response->assertRedirect();
        $this->assertFalse(Storage::disk('public')->exists($media->getPath()));
    }

    /** @test */
    public function superadmin_can_access_vehicle_documents()
    {
        $vehicle = \App\Models\Admin\Vehicle\Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id
        ]);

        $response = $this->actingAsSuperAdmin()
            ->get(route('vehicles.documents.index', $vehicle));

        $response->assertStatus(200);
    }

    /** @test */
    public function superadmin_can_upload_vehicle_document()
    {
        Storage::fake('public');
        
        $vehicle = \App\Models\Admin\Vehicle\Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id
        ]);
        
        $file = UploadedFile::fake()->create('vehicle_doc.pdf', 1000, 'application/pdf');

        $response = $this->actingAsSuperAdmin()
            ->post(route('vehicles.documents.store', $vehicle), [
                'files' => [$file],
            ]);

        $response->assertRedirect();
        $this->assertTrue(Storage::disk('public')->exists('vehicle-documents/' . $file->hashName()));
    }

    /** @test */
    public function superadmin_can_delete_vehicle_document()
    {
        Storage::fake('public');
        
        $vehicle = \App\Models\Admin\Vehicle\Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id
        ]);
        
        $document = VehicleDocument::factory()->create([
            'vehicle_id' => $vehicle->id
        ]);
        
        $document->addMedia(UploadedFile::fake()->create('vehicle.pdf', 1000, 'application/pdf'))
            ->toMediaCollection('vehicle_files');

        $media = $document->getFirstMedia('vehicle_files');

        $response = $this->actingAsSuperAdmin()
            ->delete(route('documents.destroy', $media));

        $response->assertStatus(200);
        $this->assertFalse(Storage::disk('public')->exists($media->getPath()));
    }

    /** @test */
    public function superadmin_can_preview_document()
    {
        Storage::fake('public');
        
        $vehicle = \App\Models\Admin\Vehicle\Vehicle::factory()->create([
            'carrier_id' => $this->carrier->id
        ]);
        
        $document = VehicleDocument::factory()->create([
            'vehicle_id' => $vehicle->id
        ]);
        
        $document->addMedia(UploadedFile::fake()->create('doc.pdf', 1000, 'application/pdf'))
            ->toMediaCollection('vehicle_files');

        $media = $document->getFirstMedia('vehicle_files');

        $response = $this->actingAsSuperAdmin()
            ->get(route('documents.preview', $media));

        $response->assertStatus(200);
    }

    /** @test */
    public function guest_cannot_access_documents()
    {
        $response = $this->get(route('admin.carrier.documents', $this->carrier));

        $response->assertRedirect('/login');
    }
}
