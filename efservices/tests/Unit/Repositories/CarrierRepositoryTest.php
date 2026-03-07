<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use App\Models\Carrier;
use App\Models\Membership;
use App\Repositories\CarrierRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CarrierRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected CarrierRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new CarrierRepository();
    }

    public function test_can_find_active_carriers(): void
    {
        // Crear carriers con diferentes estados
        Carrier::factory()->count(3)->create(['status' => Carrier::STATUS_ACTIVE]);
        Carrier::factory()->count(2)->create(['status' => Carrier::STATUS_INACTIVE]);

        $activeCarriers = $this->repository->findActive();

        $this->assertCount(3, $activeCarriers);
        $activeCarriers->each(function ($carrier) {
            $this->assertEquals(Carrier::STATUS_ACTIVE, $carrier->status);
        });
    }

    public function test_can_find_carriers_pending_validation(): void
    {
        Carrier::factory()->count(2)->create(['status' => Carrier::STATUS_PENDING_VALIDATION]);
        Carrier::factory()->create(['status' => Carrier::STATUS_ACTIVE]);

        $pendingCarriers = $this->repository->findPendingValidation();

        $this->assertCount(2, $pendingCarriers);
        $pendingCarriers->each(function ($carrier) {
            $this->assertEquals(Carrier::STATUS_PENDING_VALIDATION, $carrier->status);
        });
    }

    public function test_can_find_carrier_by_slug(): void
    {
        $carrier = Carrier::factory()->create(['slug' => 'test-carrier']);

        $found = $this->repository->findBySlug('test-carrier');

        $this->assertNotNull($found);
        $this->assertEquals($carrier->id, $found->id);
    }

    public function test_returns_null_when_carrier_slug_not_found(): void
    {
        $found = $this->repository->findBySlug('non-existent');

        $this->assertNull($found);
    }

    public function test_can_find_carrier_by_dot_number(): void
    {
        $carrier = Carrier::factory()->create(['dot_number' => '123456']);

        $found = $this->repository->findByDotNumber('123456');

        $this->assertNotNull($found);
        $this->assertEquals($carrier->id, $found->id);
    }

    public function test_can_find_carriers_by_membership(): void
    {
        $membership = Membership::factory()->create();
        Carrier::factory()->count(3)->create(['id_plan' => $membership->id]);
        Carrier::factory()->create(); // Sin membresía

        $carriers = $this->repository->findByMembership($membership->id);

        $this->assertCount(3, $carriers);
    }

    public function test_can_find_carriers_with_completed_documents(): void
    {
        Carrier::factory()->count(2)->create([
            'documents_completed' => true,
            'documents_completed_at' => now(),
        ]);
        Carrier::factory()->create(['documents_completed' => false]);

        $carriers = $this->repository->findWithCompletedDocuments();

        $this->assertCount(2, $carriers);
        $carriers->each(function ($carrier) {
            $this->assertTrue($carrier->documents_completed);
        });
    }

    public function test_can_search_carriers_by_name(): void
    {
        Carrier::factory()->create(['name' => 'ABC Transport']);
        Carrier::factory()->create(['name' => 'XYZ Logistics']);
        Carrier::factory()->create(['name' => 'ABC Freight']);

        $results = $this->repository->search('ABC');

        $this->assertCount(2, $results);
    }

    public function test_can_search_carriers_by_dot_number(): void
    {
        Carrier::factory()->create(['dot_number' => '123456']);
        Carrier::factory()->create(['dot_number' => '789012']);

        $results = $this->repository->search('123');

        $this->assertCount(1, $results);
        $this->assertEquals('123456', $results->first()->dot_number);
    }

    public function test_repository_uses_eager_loading(): void
    {
        $membership = Membership::factory()->create();
        $carrier = Carrier::factory()->create(['id_plan' => $membership->id]);

        $found = $this->repository->findBySlug($carrier->slug);

        // Verificar que la relación está cargada (no genera query adicional)
        $this->assertTrue($found->relationLoaded('membership'));
    }

    public function test_can_paginate_carriers(): void
    {
        Carrier::factory()->count(25)->create();

        $paginated = $this->repository->paginate(10);

        $this->assertEquals(10, $paginated->count());
        $this->assertEquals(25, $paginated->total());
        $this->assertEquals(3, $paginated->lastPage());
    }

    public function test_can_count_carriers(): void
    {
        Carrier::factory()->count(5)->create(['status' => Carrier::STATUS_ACTIVE]);

        $count = $this->repository->count(['status' => Carrier::STATUS_ACTIVE]);

        $this->assertEquals(5, $count);
    }

    public function test_can_check_if_carrier_exists(): void
    {
        $carrier = Carrier::factory()->create(['dot_number' => '999999']);

        $exists = $this->repository->exists(['dot_number' => '999999']);
        $notExists = $this->repository->exists(['dot_number' => '000000']);

        $this->assertTrue($exists);
        $this->assertFalse($notExists);
    }
}
