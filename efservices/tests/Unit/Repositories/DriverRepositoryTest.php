<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use App\Models\User;
use App\Models\Carrier;
use App\Models\UserDriverDetail;
use App\Repositories\DriverRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DriverRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected DriverRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new DriverRepository();
    }

    public function test_can_find_active_drivers_by_carrier(): void
    {
        $carrier = Carrier::factory()->create();
        
        UserDriverDetail::factory()->count(3)->create([
            'carrier_id' => $carrier->id,
            'status' => UserDriverDetail::STATUS_ACTIVE,
        ]);
        
        UserDriverDetail::factory()->count(2)->create([
            'carrier_id' => $carrier->id,
            'status' => UserDriverDetail::STATUS_INACTIVE,
        ]);

        $activeDrivers = $this->repository->findActiveByCarrier($carrier->id);

        $this->assertCount(3, $activeDrivers);
        $activeDrivers->each(function ($driver) use ($carrier) {
            $this->assertEquals(UserDriverDetail::STATUS_ACTIVE, $driver->status);
            $this->assertEquals($carrier->id, $driver->carrier_id);
        });
    }

    public function test_can_find_drivers_with_completed_application(): void
    {
        UserDriverDetail::factory()->count(2)->create([
            'application_completed' => true,
        ]);
        
        UserDriverDetail::factory()->create([
            'application_completed' => false,
        ]);

        $completedDrivers = $this->repository->findWithCompletedApplication();

        $this->assertCount(2, $completedDrivers);
        $completedDrivers->each(function ($driver) {
            $this->assertTrue($driver->application_completed);
        });
    }

    public function test_can_find_drivers_pending_approval(): void
    {
        $carrier = Carrier::factory()->create();
        
        UserDriverDetail::factory()->count(2)->create([
            'carrier_id' => $carrier->id,
            'application_completed' => true,
            'status' => UserDriverDetail::STATUS_PENDING,
        ]);
        
        UserDriverDetail::factory()->create([
            'carrier_id' => $carrier->id,
            'application_completed' => false,
            'status' => UserDriverDetail::STATUS_PENDING,
        ]);

        $pendingDrivers = $this->repository->findPendingApproval($carrier->id);

        $this->assertCount(2, $pendingDrivers);
    }

    public function test_can_find_driver_by_email(): void
    {
        $user = User::factory()->create(['email' => 'driver@test.com']);
        $driver = UserDriverDetail::factory()->create(['user_id' => $user->id]);

        $found = $this->repository->findByEmail('driver@test.com');

        $this->assertNotNull($found);
        $this->assertEquals($driver->id, $found->id);
    }

    public function test_returns_null_when_driver_email_not_found(): void
    {
        $found = $this->repository->findByEmail('nonexistent@test.com');

        $this->assertNull($found);
    }

    public function test_can_search_drivers_by_name(): void
    {
        $user1 = User::factory()->create(['name' => 'John Doe']);
        $user2 = User::factory()->create(['name' => 'Jane Smith']);
        $user3 = User::factory()->create(['name' => 'John Smith']);
        
        UserDriverDetail::factory()->create(['user_id' => $user1->id]);
        UserDriverDetail::factory()->create(['user_id' => $user2->id]);
        UserDriverDetail::factory()->create(['user_id' => $user3->id]);

        $results = $this->repository->search('John');

        $this->assertCount(2, $results);
    }

    public function test_can_search_drivers_by_carrier(): void
    {
        $carrier1 = Carrier::factory()->create();
        $carrier2 = Carrier::factory()->create();
        
        UserDriverDetail::factory()->count(3)->create(['carrier_id' => $carrier1->id]);
        UserDriverDetail::factory()->count(2)->create(['carrier_id' => $carrier2->id]);

        $results = $this->repository->search('', $carrier1->id);

        $this->assertCount(3, $results);
    }

    public function test_can_find_unassigned_drivers(): void
    {
        $carrier = Carrier::factory()->create();
        
        // Drivers sin asignación
        UserDriverDetail::factory()->count(2)->create([
            'carrier_id' => $carrier->id,
            'status' => UserDriverDetail::STATUS_ACTIVE,
        ]);

        $unassignedDrivers = $this->repository->findUnassigned($carrier->id);

        $this->assertCount(2, $unassignedDrivers);
    }

    public function test_repository_uses_eager_loading(): void
    {
        $driver = UserDriverDetail::factory()->create();

        $found = $this->repository->find($driver->id);

        // Verificar que las relaciones están cargadas
        $this->assertTrue($found->relationLoaded('user'));
    }

    public function test_can_get_application_progress(): void
    {
        $driver = UserDriverDetail::factory()->create([
            'phone' => '1234567890',
            'date_of_birth' => '1990-01-01',
            'completion_percentage' => 50,
        ]);

        $progress = $this->repository->getApplicationProgress($driver->id);

        $this->assertIsArray($progress);
        $this->assertArrayHasKey('completion_percentage', $progress);
        $this->assertArrayHasKey('sections', $progress);
        $this->assertEquals(50, $progress['completion_percentage']);
    }

    public function test_returns_empty_array_for_nonexistent_driver_progress(): void
    {
        $progress = $this->repository->getApplicationProgress(99999);

        $this->assertIsArray($progress);
        $this->assertEmpty($progress);
    }
}
