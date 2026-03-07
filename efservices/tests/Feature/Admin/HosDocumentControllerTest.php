<?php

namespace Tests\Feature\Admin;

use App\Models\Carrier;
use App\Models\UserDriverDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Test suite for Admin HOS Document Controller
 * Tests the index method with the new grouping functionality
 */
class HosDocumentControllerTest extends AdminTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create necessary permissions
        $this->createPermission('view hos documents');
        $this->createPermission('manage hos documents');
    }

    /**
     * Helper method to create a valid PDF file for testing
     */
    protected function createPdfFile($filename = 'test.pdf')
    {
        $pdfContent = '%PDF-1.4
1 0 obj
<<
/Type /Catalog
/Pages 2 0 R
>>
endobj
2 0 obj
<<
/Type /Pages
/Kids [3 0 R]
/Count 1
>>
endobj
3 0 obj
<<
/Type /Page
/Parent 2 0 R
/Resources <<
/Font <<
/F1 <<
/Type /Font
/Subtype /Type1
/BaseFont /Helvetica
>>
>>
>>
/MediaBox [0 0 612 792]
/Contents 4 0 R
>>
endobj
4 0 obj
<<
/Length 44
>>
stream
BT
/F1 12 Tf
100 700 Td
(Test PDF) Tj
ET
endstream
endobj
xref
0 5
0000000000 65535 f 
0000000009 00000 n 
0000000058 00000 n 
0000000115 00000 n 
0000000317 00000 n 
trailer
<<
/Size 5
/Root 1 0 R
>>
startxref
410
%%EOF';
        
        return UploadedFile::fake()->createWithContent($filename, $pdfContent);
    }

    /** @test */
    public function it_displays_hos_documents_index_page()
    {
        $response = $this->actingAsSuperAdmin()
            ->get(route('admin.hos.documents.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.hos.documents.index');
    }

    /** @test */
    public function it_passes_documents_by_carrier_to_view()
    {
        $response = $this->actingAsSuperAdmin()
            ->get(route('admin.hos.documents.index'));

        $response->assertStatus(200);
        $response->assertViewHas('documentsByCarrier');
        $response->assertViewHas('documents');
        $response->assertViewHas('carriers');
        $response->assertViewHas('drivers');
    }

    /** @test */
    public function it_groups_documents_by_carrier_and_driver()
    {
        // Create carriers
        $carrier1 = Carrier::factory()->create(['name' => 'Carrier A']);
        $carrier2 = Carrier::factory()->create(['name' => 'Carrier B']);

        // Create drivers
        $driver1 = UserDriverDetail::factory()->create([
            'carrier_id' => $carrier1->id,
        ]);
        $driver2 = UserDriverDetail::factory()->create([
            'carrier_id' => $carrier2->id,
        ]);

        // Create documents for drivers using Spatie Media Library
        Storage::fake('public');
        
        $driver1->addMedia($this->createPdfFile('daily_log1.pdf'))->toMediaCollection('daily_logs');
        $driver2->addMedia($this->createPdfFile('daily_log2.pdf'))->toMediaCollection('daily_logs');

        $response = $this->actingAsSuperAdmin()
            ->get(route('admin.hos.documents.index'));

        $response->assertStatus(200);
        
        $documentsByCarrier = $response->viewData('documentsByCarrier');
        
        // Assert structure exists
        $this->assertIsArray($documentsByCarrier);
        
        // Assert carriers are present
        $this->assertArrayHasKey($carrier1->id, $documentsByCarrier);
        $this->assertArrayHasKey($carrier2->id, $documentsByCarrier);
        
        // Assert carrier structure
        $this->assertArrayHasKey('carrier', $documentsByCarrier[$carrier1->id]);
        $this->assertArrayHasKey('drivers', $documentsByCarrier[$carrier1->id]);
        
        // Assert driver structure
        $this->assertArrayHasKey($driver1->id, $documentsByCarrier[$carrier1->id]['drivers']);
        $this->assertArrayHasKey('driver', $documentsByCarrier[$carrier1->id]['drivers'][$driver1->id]);
        $this->assertArrayHasKey('documents', $documentsByCarrier[$carrier1->id]['drivers'][$driver1->id]);
    }

    /** @test */
    public function it_filters_documents_by_carrier()
    {
        // Create carriers
        $carrier1 = Carrier::factory()->create(['name' => 'Carrier A']);
        $carrier2 = Carrier::factory()->create(['name' => 'Carrier B']);

        // Create drivers
        $driver1 = UserDriverDetail::factory()->create(['carrier_id' => $carrier1->id]);
        $driver2 = UserDriverDetail::factory()->create(['carrier_id' => $carrier2->id]);

        // Create documents
        Storage::fake('public');
        
        $driver1->addMedia($this->createPdfFile('daily_log1.pdf'))->toMediaCollection('daily_logs');
        $driver2->addMedia($this->createPdfFile('daily_log2.pdf'))->toMediaCollection('daily_logs');

        $response = $this->actingAsSuperAdmin()
            ->get(route('admin.hos.documents.index', ['carrier_id' => $carrier1->id]));

        $response->assertStatus(200);
        
        $documentsByCarrier = $response->viewData('documentsByCarrier');
        
        // Assert only carrier1 documents are present
        $this->assertArrayHasKey($carrier1->id, $documentsByCarrier);
        $this->assertArrayNotHasKey($carrier2->id, $documentsByCarrier);
    }

    /** @test */
    public function it_filters_documents_by_driver()
    {
        // Create carrier and drivers
        $carrier = Carrier::factory()->create();
        $driver1 = UserDriverDetail::factory()->create(['carrier_id' => $carrier->id]);
        $driver2 = UserDriverDetail::factory()->create(['carrier_id' => $carrier->id]);

        // Create documents
        Storage::fake('public');
        
        $driver1->addMedia($this->createPdfFile('daily_log1.pdf'))->toMediaCollection('daily_logs');
        $driver2->addMedia($this->createPdfFile('daily_log2.pdf'))->toMediaCollection('daily_logs');

        $response = $this->actingAsSuperAdmin()
            ->get(route('admin.hos.documents.index', ['driver_id' => $driver1->id]));

        $response->assertStatus(200);
        
        $documentsByCarrier = $response->viewData('documentsByCarrier');
        
        // Assert only driver1 documents are present
        $this->assertArrayHasKey($carrier->id, $documentsByCarrier);
        $this->assertArrayHasKey($driver1->id, $documentsByCarrier[$carrier->id]['drivers']);
        $this->assertArrayNotHasKey($driver2->id, $documentsByCarrier[$carrier->id]['drivers']);
    }

    /** @test */
    public function it_filters_documents_by_type()
    {
        // Create carrier and driver
        $carrier = Carrier::factory()->create();
        $driver = UserDriverDetail::factory()->create(['carrier_id' => $carrier->id]);

        // Create different types of documents
        Storage::fake('public');
        
        $driver->addMedia($this->createPdfFile('daily_log.pdf'))->toMediaCollection('daily_logs');
        $driver->addMedia($this->createPdfFile('monthly_summary.pdf'))->toMediaCollection('monthly_summaries');
        $driver->addMedia($this->createPdfFile('trip_report.pdf'))->toMediaCollection('trip_reports');

        // Test daily_logs filter
        $response = $this->actingAsSuperAdmin()
            ->get(route('admin.hos.documents.index', ['type' => 'daily_logs']));

        $response->assertStatus(200);
        
        $documents = $response->viewData('documents');
        
        // Assert all documents are daily_logs
        foreach ($documents as $doc) {
            $this->assertEquals('daily_logs', $doc->collection_name);
        }
    }

    /** @test */
    public function it_maintains_backward_compatibility_with_flattened_documents()
    {
        // Create carrier and driver with documents
        $carrier = Carrier::factory()->create();
        $driver = UserDriverDetail::factory()->create(['carrier_id' => $carrier->id]);

        Storage::fake('public');
        
        $driver->addMedia($this->createPdfFile('daily_log.pdf'))->toMediaCollection('daily_logs');
        $driver->addMedia($this->createPdfFile('monthly_summary.pdf'))->toMediaCollection('monthly_summaries');

        $response = $this->actingAsSuperAdmin()
            ->get(route('admin.hos.documents.index'));

        $response->assertStatus(200);
        
        // Assert both structures are present
        $response->assertViewHas('documentsByCarrier');
        $response->assertViewHas('documents');
        
        $documents = $response->viewData('documents');
        
        // Assert documents is a collection
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $documents);
        
        // Assert documents count matches
        $this->assertCount(2, $documents);
    }

    /** @test */
    public function it_handles_empty_documents_gracefully()
    {
        $response = $this->actingAsSuperAdmin()
            ->get(route('admin.hos.documents.index'));

        $response->assertStatus(200);
        
        $documentsByCarrier = $response->viewData('documentsByCarrier');
        $documents = $response->viewData('documents');
        
        $this->assertIsArray($documentsByCarrier);
        $this->assertEmpty($documentsByCarrier);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $documents);
        $this->assertTrue($documents->isEmpty());
    }
}
