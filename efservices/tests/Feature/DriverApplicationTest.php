<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Carrier;
use App\Models\UserDriverDetail;
use App\Models\Admin\Driver\DriverApplication;
use App\Services\Driver\DriverApplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class DriverApplicationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected DriverApplicationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DriverApplicationService();
    }

    /**
     * Test que un driver puede ser creado con datos válidos
     */
    public function test_driver_can_be_created_with_valid_data(): void
    {
        $carrier = Carrier::factory()->create();

        $driverData = [
            'name' => $this->faker->firstName,
            'middle_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'Password123!',
            'phone' => $this->faker->phoneNumber,
            'date_of_birth' => $this->faker->date('Y-m-d', '-25 years'),
        ];

        $user = $this->service->createDriverUser($carrier, $driverData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertDatabaseHas('users', [
            'email' => $driverData['email'],
        ]);
        $this->assertDatabaseHas('user_driver_details', [
            'user_id' => $user->id,
            'carrier_id' => $carrier->id,
        ]);
        $this->assertTrue($user->hasRole('user_driver'));
    }

    /**
     * Test que se crea una aplicación al crear un driver
     */
    public function test_driver_application_is_created_with_driver(): void
    {
        $carrier = Carrier::factory()->create();

        $driverData = [
            'name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'Password123!',
        ];

        $user = $this->service->createDriverUser($carrier, $driverData);

        $this->assertDatabaseHas('driver_applications', [
            'user_id' => $user->id,
            'carrier_id' => $carrier->id,
            'status' => 'pending',
        ]);
    }

    /**
     * Test que el progreso de la aplicación se actualiza correctamente
     */
    public function test_application_progress_updates_correctly(): void
    {
        $driver = UserDriverDetail::factory()->create([
            'current_step' => 1,
            'completion_percentage' => 0,
        ]);

        $updatedDriver = $this->service->updateApplicationProgress($driver, 5);

        $this->assertEquals(5, $updatedDriver->current_step);
        $this->assertGreaterThan(0, $updatedDriver->completion_percentage);
    }

    /**
     * Test que el porcentaje de completitud se calcula correctamente
     */
    public function test_completion_percentage_calculates_correctly(): void
    {
        $driver = UserDriverDetail::factory()->create([
            'phone' => '1234567890',
            'date_of_birth' => '1990-01-01',
        ]);

        $percentage = $this->service->calculateCompletionPercentage($driver);

        $this->assertIsInt($percentage);
        $this->assertGreaterThanOrEqual(0, $percentage);
        $this->assertLessThanOrEqual(100, $percentage);
    }

    /**
     * Test que una aplicación no puede completarse si no está al 100%
     */
    public function test_application_cannot_complete_if_not_100_percent(): void
    {
        $driver = UserDriverDetail::factory()->create([
            'application_completed' => false,
            'completion_percentage' => 50,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Application is not complete');

        $this->service->completeApplication($driver);
    }

    /**
     * Test que un driver puede ser aprobado después de completar la aplicación
     */
    public function test_driver_can_be_approved_after_completing_application(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user_carrier');
        $this->actingAs($user);

        $driver = UserDriverDetail::factory()->create([
            'application_completed' => true,
            'status' => UserDriverDetail::STATUS_PENDING,
        ]);

        $approvedDriver = $this->service->approveDriver($driver);

        $this->assertEquals(UserDriverDetail::STATUS_ACTIVE, $approvedDriver->status);
        $this->assertEquals(1, $approvedDriver->user->status);
    }

    /**
     * Test que un driver no puede ser aprobado sin completar la aplicación
     */
    public function test_driver_cannot_be_approved_without_completing_application(): void
    {
        $driver = UserDriverDetail::factory()->create([
            'application_completed' => false,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot approve driver with incomplete application');

        $this->service->approveDriver($driver);
    }

    /**
     * Test que un driver puede ser rechazado
     */
    public function test_driver_can_be_rejected(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user_carrier');
        $this->actingAs($user);

        $driver = UserDriverDetail::factory()->create([
            'status' => UserDriverDetail::STATUS_PENDING,
        ]);

        $reason = 'Failed background check';
        $rejectedDriver = $this->service->rejectDriver($driver, $reason);

        $this->assertEquals(UserDriverDetail::STATUS_INACTIVE, $rejectedDriver->status);
        
        if ($rejectedDriver->application) {
            $this->assertEquals('rejected', $rejectedDriver->application->status);
            $this->assertEquals($reason, $rejectedDriver->application->rejection_reason);
        }
    }

    /**
     * Test que verifica si el driver tiene documentos requeridos
     */
    public function test_checks_if_driver_has_required_documents(): void
    {
        $driver = UserDriverDetail::factory()->create();

        $hasDocuments = $this->service->hasRequiredDocuments($driver);

        $this->assertIsBool($hasDocuments);
    }

    /**
     * Test que obtiene documentos faltantes del driver
     */
    public function test_gets_missing_documents_for_driver(): void
    {
        $driver = UserDriverDetail::factory()->create();

        $missingDocuments = $this->service->getMissingDocuments($driver);

        $this->assertIsArray($missingDocuments);
        $this->assertNotEmpty($missingDocuments); // Driver nuevo debe tener documentos faltantes
    }

    /**
     * Test que el driver inicial tiene status pending
     */
    public function test_new_driver_has_pending_status(): void
    {
        $carrier = Carrier::factory()->create();

        $driverData = [
            'name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'Password123!',
        ];

        $user = $this->service->createDriverUser($carrier, $driverData);
        $driver = $user->driverDetails;

        $this->assertEquals(UserDriverDetail::STATUS_PENDING, $driver->status);
        $this->assertFalse($driver->application_completed);
        $this->assertEquals(0, $driver->completion_percentage);
    }

    /**
     * Test que el usuario driver tiene status inactivo inicialmente
     */
    public function test_new_driver_user_is_inactive_initially(): void
    {
        $carrier = Carrier::factory()->create();

        $driverData = [
            'name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'Password123!',
        ];

        $user = $this->service->createDriverUser($carrier, $driverData);

        $this->assertEquals(0, $user->status);
    }
}
