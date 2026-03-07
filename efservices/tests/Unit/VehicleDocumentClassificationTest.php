<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Admin\Vehicle\VehicleDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VehicleDocumentClassificationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_correctly_identifies_expired_documents()
    {
        $vehicle = Vehicle::factory()->create();
        
        // Document expired yesterday
        $expiredDoc = VehicleDocument::factory()->create([
            'vehicle_id' => $vehicle->id,
            'expiration_date' => now()->subDay(),
        ]);

        $this->assertTrue($expiredDoc->isExpired());
    }

    /** @test */
    public function it_correctly_identifies_non_expired_documents()
    {
        $vehicle = Vehicle::factory()->create();
        
        // Document expires tomorrow
        $activeDoc = VehicleDocument::factory()->create([
            'vehicle_id' => $vehicle->id,
            'expiration_date' => now()->addDay(),
        ]);

        $this->assertFalse($activeDoc->isExpired());
    }

    /** @test */
    public function it_handles_documents_without_expiration_date()
    {
        $vehicle = Vehicle::factory()->create();
        
        $noExpirationDoc = VehicleDocument::factory()->create([
            'vehicle_id' => $vehicle->id,
            'expiration_date' => null,
        ]);

        $this->assertFalse($noExpirationDoc->isExpired());
    }

    /** @test */
    public function it_correctly_identifies_documents_about_to_expire()
    {
        $vehicle = Vehicle::factory()->create();
        
        // Document expires in 15 days
        $expiringSoonDoc = VehicleDocument::factory()->create([
            'vehicle_id' => $vehicle->id,
            'expiration_date' => now()->addDays(15),
        ]);

        $this->assertTrue($expiringSoonDoc->isAboutToExpire(30));
    }

    /** @test */
    public function it_correctly_identifies_documents_not_about_to_expire()
    {
        $vehicle = Vehicle::factory()->create();
        
        // Document expires in 45 days
        $notExpiringSoonDoc = VehicleDocument::factory()->create([
            'vehicle_id' => $vehicle->id,
            'expiration_date' => now()->addDays(45),
        ]);

        $this->assertFalse($notExpiringSoonDoc->isAboutToExpire(30));
    }

    /** @test */
    public function it_does_not_classify_expired_documents_as_about_to_expire()
    {
        $vehicle = Vehicle::factory()->create();
        
        // Document expired yesterday
        $expiredDoc = VehicleDocument::factory()->create([
            'vehicle_id' => $vehicle->id,
            'expiration_date' => now()->subDay(),
        ]);

        $this->assertFalse($expiredDoc->isAboutToExpire(30));
    }

    /** @test */
    public function it_handles_documents_without_expiration_date_for_about_to_expire()
    {
        $vehicle = Vehicle::factory()->create();
        
        $noExpirationDoc = VehicleDocument::factory()->create([
            'vehicle_id' => $vehicle->id,
            'expiration_date' => null,
        ]);

        $this->assertFalse($noExpirationDoc->isAboutToExpire(30));
    }

    /** @test */
    public function it_correctly_uses_custom_days_parameter()
    {
        $vehicle = Vehicle::factory()->create();
        
        // Document expires in 10 days
        $doc = VehicleDocument::factory()->create([
            'vehicle_id' => $vehicle->id,
            'expiration_date' => now()->addDays(10),
        ]);

        $this->assertTrue($doc->isAboutToExpire(15));
        $this->assertFalse($doc->isAboutToExpire(5));
    }

    /** @test */
    public function it_correctly_identifies_document_expiring_today()
    {
        $vehicle = Vehicle::factory()->create();
        
        // Document expires today (start of day - already passed)
        $doc = VehicleDocument::factory()->create([
            'vehicle_id' => $vehicle->id,
            'expiration_date' => now()->startOfDay(),
        ]);

        // Start of today is in the past, so it's expired
        $this->assertTrue($doc->isExpired());
        $this->assertFalse($doc->isAboutToExpire(30));
    }

    /** @test */
    public function it_correctly_identifies_document_expiring_on_boundary()
    {
        $vehicle = Vehicle::factory()->create();
        
        // Document expires exactly 30 days from now
        $doc = VehicleDocument::factory()->create([
            'vehicle_id' => $vehicle->id,
            'expiration_date' => now()->addDays(30),
        ]);

        $this->assertTrue($doc->isAboutToExpire(30));
    }
}
