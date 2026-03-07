<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Carrier;
use App\Models\DocumentType;
use App\Models\CarrierDocument;
use App\Services\CarrierDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CarrierDocumentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CarrierDocumentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CarrierDocumentService();
        Storage::fake('public');
    }

    /** @test */
    public function it_generates_base_documents_for_carrier()
    {
        $carrier = Carrier::factory()->create();

        // Crear tipos de documentos
        $documentType1 = DocumentType::create(['name' => 'W9', 'description' => 'W9 Form']);
        $documentType2 = DocumentType::create(['name' => 'Insurance', 'description' => 'Insurance Certificate']);
        $documentType3 = DocumentType::create(['name' => 'MC Authority', 'description' => 'MC Authority']);

        $this->service->generateBaseDocuments($carrier);

        // Verificar que se crearon documentos para todos los tipos
        $this->assertDatabaseHas('carrier_documents', [
            'carrier_id' => $carrier->id,
            'document_type_id' => $documentType1->id,
            'status' => CarrierDocument::STATUS_PENDING
        ]);

        $this->assertDatabaseHas('carrier_documents', [
            'carrier_id' => $carrier->id,
            'document_type_id' => $documentType2->id,
            'status' => CarrierDocument::STATUS_PENDING
        ]);

        $this->assertDatabaseHas('carrier_documents', [
            'carrier_id' => $carrier->id,
            'document_type_id' => $documentType3->id,
            'status' => CarrierDocument::STATUS_PENDING
        ]);

        $this->assertEquals(3, CarrierDocument::where('carrier_id', $carrier->id)->count());
    }

    /** @test */
    public function it_does_not_create_duplicate_documents()
    {
        $carrier = Carrier::factory()->create();
        $documentType = DocumentType::create(['name' => 'W9', 'description' => 'W9 Form']);

        // Generar documentos dos veces
        $this->service->generateBaseDocuments($carrier);
        $this->service->generateBaseDocuments($carrier);

        // Solo debe haber un documento
        $this->assertEquals(1, CarrierDocument::where([
            'carrier_id' => $carrier->id,
            'document_type_id' => $documentType->id
        ])->count());
    }

    /** @test */
    public function it_uploads_document_for_carrier()
    {
        $carrier = Carrier::factory()->create();
        $documentType = DocumentType::create(['name' => 'W9', 'description' => 'W9 Form']);

        $file = UploadedFile::fake()->create('w9.pdf', 1024, 'application/pdf');

        $carrierDocument = $this->service->uploadDocument($carrier, $documentType, $file);

        $this->assertNotNull($carrierDocument);
        $this->assertEquals($carrier->id, $carrierDocument->carrier_id);
        $this->assertEquals($documentType->id, $carrierDocument->document_type_id);
        $this->assertEquals(CarrierDocument::STATUS_IN_PROCESS, $carrierDocument->status);

        // Verificar que el archivo se guardó
        $this->assertCount(1, $carrierDocument->getMedia('carrier_documents'));
    }

    /** @test */
    public function it_creates_carrier_document_if_not_exists_on_upload()
    {
        $carrier = Carrier::factory()->create();
        $documentType = DocumentType::create(['name' => 'W9', 'description' => 'W9 Form']);

        // No existe documento previo
        $this->assertEquals(0, CarrierDocument::where('carrier_id', $carrier->id)->count());

        $file = UploadedFile::fake()->create('w9.pdf', 1024, 'application/pdf');

        $carrierDocument = $this->service->uploadDocument($carrier, $documentType, $file);

        // Ahora debe existir
        $this->assertDatabaseHas('carrier_documents', [
            'carrier_id' => $carrier->id,
            'document_type_id' => $documentType->id
        ]);
    }

    /** @test */
    public function it_replaces_existing_document_when_uploading_new_one()
    {
        $carrier = Carrier::factory()->create();
        $documentType = DocumentType::create(['name' => 'W9', 'description' => 'W9 Form']);

        // Subir primer documento
        $firstFile = UploadedFile::fake()->create('w9_old.pdf', 512, 'application/pdf');
        $firstDocument = $this->service->uploadDocument($carrier, $documentType, $firstFile);

        $this->assertCount(1, $firstDocument->getMedia('carrier_documents'));
        $firstMediaId = $firstDocument->getFirstMedia('carrier_documents')->id;

        // Subir segundo documento (debe reemplazar)
        $secondFile = UploadedFile::fake()->create('w9_new.pdf', 1024, 'application/pdf');
        $secondDocument = $this->service->uploadDocument($carrier, $documentType, $secondFile);

        // Debe tener solo un archivo en la colección
        $this->assertCount(1, $secondDocument->fresh()->getMedia('carrier_documents'));

        // El ID del media debe ser diferente (nuevo archivo)
        $secondMediaId = $secondDocument->fresh()->getFirstMedia('carrier_documents')->id;
        $this->assertNotEquals($firstMediaId, $secondMediaId);
    }

    /** @test */
    public function it_updates_status_to_in_process_when_uploading()
    {
        $carrier = Carrier::factory()->create();
        $documentType = DocumentType::create(['name' => 'W9', 'description' => 'W9 Form']);

        // Crear documento con status PENDING
        $carrierDocument = CarrierDocument::create([
            'carrier_id' => $carrier->id,
            'document_type_id' => $documentType->id,
            'status' => CarrierDocument::STATUS_PENDING,
            'date' => now()
        ]);

        $this->assertEquals(CarrierDocument::STATUS_PENDING, $carrierDocument->status);

        // Subir archivo
        $file = UploadedFile::fake()->create('w9.pdf', 1024, 'application/pdf');
        $updatedDocument = $this->service->uploadDocument($carrier, $documentType, $file);

        // Status debe cambiar a IN_PROCESS
        $this->assertEquals(CarrierDocument::STATUS_IN_PROCESS, $updatedDocument->status);
    }

    /** @test */
    public function it_uses_sanitized_filename_when_uploading()
    {
        $carrier = Carrier::factory()->create();
        $documentType = DocumentType::create(['name' => 'W9 Form Test', 'description' => 'W9 Form']);

        $file = UploadedFile::fake()->create('original_name.pdf', 1024, 'application/pdf');

        $carrierDocument = $this->service->uploadDocument($carrier, $documentType, $file);

        $media = $carrierDocument->getFirstMedia('carrier_documents');

        // El nombre debe ser sanitizado: "w9_form_test.pdf"
        $this->assertEquals('w9_form_test.pdf', $media->file_name);
    }

    /** @test */
    public function it_updates_date_when_uploading_document()
    {
        $carrier = Carrier::factory()->create();
        $documentType = DocumentType::create(['name' => 'W9', 'description' => 'W9 Form']);

        $beforeUpload = now()->subMinute();

        $file = UploadedFile::fake()->create('w9.pdf', 1024, 'application/pdf');
        $carrierDocument = $this->service->uploadDocument($carrier, $documentType, $file);

        $afterUpload = now()->addMinute();

        $this->assertNotNull($carrierDocument->date);
        $this->assertTrue($carrierDocument->date->between($beforeUpload, $afterUpload));
    }

    /** @test */
    public function it_updates_document_status()
    {
        $carrierDocument = CarrierDocument::factory()->create([
            'status' => CarrierDocument::STATUS_PENDING
        ]);

        $result = $this->service->updateDocumentStatus(
            $carrierDocument,
            CarrierDocument::STATUS_APPROVED,
            'Document looks good'
        );

        $this->assertTrue($result);
        $this->assertDatabaseHas('carrier_documents', [
            'id' => $carrierDocument->id,
            'status' => CarrierDocument::STATUS_APPROVED,
            'notes' => 'Document looks good'
        ]);
    }

    /** @test */
    public function it_updates_document_status_without_notes()
    {
        $carrierDocument = CarrierDocument::factory()->create([
            'status' => CarrierDocument::STATUS_PENDING
        ]);

        $result = $this->service->updateDocumentStatus(
            $carrierDocument,
            CarrierDocument::STATUS_APPROVED
        );

        $this->assertTrue($result);
        $this->assertDatabaseHas('carrier_documents', [
            'id' => $carrierDocument->id,
            'status' => CarrierDocument::STATUS_APPROVED,
            'notes' => null
        ]);
    }

    /** @test */
    public function it_distributes_default_document_to_all_carriers()
    {
        // Crear varios carriers
        $carrier1 = Carrier::factory()->create();
        $carrier2 = Carrier::factory()->create();
        $carrier3 = Carrier::factory()->create();

        $documentType = DocumentType::create(['name' => 'Safety Policy', 'description' => 'Safety Policy']);

        $this->service->distributeDefaultDocument($documentType);

        // Verificar que se crearon documentos para todos los carriers
        $this->assertDatabaseHas('carrier_documents', [
            'carrier_id' => $carrier1->id,
            'document_type_id' => $documentType->id
        ]);

        $this->assertDatabaseHas('carrier_documents', [
            'carrier_id' => $carrier2->id,
            'document_type_id' => $documentType->id
        ]);

        $this->assertDatabaseHas('carrier_documents', [
            'carrier_id' => $carrier3->id,
            'document_type_id' => $documentType->id
        ]);
    }

    /** @test */
    public function it_does_not_duplicate_documents_when_distributing()
    {
        $carrier = Carrier::factory()->create();
        $documentType = DocumentType::create(['name' => 'Safety Policy', 'description' => 'Safety Policy']);

        // Crear documento existente
        CarrierDocument::create([
            'carrier_id' => $carrier->id,
            'document_type_id' => $documentType->id,
            'status' => CarrierDocument::STATUS_APPROVED,
            'date' => now()
        ]);

        $this->service->distributeDefaultDocument($documentType);

        // Solo debe haber un documento
        $this->assertEquals(1, CarrierDocument::where([
            'carrier_id' => $carrier->id,
            'document_type_id' => $documentType->id
        ])->count());
    }

    /** @test */
    public function it_handles_large_number_of_carriers_when_distributing()
    {
        // Crear 250 carriers (más de 100, para probar chunking)
        Carrier::factory()->count(250)->create();

        $documentType = DocumentType::create(['name' => 'Safety Policy', 'description' => 'Safety Policy']);

        $this->service->distributeDefaultDocument($documentType);

        // Verificar que se crearon documentos para todos
        $this->assertEquals(250, CarrierDocument::where('document_type_id', $documentType->id)->count());
    }

    /** @test */
    public function it_returns_fresh_instance_after_upload()
    {
        $carrier = Carrier::factory()->create();
        $documentType = DocumentType::create(['name' => 'W9', 'description' => 'W9 Form']);

        $file = UploadedFile::fake()->create('w9.pdf', 1024, 'application/pdf');

        $carrierDocument = $this->service->uploadDocument($carrier, $documentType, $file);

        // Verificar que es una instancia fresca con las relaciones cargadas
        $this->assertTrue($carrierDocument->wasRecentlyCreated || $carrierDocument->wasChanged());
        $this->assertNotNull($carrierDocument->getFirstMedia('carrier_documents'));
    }
}
