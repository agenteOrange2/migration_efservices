<?php

namespace Tests\Unit\Models\Admin;

use Tests\TestCase;
use App\Models\Carrier;
use App\Models\Membership;
use App\Models\CarrierDocument;
use App\Models\UserCarrierDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CarrierModelTest extends TestCase
{
    use RefreshDatabase;

    protected $carrier;
    protected $membership;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->membership = Membership::factory()->create();
        $this->carrier = Carrier::factory()->create([
            'id_plan' => $this->membership->id
        ]);
    }

    /** @test */
    public function carrier_has_required_fillable_attributes()
    {
        $carrier = new Carrier();
        $fillable = $carrier->getFillable();
        
        $this->assertContains('name', $fillable);
        $this->assertContains('address', $fillable);
        $this->assertContains('state', $fillable);
        $this->assertContains('zipcode', $fillable);
        $this->assertContains('ein_number', $fillable);
        $this->assertContains('status', $fillable);
    }

    /** @test */
    public function carrier_has_correct_status_constants()
    {
        $this->assertEquals(1, Carrier::STATUS_ACTIVE);
        $this->assertEquals(0, Carrier::STATUS_INACTIVE);
        $this->assertEquals(2, Carrier::STATUS_PENDING);
        $this->assertEquals(3, Carrier::STATUS_PENDING_VALIDATION);
    }

    /** @test */
    public function carrier_belongs_to_membership()
    {
        $this->assertInstanceOf(Membership::class, $this->carrier->membership);
    }

    /** @test */
    public function carrier_has_many_documents()
    {
        CarrierDocument::factory()->count(5)->create([
            'carrier_id' => $this->carrier->id
        ]);

        $this->assertCount(5, $this->carrier->documents);
        $this->assertInstanceOf(CarrierDocument::class, $this->carrier->documents->first());
    }

    /** @test */
    public function carrier_has_many_user_carriers()
    {
        UserCarrierDetail::factory()->count(3)->create([
            'carrier_id' => $this->carrier->id
        ]);

        $this->assertCount(3, $this->carrier->userCarriers);
    }

    /** @test */
    public function carrier_can_be_created_with_factory()
    {
        $newCarrier = Carrier::factory()->create([
            'id_plan' => $this->membership->id
        ]);

        $this->assertInstanceOf(Carrier::class, $newCarrier);
        $this->assertDatabaseHas('carriers', [
            'id' => $newCarrier->id,
            'id_plan' => $this->membership->id
        ]);
    }

    /** @test */
    public function carrier_status_text_accessor_works()
    {
        $activeCarrier = Carrier::factory()->create([
            'status' => Carrier::STATUS_ACTIVE,
            'id_plan' => $this->membership->id
        ]);

        $inactiveCarrier = Carrier::factory()->create([
            'status' => Carrier::STATUS_INACTIVE,
            'id_plan' => $this->membership->id
        ]);

        $pendingCarrier = Carrier::factory()->create([
            'status' => Carrier::STATUS_PENDING,
            'id_plan' => $this->membership->id
        ]);

        $this->assertEquals('Active', $activeCarrier->status_text);
        $this->assertEquals('Inactive', $inactiveCarrier->status_text);
        $this->assertEquals('Pending', $pendingCarrier->status_text);
    }

    /** @test */
    public function carrier_scope_active_works()
    {
        Carrier::factory()->count(5)->create([
            'status' => Carrier::STATUS_ACTIVE,
            'id_plan' => $this->membership->id
        ]);
        
        Carrier::factory()->count(2)->create([
            'status' => Carrier::STATUS_INACTIVE,
            'id_plan' => $this->membership->id
        ]);

        $activeCarriers = Carrier::active()->get();
        
        $this->assertCount(5, $activeCarriers);
        $this->assertTrue($activeCarriers->every(function ($carrier) {
            return $carrier->status === Carrier::STATUS_ACTIVE;
        }));
    }

    /** @test */
    public function carrier_has_slug_attribute()
    {
        $carrier = Carrier::factory()->create([
            'name' => 'Test Carrier Company',
            'id_plan' => $this->membership->id
        ]);

        $this->assertNotEmpty($carrier->slug);
        $this->assertEquals('test-carrier-company', $carrier->slug);
    }

    /** @test */
    public function carrier_has_pending_documents_count()
    {
        CarrierDocument::factory()->create([
            'carrier_id' => $this->carrier->id,
            'status' => CarrierDocument::STATUS_PENDING
        ]);
        
        CarrierDocument::factory()->create([
            'carrier_id' => $this->carrier->id,
            'status' => CarrierDocument::STATUS_APPROVED
        ]);

        $this->assertEquals(1, $this->carrier->pending_documents_count);
    }

    /** @test */
    public function carrier_has_approved_documents_count()
    {
        CarrierDocument::factory()->count(3)->create([
            'carrier_id' => $this->carrier->id,
            'status' => CarrierDocument::STATUS_APPROVED
        ]);

        $this->assertEquals(3, $this->carrier->approved_documents_count);
    }
}
