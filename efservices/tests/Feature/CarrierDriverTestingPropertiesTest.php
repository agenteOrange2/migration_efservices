<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Carrier;
use App\Models\UserCarrierDetail;
use App\Models\UserDriverDetail;
use App\Models\Admin\Driver\DriverTesting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Property-Based Tests for Carrier Driver Testing Management
 * Feature: carrier-driver-testing-management
 */
class CarrierDriverTestingPropertiesTest extends TestCase
{
    use RefreshDatabase;

    protected $carrier;
    protected $carrierUser;
    protected $carrierDetails;
    protected $otherCarrier;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create user_carrier role if it doesn't exist
        if (!Role::where('name', 'user_carrier')->exists()) {
            Role::create(['name' => 'user_carrier', 'guard_name' => 'web']);
        }
        
        // Create first carrier and user
        $this->carrier = Carrier::create([
            'name' => 'Test Carrier 1',
            'slug' => 'test-carrier-1',
            'address' => '123 Test St',
            'state' => 'CA',
            'zipcode' => '12345',
            'ein_number' => '12-3456789',
            'dot_number' => '123456',
            'mc_number' => 'MC123456',
            'status' => Carrier::STATUS_ACTIVE,
        ]);
        
        $this->carrierUser = User::factory()->create([
            'status' => 1,
        ]);
        $this->carrierUser->assignRole('user_carrier');
        
        $this->carrierDetails = UserCarrierDetail::create([
            'user_id' => $this->carrierUser->id,
            'carrier_id' => $this->carrier->id,
            'phone' => '1234567890',
            'job_position' => 'Manager',
            'status' => UserCarrierDetail::STATUS_ACTIVE,
        ]);
        
        // Ensure carrier has active status to pass middleware checks
        $this->carrier->update([
            'status' => Carrier::STATUS_ACTIVE,
            'documents_completed' => true
        ]);
        
        // Create another carrier (for negative tests)
        $this->otherCarrier = Carrier::create([
            'name' => 'Test Carrier 2',
            'slug' => 'test-carrier-2',
            'address' => '456 Other St',
            'state' => 'NY',
            'zipcode' => '54321',
            'ein_number' => '98-7654321',
            'dot_number' => '654321',
            'mc_number' => 'MC654321',
            'status' => Carrier::STATUS_ACTIVE,
        ]);
    }

    /**
     * Property 1: Carrier Isolation in Listing
     * Validates: Requirements 1.1
     * 
     * For any carrier user accessing the driver testings index page, 
     * all returned testing records must have drivers that belong exclusively 
     * to that carrier's organization.
     * 
     * @test
     */
    public function property_carrier_isolation_in_listing()
    {
        // Run test with multiple iterations (100 as per design spec)
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $this->actingAs($this->carrierUser);
            
            // Bypass middleware for testing
            $this->withoutMiddleware(\App\Http\Middleware\CheckUserStatus::class);
            // Generate random number of drivers for this carrier (1-5)
            $numOwnDrivers = rand(1, 5);
            $ownDrivers = [];
            
            for ($i = 0; $i < $numOwnDrivers; $i++) {
                $driverUser = User::factory()->create();
                $driver = UserDriverDetail::create([
                    'user_id' => $driverUser->id,
                    'carrier_id' => $this->carrier->id,
                    'last_name' => 'Driver' . $iteration . '_' . $i,
                    'phone' => '555' . str_pad($iteration * 10 + $i, 7, '0', STR_PAD_LEFT),
                    'date_of_birth' => '1990-01-01',
                    'status' => 1,
                ]);
                $ownDrivers[] = $driver;
                
                // Create random number of tests for this driver (0-3)
                $numTests = rand(0, 3);
                for ($j = 0; $j < $numTests; $j++) {
                    DriverTesting::create([
                        'user_driver_detail_id' => $driver->id,
                        'carrier_id' => $this->carrier->id,
                        'test_date' => now()->subDays(rand(1, 365)),
                        'test_type' => $this->randomTestType(),
                        'test_result' => $this->randomTestResult(),
                        'status' => $this->randomStatus(),
                        'created_by' => $this->carrierUser->id,
                    ]);
                }
            }
            
            // Generate random number of drivers for other carrier (1-5)
            $numOtherDrivers = rand(1, 5);
            for ($i = 0; $i < $numOtherDrivers; $i++) {
                $otherDriverUser = User::factory()->create();
                $otherDriver = UserDriverDetail::create([
                    'user_id' => $otherDriverUser->id,
                    'carrier_id' => $this->otherCarrier->id,
                    'last_name' => 'OtherDriver' . $iteration . '_' . $i,
                    'phone' => '666' . str_pad($iteration * 10 + $i, 7, '0', STR_PAD_LEFT),
                    'date_of_birth' => '1992-01-01',
                    'status' => 1,
                ]);
                
                // Create random number of tests for this driver (0-3)
                $numTests = rand(0, 3);
                for ($j = 0; $j < $numTests; $j++) {
                    DriverTesting::create([
                        'user_driver_detail_id' => $otherDriver->id,
                        'carrier_id' => $this->otherCarrier->id,
                        'test_date' => now()->subDays(rand(1, 365)),
                        'test_type' => $this->randomTestType(),
                        'test_result' => $this->randomTestResult(),
                        'status' => $this->randomStatus(),
                        'created_by' => $this->carrierUser->id,
                    ]);
                }
            }
            
            // Make request to index
            $response = $this->get(route('carrier.drivers.testings.index'));
            
            $response->assertStatus(200);
            $response->assertViewHas('testings');
            
            $testings = $response->viewData('testings');
            
            // Property: All returned testing records must have drivers belonging to this carrier
            foreach ($testings as $testing) {
                $this->assertNotNull($testing->userDriverDetail, 
                    "Testing record {$testing->id} has no driver relationship");
                $this->assertEquals($this->carrier->id, $testing->userDriverDetail->carrier_id,
                    "Testing record {$testing->id} belongs to carrier {$testing->userDriverDetail->carrier_id}, expected {$this->carrier->id}");
            }
            
            // Clean up for next iteration
            DriverTesting::query()->delete();
            UserDriverDetail::query()->delete();
            User::whereNotIn('id', [$this->carrierUser->id])->delete();
        }
    }

    /**
     * Property 10: Driver Selection Constraint
     * Feature: carrier-driver-testing-management, Property 10: Driver Selection Constraint
     * Validates: Requirements 3.1
     * 
     * For any carrier user accessing the create or edit form, 
     * the list of available drivers must contain only drivers where 
     * carrier_id matches the authenticated user's carrier.
     * 
     * @test
     */
    public function property_driver_selection_constraint()
    {
        // Run test with multiple iterations (100 as per design spec)
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $this->actingAs($this->carrierUser);
            
            // Bypass middleware for testing
            $this->withoutMiddleware(\App\Http\Middleware\CheckUserStatus::class);
            
            // Generate random number of drivers for this carrier (1-10)
            $numOwnDrivers = rand(1, 10);
            $ownDriverIds = [];
            
            for ($i = 0; $i < $numOwnDrivers; $i++) {
                $driverUser = User::factory()->create();
                $driver = UserDriverDetail::create([
                    'user_id' => $driverUser->id,
                    'carrier_id' => $this->carrier->id,
                    'last_name' => 'OwnDriver' . $iteration . '_' . $i,
                    'phone' => '555' . str_pad($iteration * 10 + $i, 7, '0', STR_PAD_LEFT),
                    'date_of_birth' => '1990-01-01',
                    'status' => 1,
                ]);
                $ownDriverIds[] = $driver->id;
            }
            
            // Generate random number of drivers for other carrier (1-10)
            $numOtherDrivers = rand(1, 10);
            $otherDriverIds = [];
            
            for ($i = 0; $i < $numOtherDrivers; $i++) {
                $otherDriverUser = User::factory()->create();
                $otherDriver = UserDriverDetail::create([
                    'user_id' => $otherDriverUser->id,
                    'carrier_id' => $this->otherCarrier->id,
                    'last_name' => 'OtherDriver' . $iteration . '_' . $i,
                    'phone' => '666' . str_pad($iteration * 10 + $i, 7, '0', STR_PAD_LEFT),
                    'date_of_birth' => '1992-01-01',
                    'status' => 1,
                ]);
                $otherDriverIds[] = $otherDriver->id;
            }
            
            // Test create form
            $response = $this->get(route('carrier.drivers.testings.create'));
            
            $response->assertStatus(200);
            $response->assertViewHas('drivers');
            
            $drivers = $response->viewData('drivers');
            
            // Property: All drivers in the list must belong to the authenticated user's carrier
            foreach ($drivers as $driver) {
                $this->assertEquals($this->carrier->id, $driver->carrier_id,
                    "Driver {$driver->id} in create form belongs to carrier {$driver->carrier_id}, expected {$this->carrier->id}");
                $this->assertContains($driver->id, $ownDriverIds,
                    "Driver {$driver->id} should be in own drivers list");
                $this->assertNotContains($driver->id, $otherDriverIds,
                    "Driver {$driver->id} should NOT be in other carrier's drivers list");
            }
            
            // Verify that no drivers from other carrier are present
            $driverIds = $drivers->pluck('id')->toArray();
            foreach ($otherDriverIds as $otherDriverId) {
                $this->assertNotContains($otherDriverId, $driverIds,
                    "Other carrier's driver {$otherDriverId} should not appear in the list");
            }
            
            // Clean up for next iteration
            UserDriverDetail::query()->delete();
            User::whereNotIn('id', [$this->carrierUser->id])->delete();
        }
    }

    /**
     * Helper method to generate random test type
     */
    private function randomTestType(): string
    {
        $types = array_keys(DriverTesting::getTestTypes());
        return $types[array_rand($types)];
    }

    /**
     * Helper method to generate random test result
     */
    private function randomTestResult(): string
    {
        $results = array_keys(DriverTesting::getTestResults());
        return $results[array_rand($results)];
    }

    /**
     * Helper method to generate random status
     */
    private function randomStatus(): string
    {
        $statuses = array_keys(DriverTesting::getStatuses());
        return $statuses[array_rand($statuses)];
    }

    /**
     * Property 15: Edit Authorization Verification
     * Feature: carrier-driver-testing-management, Property 15: Edit Authorization Verification
     * Validates: Requirements 4.1, 4.2
     * 
     * For any attempt to access the edit form for a testing record, 
     * if the associated driver does not belong to the authenticated user's carrier, 
     * the system must deny access and redirect with an error message.
     * 
     * @test
     */
    public function property_edit_authorization_verification()
    {
        // Run test with multiple iterations (100 as per design spec)
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $this->actingAs($this->carrierUser);
            
            // Bypass middleware for testing
            $this->withoutMiddleware(\App\Http\Middleware\CheckUserStatus::class);
            
            // Create a driver for this carrier
            $ownDriverUser = User::factory()->create();
            $ownDriver = UserDriverDetail::create([
                'user_id' => $ownDriverUser->id,
                'carrier_id' => $this->carrier->id,
                'last_name' => 'OwnDriver' . $iteration,
                'phone' => '555' . str_pad($iteration, 7, '0', STR_PAD_LEFT),
                'date_of_birth' => '1990-01-01',
                'status' => 1,
            ]);
            
            // Create a testing record for own driver
            $ownTesting = DriverTesting::create([
                'user_driver_detail_id' => $ownDriver->id,
                'carrier_id' => $this->carrier->id,
                'test_date' => now()->subDays(rand(1, 30)),
                'test_type' => $this->randomTestType(),
                'test_result' => $this->randomTestResult(),
                'status' => $this->randomStatus(),
                'administered_by' => 'Test Administrator',
                'mro' => 'Test MRO',
                'requester_name' => 'Test Requester',
                'location' => 'Test Location',
                'scheduled_time' => now(),
                'bill_to' => 'Carrier',
                'created_by' => $this->carrierUser->id,
            ]);
            
            // Property: Should be able to access edit form for own driver's testing
            $response = $this->get(route('carrier.drivers.testings.edit', $ownTesting));
            $response->assertStatus(200);
            $response->assertViewHas('testing');
            $response->assertViewHas('drivers');
            
            // Create a driver for other carrier
            $otherDriverUser = User::factory()->create();
            $otherDriver = UserDriverDetail::create([
                'user_id' => $otherDriverUser->id,
                'carrier_id' => $this->otherCarrier->id,
                'last_name' => 'OtherDriver' . $iteration,
                'phone' => '666' . str_pad($iteration, 7, '0', STR_PAD_LEFT),
                'date_of_birth' => '1992-01-01',
                'status' => 1,
            ]);
            
            // Create a testing record for other carrier's driver
            $otherTesting = DriverTesting::create([
                'user_driver_detail_id' => $otherDriver->id,
                'carrier_id' => $this->otherCarrier->id,
                'test_date' => now()->subDays(rand(1, 30)),
                'test_type' => $this->randomTestType(),
                'test_result' => $this->randomTestResult(),
                'status' => $this->randomStatus(),
                'administered_by' => 'Test Administrator',
                'mro' => 'Test MRO',
                'requester_name' => 'Test Requester',
                'location' => 'Test Location',
                'scheduled_time' => now(),
                'bill_to' => 'Carrier',
                'created_by' => $this->carrierUser->id,
            ]);
            
            // Property: Should NOT be able to access edit form for other carrier's driver testing
            $response = $this->get(route('carrier.drivers.testings.edit', $otherTesting));
            $response->assertRedirect(route('carrier.drivers.testings.index'));
            $response->assertSessionHas('error', 'No tienes acceso a este registro de prueba.');
            
            // Clean up for next iteration
            DriverTesting::query()->delete();
            UserDriverDetail::query()->delete();
            User::whereNotIn('id', [$this->carrierUser->id])->delete();
        }
    }

    /**
     * Property 16: Update Validation
     * Feature: carrier-driver-testing-management, Property 16: Update Validation
     * Validates: Requirements 4.4
     * 
     * For any update form submission with invalid data, 
     * the system must reject the submission and return validation errors 
     * without modifying the database.
     * 
     * @test
     */
    public function property_update_validation()
    {
        // Run test with multiple iterations (100 as per design spec)
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $this->actingAs($this->carrierUser);
            
            // Bypass middleware for testing
            $this->withoutMiddleware(\App\Http\Middleware\CheckUserStatus::class);
            
            // Create a driver for this carrier
            $driverUser = User::factory()->create();
            $driver = UserDriverDetail::create([
                'user_id' => $driverUser->id,
                'carrier_id' => $this->carrier->id,
                'last_name' => 'TestDriver' . $iteration,
                'phone' => '555' . str_pad($iteration, 7, '0', STR_PAD_LEFT),
                'date_of_birth' => '1990-01-01',
                'status' => 1,
            ]);
            
            // Create a testing record
            $testing = DriverTesting::create([
                'user_driver_detail_id' => $driver->id,
                'carrier_id' => $this->carrier->id,
                'test_date' => now()->subDays(10),
                'test_type' => 'DOT Drug Test',
                'test_result' => 'Negative',
                'status' => 'Completed',
                'administered_by' => 'Original Administrator',
                'mro' => 'Original MRO',
                'requester_name' => 'Original Requester',
                'location' => 'Original Location',
                'scheduled_time' => now()->subDays(10),
                'bill_to' => 'Carrier',
                'created_by' => $this->carrierUser->id,
            ]);
            
            $originalData = $testing->toArray();
            
            // Generate invalid data - randomly choose which field to make invalid
            $invalidScenarios = [
                ['user_driver_detail_id' => null], // Missing required field
                ['test_date' => null], // Missing required field
                ['test_type' => null], // Missing required field
                ['test_result' => null], // Missing required field
                ['administered_by' => null], // Missing required field
                ['location' => null], // Missing required field
            ];
            
            $invalidData = $invalidScenarios[array_rand($invalidScenarios)];
            
            // Property: Invalid data should be rejected with validation errors
            $response = $this->put(route('carrier.drivers.testings.update', $testing), $invalidData);
            $response->assertSessionHasErrors();
            
            // Property: Database should not be modified
            $testing->refresh();
            $this->assertEquals($originalData['test_date'], $testing->test_date->format('Y-m-d H:i:s'));
            $this->assertEquals($originalData['test_type'], $testing->test_type);
            $this->assertEquals($originalData['test_result'], $testing->test_result);
            $this->assertEquals($originalData['administered_by'], $testing->administered_by);
            
            // Clean up for next iteration
            DriverTesting::query()->delete();
            UserDriverDetail::query()->delete();
            User::whereNotIn('id', [$this->carrierUser->id])->delete();
        }
    }

    /**
     * Property 17: Successful Record Update
     * Feature: carrier-driver-testing-management, Property 17: Successful Record Update
     * Validates: Requirements 4.5
     * 
     * For any valid update form submission, 
     * the DriverTesting record must be updated in the database 
     * with the new field values.
     * 
     * @test
     */
    public function property_successful_record_update()
    {
        // Run test with multiple iterations (100 as per design spec)
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $this->actingAs($this->carrierUser);
            
            // Bypass middleware for testing
            $this->withoutMiddleware(\App\Http\Middleware\CheckUserStatus::class);
            
            // Create a driver for this carrier
            $driverUser = User::factory()->create();
            $driver = UserDriverDetail::create([
                'user_id' => $driverUser->id,
                'carrier_id' => $this->carrier->id,
                'last_name' => 'TestDriver' . $iteration,
                'phone' => '555' . str_pad($iteration, 7, '0', STR_PAD_LEFT),
                'date_of_birth' => '1990-01-01',
                'status' => 1,
            ]);
            
            // Create a testing record
            $testing = DriverTesting::create([
                'user_driver_detail_id' => $driver->id,
                'carrier_id' => $this->carrier->id,
                'test_date' => now()->subDays(10),
                'test_type' => 'DOT Drug Test',
                'test_result' => 'Negative',
                'status' => 'Completed',
                'administered_by' => 'Original Administrator',
                'mro' => 'Original MRO',
                'requester_name' => 'Original Requester',
                'location' => 'Original Location',
                'scheduled_time' => now()->subDays(10)->format('Y-m-d\TH:i'),
                'bill_to' => 'Carrier',
                'notes' => 'Original notes',
                'created_by' => $this->carrierUser->id,
            ]);
            
            // Generate valid update data with random values
            $newTestType = $this->randomTestType();
            $newTestResult = $this->randomTestResult();
            $newStatus = $this->randomStatus();
            $newNotes = 'Updated notes ' . $iteration;
            
            $updateData = [
                'user_driver_detail_id' => $driver->id,
                'test_date' => now()->subDays(5)->format('Y-m-d'),
                'test_type' => $newTestType,
                'test_result' => $newTestResult,
                'status' => $newStatus,
                'administered_by' => 'Updated Administrator ' . $iteration,
                'mro' => 'Updated MRO ' . $iteration,
                'requester_name' => 'Updated Requester ' . $iteration,
                'location' => 'Updated Location ' . $iteration,
                'scheduled_time' => now()->subDays(5)->format('Y-m-d\TH:i'),
                'bill_to' => 'Driver',
                'notes' => $newNotes,
                'is_random_test' => rand(0, 1),
                'is_post_accident_test' => rand(0, 1),
                'is_reasonable_suspicion_test' => rand(0, 1),
                'is_pre_employment_test' => rand(0, 1),
                'is_follow_up_test' => rand(0, 1),
                'is_return_to_duty_test' => rand(0, 1),
                'is_other_reason_test' => 0,
            ];
            
            // Property: Valid data should update the record successfully
            $response = $this->put(route('carrier.drivers.testings.update', $testing), $updateData);
            $response->assertRedirect(route('carrier.drivers.testings.show', $testing));
            $response->assertSessionHas('success');
            
            // Property: Database should be updated with new values
            $testing->refresh();
            $this->assertEquals($newTestType, $testing->test_type);
            $this->assertEquals($newTestResult, $testing->test_result);
            $this->assertEquals($newStatus, $testing->status);
            $this->assertEquals($newNotes, $testing->notes);
            $this->assertEquals($updateData['administered_by'], $testing->administered_by);
            $this->assertEquals($updateData['mro'], $testing->mro);
            $this->assertEquals($updateData['location'], $testing->location);
            
            // Clean up for next iteration
            DriverTesting::query()->delete();
            UserDriverDetail::query()->delete();
            User::whereNotIn('id', [$this->carrierUser->id])->delete();
        }
    }

    /**
     * Property 18: PDF Regeneration on Update
     * Feature: carrier-driver-testing-management, Property 18: PDF Regeneration on Update
     * Validates: Requirements 4.6
     * 
     * For any successfully updated DriverTesting record, 
     * the PDF must be regenerated with the updated information.
     * 
     * @test
     */
    public function property_pdf_regeneration_on_update()
    {
        // Run test with multiple iterations (50 iterations to reduce test time)
        for ($iteration = 0; $iteration < 50; $iteration++) {
            $this->actingAs($this->carrierUser);
            
            // Bypass middleware for testing
            $this->withoutMiddleware(\App\Http\Middleware\CheckUserStatus::class);
            
            // Create a driver for this carrier
            $driverUser = User::factory()->create();
            $driver = UserDriverDetail::create([
                'user_id' => $driverUser->id,
                'carrier_id' => $this->carrier->id,
                'last_name' => 'TestDriver' . $iteration,
                'phone' => '555' . str_pad($iteration, 7, '0', STR_PAD_LEFT),
                'date_of_birth' => '1990-01-01',
                'status' => 1,
            ]);
            
            // Create a testing record
            $testing = DriverTesting::create([
                'user_driver_detail_id' => $driver->id,
                'carrier_id' => $this->carrier->id,
                'test_date' => now()->subDays(10),
                'test_type' => 'DOT Drug Test',
                'test_result' => 'Negative',
                'status' => 'Completed',
                'administered_by' => 'Original Administrator',
                'mro' => 'Original MRO',
                'requester_name' => 'Original Requester',
                'location' => 'Original Location',
                'scheduled_time' => now()->subDays(10)->format('Y-m-d\TH:i'),
                'bill_to' => 'Carrier',
                'created_by' => $this->carrierUser->id,
            ]);
            
            // Add initial PDF
            $testing->addMediaFromString('Initial PDF content')
                ->usingFileName('initial_test.pdf')
                ->toMediaCollection('drug_test_pdf');
            
            $initialPdfCount = $testing->getMedia('drug_test_pdf')->count();
            $this->assertEquals(1, $initialPdfCount, 'Should have 1 initial PDF');
            
            // Update the testing record
            $updateData = [
                'user_driver_detail_id' => $driver->id,
                'test_date' => now()->subDays(5)->format('Y-m-d'),
                'test_type' => $this->randomTestType(),
                'test_result' => $this->randomTestResult(),
                'status' => $this->randomStatus(),
                'administered_by' => 'Updated Administrator',
                'mro' => 'Updated MRO',
                'requester_name' => 'Updated Requester',
                'location' => 'Updated Location',
                'scheduled_time' => now()->subDays(5)->format('Y-m-d\TH:i'),
                'bill_to' => 'Driver',
                'is_random_test' => 1,
                'is_post_accident_test' => 0,
                'is_reasonable_suspicion_test' => 0,
                'is_pre_employment_test' => 0,
                'is_follow_up_test' => 0,
                'is_return_to_duty_test' => 0,
                'is_other_reason_test' => 0,
            ];
            
            $response = $this->put(route('carrier.drivers.testings.update', $testing), $updateData);
            $response->assertRedirect(route('carrier.drivers.testings.show', $testing));
            
            // Property: PDF should be regenerated (still 1 PDF but different content)
            $testing->refresh();
            $finalPdfCount = $testing->getMedia('drug_test_pdf')->count();
            $this->assertEquals(1, $finalPdfCount, 'Should still have exactly 1 PDF after update');
            
            // Clean up for next iteration
            DriverTesting::query()->delete();
            UserDriverDetail::query()->delete();
            User::whereNotIn('id', [$this->carrierUser->id])->delete();
        }
    }

    /**
     * Property 19: PDF Replacement Atomicity
     * Feature: carrier-driver-testing-management, Property 19: PDF Replacement Atomicity
     * Validates: Requirements 4.7, 9.5
     * 
     * For any testing record being updated, if a PDF already exists 
     * in the 'drug_test_pdf' collection, it must be deleted before 
     * the new PDF is stored, ensuring only one PDF exists.
     * 
     * @test
     */
    public function property_pdf_replacement_atomicity()
    {
        // Run test with multiple iterations (50 iterations to reduce test time)
        for ($iteration = 0; $iteration < 50; $iteration++) {
            $this->actingAs($this->carrierUser);
            
            // Bypass middleware for testing
            $this->withoutMiddleware(\App\Http\Middleware\CheckUserStatus::class);
            
            // Create a driver for this carrier
            $driverUser = User::factory()->create();
            $driver = UserDriverDetail::create([
                'user_id' => $driverUser->id,
                'carrier_id' => $this->carrier->id,
                'last_name' => 'TestDriver' . $iteration,
                'phone' => '555' . str_pad($iteration, 7, '0', STR_PAD_LEFT),
                'date_of_birth' => '1990-01-01',
                'status' => 1,
            ]);
            
            // Create a testing record
            $testing = DriverTesting::create([
                'user_driver_detail_id' => $driver->id,
                'carrier_id' => $this->carrier->id,
                'test_date' => now()->subDays(10),
                'test_type' => 'DOT Drug Test',
                'test_result' => 'Negative',
                'status' => 'Completed',
                'administered_by' => 'Test Administrator',
                'mro' => 'Test MRO',
                'requester_name' => 'Test Requester',
                'location' => 'Test Location',
                'scheduled_time' => now()->subDays(10)->format('Y-m-d\TH:i'),
                'bill_to' => 'Carrier',
                'created_by' => $this->carrierUser->id,
            ]);
            
            // Add initial PDF
            $testing->addMediaFromString('Initial PDF content ' . $iteration)
                ->usingFileName('initial_test_' . $iteration . '.pdf')
                ->toMediaCollection('drug_test_pdf');
            
            $this->assertEquals(1, $testing->getMedia('drug_test_pdf')->count());
            
            // Update the testing record multiple times
            $numUpdates = rand(2, 5);
            for ($updateNum = 0; $updateNum < $numUpdates; $updateNum++) {
                $updateData = [
                    'user_driver_detail_id' => $driver->id,
                    'test_date' => now()->subDays(5)->format('Y-m-d'),
                    'test_type' => $this->randomTestType(),
                    'test_result' => $this->randomTestResult(),
                    'status' => $this->randomStatus(),
                    'administered_by' => 'Administrator Update ' . $updateNum,
                    'mro' => 'MRO Update ' . $updateNum,
                    'requester_name' => 'Requester Update ' . $updateNum,
                    'location' => 'Location Update ' . $updateNum,
                    'scheduled_time' => now()->subDays(5)->format('Y-m-d\TH:i'),
                    'bill_to' => 'Driver',
                    'is_random_test' => 1,
                    'is_post_accident_test' => 0,
                    'is_reasonable_suspicion_test' => 0,
                    'is_pre_employment_test' => 0,
                    'is_follow_up_test' => 0,
                    'is_return_to_duty_test' => 0,
                    'is_other_reason_test' => 0,
                ];
                
                $response = $this->put(route('carrier.drivers.testings.update', $testing), $updateData);
                $response->assertRedirect(route('carrier.drivers.testings.show', $testing));
                
                // Property: Should always have exactly 1 PDF (atomicity)
                $testing->refresh();
                $pdfCount = $testing->getMedia('drug_test_pdf')->count();
                $this->assertEquals(1, $pdfCount, 
                    "After update #{$updateNum}, should have exactly 1 PDF, found {$pdfCount}");
            }
            
            // Clean up for next iteration
            DriverTesting::query()->delete();
            UserDriverDetail::query()->delete();
            User::whereNotIn('id', [$this->carrierUser->id])->delete();
        }
    }

    /**
     * Property 37: Update Audit Trail
     * Feature: carrier-driver-testing-management, Property 37: Update Audit Trail
     * Validates: Requirements 12.2
     * 
     * For any updated DriverTesting record, 
     * the updated_by field must be set to the ID of the authenticated user 
     * performing the update.
     * 
     * @test
     */
    public function property_update_audit_trail()
    {
        // Run test with multiple iterations (100 as per design spec)
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $this->actingAs($this->carrierUser);
            
            // Bypass middleware for testing
            $this->withoutMiddleware(\App\Http\Middleware\CheckUserStatus::class);
            
            // Create a driver for this carrier
            $driverUser = User::factory()->create();
            $driver = UserDriverDetail::create([
                'user_id' => $driverUser->id,
                'carrier_id' => $this->carrier->id,
                'last_name' => 'TestDriver' . $iteration,
                'phone' => '555' . str_pad($iteration, 7, '0', STR_PAD_LEFT),
                'date_of_birth' => '1990-01-01',
                'status' => 1,
            ]);
            
            // Create a testing record
            $testing = DriverTesting::create([
                'user_driver_detail_id' => $driver->id,
                'carrier_id' => $this->carrier->id,
                'test_date' => now()->subDays(10),
                'test_type' => 'DOT Drug Test',
                'test_result' => 'Negative',
                'status' => 'Completed',
                'administered_by' => 'Test Administrator',
                'mro' => 'Test MRO',
                'requester_name' => 'Test Requester',
                'location' => 'Test Location',
                'scheduled_time' => now()->subDays(10)->format('Y-m-d\TH:i'),
                'bill_to' => 'Carrier',
                'created_by' => $this->carrierUser->id,
                'updated_by' => null, // Initially null
            ]);
            
            $this->assertNull($testing->updated_by, 'updated_by should initially be null');
            
            // Update the testing record
            $updateData = [
                'user_driver_detail_id' => $driver->id,
                'test_date' => now()->subDays(5)->format('Y-m-d'),
                'test_type' => $this->randomTestType(),
                'test_result' => $this->randomTestResult(),
                'status' => $this->randomStatus(),
                'administered_by' => 'Updated Administrator',
                'mro' => 'Updated MRO',
                'requester_name' => 'Updated Requester',
                'location' => 'Updated Location',
                'scheduled_time' => now()->subDays(5)->format('Y-m-d\TH:i'),
                'bill_to' => 'Driver',
                'is_random_test' => 1,
                'is_post_accident_test' => 0,
                'is_reasonable_suspicion_test' => 0,
                'is_pre_employment_test' => 0,
                'is_follow_up_test' => 0,
                'is_return_to_duty_test' => 0,
                'is_other_reason_test' => 0,
            ];
            
            $response = $this->put(route('carrier.drivers.testings.update', $testing), $updateData);
            $response->assertRedirect(route('carrier.drivers.testings.show', $testing));
            
            // Property: updated_by should be set to authenticated user's ID
            $testing->refresh();
            $this->assertNotNull($testing->updated_by, 'updated_by should not be null after update');
            $this->assertEquals($this->carrierUser->id, $testing->updated_by,
                "updated_by should be {$this->carrierUser->id}, found {$testing->updated_by}");
            
            // Clean up for next iteration
            DriverTesting::query()->delete();
            UserDriverDetail::query()->delete();
            User::whereNotIn('id', [$this->carrierUser->id])->delete();
        }
    }

    /**
     * Property 34: File Upload Validation
     * Feature: carrier-driver-testing-management, Property 34: File Upload Validation
     * Validates: Requirements 10.3
     * 
     * For any file uploaded as an attachment, it must be validated for 
     * allowed file types (PDF, JPG, PNG, DOC, DOCX) before being stored.
     * 
     * @test
     */
    public function property_file_upload_validation()
    {
        // Run test with multiple iterations (100 as per design spec)
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $this->actingAs($this->carrierUser);
            
            // Bypass middleware for testing
            $this->withoutMiddleware(\App\Http\Middleware\CheckUserStatus::class);
            
            // Create a driver for this carrier
            $driverUser = User::factory()->create();
            $driver = UserDriverDetail::create([
                'user_id' => $driverUser->id,
                'carrier_id' => $this->carrier->id,
                'last_name' => 'TestDriver' . $iteration,
                'phone' => '555' . str_pad($iteration, 7, '0', STR_PAD_LEFT),
                'date_of_birth' => '1990-01-01',
                'status' => 1,
            ]);
            
            // Test with invalid file types - should be rejected
            $invalidExtensions = ['txt', 'exe', 'zip', 'mp3', 'mp4', 'avi'];
            $randomInvalidExt = $invalidExtensions[array_rand($invalidExtensions)];
            
            $invalidFile = \Illuminate\Http\UploadedFile::fake()->create(
                'invalid_file_' . $iteration . '.' . $randomInvalidExt,
                100
            );
            
            $invalidData = [
                'user_driver_detail_id' => $driver->id,
                'test_date' => now()->format('Y-m-d'),
                'test_type' => $this->randomTestType(),
                'test_result' => $this->randomTestResult(),
                'status' => $this->randomStatus(),
                'administered_by' => 'Test Administrator',
                'mro' => 'Test MRO',
                'requester_name' => 'Test Requester',
                'location' => 'Test Location',
                'scheduled_time' => now()->format('Y-m-d\TH:i'),
                'bill_to' => 'Carrier',
                'attachments' => [$invalidFile],
            ];
            
            // Property: Invalid file types should be rejected with validation error
            $response = $this->post(route('carrier.drivers.testings.store'), $invalidData);
            $response->assertSessionHasErrors('attachments.0');
            
            // Clean up for next iteration
            DriverTesting::query()->delete();
            UserDriverDetail::query()->delete();
            User::whereNotIn('id', [$this->carrierUser->id])->delete();
        }
    }

    /**
     * Property 35: Attachment Collection Storage
     * Feature: carrier-driver-testing-management, Property 35: Attachment Collection Storage
     * Validates: Requirements 10.2
     * 
     * For any successfully uploaded attachment file, it must be stored in 
     * the 'document_attachments' media collection associated with the testing record.
     * 
     * @test
     */
    public function property_attachment_storage()
    {
        // Run test with multiple iterations (100 as per design spec)
        for ($iteration = 0; $iteration < 100; $iteration++) {
            $this->actingAs($this->carrierUser);
            
            // Bypass middleware for testing
            $this->withoutMiddleware(\App\Http\Middleware\CheckUserStatus::class);
            
            // Create a driver for this carrier
            $driverUser = User::factory()->create();
            $driver = UserDriverDetail::create([
                'user_id' => $driverUser->id,
                'carrier_id' => $this->carrier->id,
                'last_name' => 'TestDriver' . $iteration,
                'phone' => '555' . str_pad($iteration, 7, '0', STR_PAD_LEFT),
                'date_of_birth' => '1990-01-01',
                'status' => 1,
            ]);
            
            // Create a testing record without attachments first
            $testing = DriverTesting::create([
                'user_driver_detail_id' => $driver->id,
                'carrier_id' => $this->carrier->id,
                'test_date' => now(),
                'test_type' => $this->randomTestType(),
                'test_result' => $this->randomTestResult(),
                'status' => $this->randomStatus(),
                'administered_by' => 'Test Administrator',
                'mro' => 'Test MRO',
                'requester_name' => 'Test Requester',
                'location' => 'Test Location',
                'scheduled_time' => now(),
                'bill_to' => 'Carrier',
                'created_by' => $this->carrierUser->id,
            ]);
            
            // Property: Initially should have no attachments
            $this->assertEquals(0, $testing->getMedia('document_attachments')->count(),
                "Testing record should initially have 0 attachments");
            
            // Generate random number of files to upload (1-3)
            $numFiles = rand(1, 3);
            $validExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
            
            for ($fileNum = 0; $fileNum < $numFiles; $fileNum++) {
                $randomExt = $validExtensions[array_rand($validExtensions)];
                $file = \Illuminate\Http\UploadedFile::fake()->create(
                    'attachment_' . $iteration . '_' . $fileNum . '.' . $randomExt,
                    100
                );
                
                // Add file to document_attachments collection
                $testing->addMedia($file)->toMediaCollection('document_attachments');
            }
            
            // Property: Should have exactly the number of files we uploaded
            $testing->refresh();
            $attachmentCount = $testing->getMedia('document_attachments')->count();
            $this->assertEquals($numFiles, $attachmentCount,
                "Testing record should have exactly {$numFiles} attachments, found {$attachmentCount}");
            
            // Property: All files should be in the document_attachments collection
            $attachments = $testing->getMedia('document_attachments');
            foreach ($attachments as $attachment) {
                $this->assertEquals('document_attachments', $attachment->collection_name,
                    "Attachment should be in 'document_attachments' collection");
            }
            
            // Clean up for next iteration
            DriverTesting::query()->delete();
            UserDriverDetail::query()->delete();
            User::whereNotIn('id', [$this->carrierUser->id])->delete();
        }
    }
}
