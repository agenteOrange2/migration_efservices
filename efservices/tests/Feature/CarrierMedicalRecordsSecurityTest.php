<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Carrier;
use App\Models\UserCarrierDetail;
use App\Models\UserDriverDetail;
use App\Models\Admin\Driver\DriverMedicalQualification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CarrierMedicalRecordsSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected $carrier;
    protected $carrierUser;
    protected $carrierDetails;
    protected $driver;
    protected $medicalRecord;
    protected $otherCarrier;
    protected $otherDriver;
    protected $otherMedicalRecord;

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
        
        // Create driver for this carrier
        $driverUser = User::factory()->create(['name' => 'Test Driver 1']);
        $this->driver = UserDriverDetail::create([
            'user_id' => $driverUser->id,
            'carrier_id' => $this->carrier->id,
            'last_name' => 'Driver',
            'phone' => '9876543210',
            'date_of_birth' => '1990-01-01',
            'status' => 1,
        ]);
        
        // Create medical record for this driver
        $this->medicalRecord = DriverMedicalQualification::create([
            'user_driver_detail_id' => $this->driver->id,
            'medical_examiner_name' => 'Dr. Smith',
            'medical_examiner_registry_number' => 'REG123',
            'medical_card_expiration_date' => now()->addDays(60),
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
        
        $otherDriverUser = User::factory()->create(['name' => 'Test Driver 2']);
        $this->otherDriver = UserDriverDetail::create([
            'user_id' => $otherDriverUser->id,
            'carrier_id' => $this->otherCarrier->id,
            'last_name' => 'OtherDriver',
            'phone' => '5555555555',
            'date_of_birth' => '1992-01-01',
            'status' => 1,
        ]);
        
        $this->otherMedicalRecord = DriverMedicalQualification::create([
            'user_driver_detail_id' => $this->otherDriver->id,
            'medical_examiner_name' => 'Dr. Jones',
            'medical_examiner_registry_number' => 'REG456',
            'medical_card_expiration_date' => now()->addDays(45),
        ]);
    }

    /**
     * Test that unauthenticated users cannot access medical records
     */
    public function test_unauthenticated_user_cannot_access_medical_records()
    {
        $response = $this->get(route('carrier.medical-records.index'));
        $response->assertRedirect(route('login'));
    }

    /**
     * Test that middleware is applied to all routes
     */
    public function test_middleware_is_applied_to_all_routes()
    {
        // Test index route
        $response = $this->get(route('carrier.medical-records.index'));
        $response->assertRedirect(route('login'));
        
        // Test create route
        $response = $this->get(route('carrier.medical-records.create'));
        $response->assertRedirect(route('login'));
        
        // Test show route
        $response = $this->get(route('carrier.medical-records.show', $this->medicalRecord));
        $response->assertRedirect(route('login'));
        
        // Test edit route
        $response = $this->get(route('carrier.medical-records.edit', $this->medicalRecord));
        $response->assertRedirect(route('login'));
    }

    /**
     * Test that carrier can only see own drivers' medical records in index
     */
    public function test_carrier_can_only_see_own_drivers_medical_records()
    {
        $this->actingAs($this->carrierUser);
        
        $response = $this->get(route('carrier.medical-records.index'));
        
        $response->assertStatus(200);
        $response->assertSee($this->medicalRecord->medical_examiner_name);
        $response->assertDontSee($this->otherMedicalRecord->medical_examiner_name);
    }

    /**
     * Test that queries filter by carrier_id
     */
    public function test_queries_filter_by_carrier_id()
    {
        $this->actingAs($this->carrierUser);
        
        // Create additional medical records for both carriers
        $driver2 = UserDriverDetail::create([
            'user_id' => User::factory()->create()->id,
            'carrier_id' => $this->carrier->id,
            'last_name' => 'Driver2',
            'phone' => '1111111111',
            'date_of_birth' => '1991-01-01',
            'status' => 1,
        ]);
        
        $medicalRecord2 = DriverMedicalQualification::create([
            'user_driver_detail_id' => $driver2->id,
            'medical_examiner_name' => 'Dr. Brown',
            'medical_card_expiration_date' => now()->addDays(90),
        ]);
        
        $response = $this->get(route('carrier.medical-records.index'));
        
        $response->assertStatus(200);
        
        // Should see both records from own carrier
        $response->assertSee($this->medicalRecord->medical_examiner_name);
        $response->assertSee($medicalRecord2->medical_examiner_name);
        
        // Should not see records from other carrier
        $response->assertDontSee($this->otherMedicalRecord->medical_examiner_name);
    }

    /**
     * Test that carrier cannot access other carrier's medical record
     */
    public function test_carrier_cannot_access_other_carrier_medical_record()
    {
        $this->actingAs($this->carrierUser);
        
        Log::shouldReceive('warning')
            ->once()
            ->with('Carrier attempted to access unauthorized medical record', \Mockery::type('array'));
        
        $response = $this->get(route('carrier.medical-records.show', $this->otherMedicalRecord));
        
        $response->assertStatus(403);
    }

    /**
     * Test that authorizeMedicalRecord blocks unauthorized access on show
     */
    public function test_authorize_medical_record_blocks_unauthorized_access_on_show()
    {
        $this->actingAs($this->carrierUser);
        
        Log::shouldReceive('warning')
            ->once()
            ->with('Carrier attempted to access unauthorized medical record', \Mockery::type('array'));
        
        $response = $this->get(route('carrier.medical-records.show', $this->otherMedicalRecord));
        
        $response->assertStatus(403);
        $response->assertSee('Unauthorized access to medical record');
    }

    /**
     * Test that authorizeMedicalRecord blocks unauthorized access on edit
     */
    public function test_authorize_medical_record_blocks_unauthorized_access_on_edit()
    {
        $this->actingAs($this->carrierUser);
        
        Log::shouldReceive('warning')
            ->once()
            ->with('Carrier attempted to access unauthorized medical record', \Mockery::type('array'));
        
        $response = $this->get(route('carrier.medical-records.edit', $this->otherMedicalRecord));
        
        $response->assertStatus(403);
    }

    /**
     * Test that authorizeMedicalRecord blocks unauthorized access on update
     */
    public function test_authorize_medical_record_blocks_unauthorized_access_on_update()
    {
        $this->actingAs($this->carrierUser);
        
        Log::shouldReceive('warning')
            ->once()
            ->with('Carrier attempted to access unauthorized medical record', \Mockery::type('array'));
        
        $response = $this->put(route('carrier.medical-records.update', $this->otherMedicalRecord), [
            'user_driver_detail_id' => $this->otherDriver->id,
            'medical_examiner_name' => 'Updated Name',
        ]);
        
        $response->assertStatus(403);
    }

    /**
     * Test that authorizeMedicalRecord blocks unauthorized access on destroy
     */
    public function test_authorize_medical_record_blocks_unauthorized_access_on_destroy()
    {
        $this->actingAs($this->carrierUser);
        
        Log::shouldReceive('warning')
            ->once()
            ->with('Carrier attempted to access unauthorized medical record', \Mockery::type('array'));
        
        $response = $this->delete(route('carrier.medical-records.destroy', $this->otherMedicalRecord));
        
        $response->assertStatus(403);
    }

    /**
     * Test that carrier cannot create medical record for other carrier's driver
     */
    public function test_carrier_cannot_create_medical_record_for_other_carrier_driver()
    {
        $this->actingAs($this->carrierUser);
        
        Log::shouldReceive('warning')
            ->once()
            ->with('Carrier attempted to create medical record for unauthorized driver', \Mockery::type('array'));
        
        $response = $this->post(route('carrier.medical-records.store'), [
            'user_driver_detail_id' => $this->otherDriver->id,
            'medical_examiner_name' => 'Dr. Test',
            'medical_card_expiration_date' => now()->addDays(30)->format('Y-m-d'),
        ]);
        
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    /**
     * Test that carrier cannot update medical record with other carrier's driver
     */
    public function test_carrier_cannot_update_medical_record_with_other_carrier_driver()
    {
        $this->actingAs($this->carrierUser);
        
        Log::shouldReceive('warning')
            ->once()
            ->with('Carrier attempted to update medical record with unauthorized driver', \Mockery::type('array'));
        
        $response = $this->put(route('carrier.medical-records.update', $this->medicalRecord), [
            'user_driver_detail_id' => $this->otherDriver->id,
            'medical_examiner_name' => 'Updated Name',
        ]);
        
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    /**
     * Test that logging captures successful access
     */
    public function test_logging_captures_successful_access()
    {
        $this->actingAs($this->carrierUser);
        
        Log::shouldReceive('info')
            ->once()
            ->with('Carrier accessed medical records', \Mockery::type('array'));
        
        $response = $this->get(route('carrier.medical-records.index'));
        
        $response->assertStatus(200);
    }

    /**
     * Test that logging captures unauthorized access attempts
     */
    public function test_logging_captures_unauthorized_access_attempts()
    {
        $this->actingAs($this->carrierUser);
        
        Log::shouldReceive('warning')
            ->once()
            ->with('Carrier attempted to access unauthorized medical record', \Mockery::on(function ($context) {
                return isset($context['carrier_id']) 
                    && isset($context['user_id'])
                    && isset($context['medical_record_id'])
                    && isset($context['record_carrier_id']);
            }));
        
        $response = $this->get(route('carrier.medical-records.show', $this->otherMedicalRecord));
        
        $response->assertStatus(403);
    }

    /**
     * Test that logging captures create operations
     */
    public function test_logging_captures_create_operations()
    {
        $this->actingAs($this->carrierUser);
        
        Log::shouldReceive('info')
            ->once()
            ->with('Carrier accessed create medical record form', \Mockery::type('array'));
        
        $response = $this->get(route('carrier.medical-records.create'));
        
        $response->assertStatus(200);
    }

    /**
     * Test that statistics only include carrier's records
     */
    public function test_statistics_only_include_carrier_records()
    {
        $this->actingAs($this->carrierUser);
        
        // Create records with different statuses for this carrier
        $activeDriver = UserDriverDetail::create([
            'user_id' => User::factory()->create()->id,
            'carrier_id' => $this->carrier->id,
            'last_name' => 'ActiveDriver',
            'phone' => '2222222222',
            'date_of_birth' => '1993-01-01',
            'status' => 1,
        ]);
        
        DriverMedicalQualification::create([
            'user_driver_detail_id' => $activeDriver->id,
            'medical_card_expiration_date' => now()->addDays(60), // Active
        ]);
        
        $expiringDriver = UserDriverDetail::create([
            'user_id' => User::factory()->create()->id,
            'carrier_id' => $this->carrier->id,
            'last_name' => 'ExpiringDriver',
            'phone' => '3333333333',
            'date_of_birth' => '1994-01-01',
            'status' => 1,
        ]);
        
        DriverMedicalQualification::create([
            'user_driver_detail_id' => $expiringDriver->id,
            'medical_card_expiration_date' => now()->addDays(15), // Expiring
        ]);
        
        $response = $this->get(route('carrier.medical-records.index'));
        
        $response->assertStatus(200);
        $response->assertViewHas('totalCount', 3); // Should only count this carrier's records
        $response->assertViewHas('activeCount', 2);
        $response->assertViewHas('expiringCount', 1);
    }

    /**
     * Test that driver filter only shows carrier's drivers
     */
    public function test_driver_filter_only_shows_carrier_drivers()
    {
        $this->actingAs($this->carrierUser);
        
        $response = $this->get(route('carrier.medical-records.index'));
        
        $response->assertStatus(200);
        $response->assertViewHas('drivers');
        
        $drivers = $response->viewData('drivers');
        
        // All drivers should belong to the authenticated carrier
        foreach ($drivers as $driver) {
            $this->assertEquals($this->carrier->id, $driver->carrier_id);
        }
    }

    /**
     * Test that search results are filtered by carrier_id
     */
    public function test_search_results_are_filtered_by_carrier_id()
    {
        $this->actingAs($this->carrierUser);
        
        $response = $this->get(route('carrier.medical-records.index', [
            'search_term' => 'Dr.'
        ]));
        
        $response->assertStatus(200);
        $response->assertSee($this->medicalRecord->medical_examiner_name);
        $response->assertDontSee($this->otherMedicalRecord->medical_examiner_name);
    }

    /**
     * Test that carrier can access own medical record
     */
    public function test_carrier_can_access_own_medical_record()
    {
        $this->actingAs($this->carrierUser);
        
        Log::shouldReceive('info')
            ->once()
            ->with('Carrier accessed medical record details', \Mockery::type('array'));
        
        $response = $this->get(route('carrier.medical-records.show', $this->medicalRecord));
        
        $response->assertStatus(200);
        $response->assertSee($this->medicalRecord->medical_examiner_name);
    }

    /**
     * Test that carrier can edit own medical record
     */
    public function test_carrier_can_edit_own_medical_record()
    {
        $this->actingAs($this->carrierUser);
        
        Log::shouldReceive('info')
            ->once()
            ->with('Carrier accessed edit medical record form', \Mockery::type('array'));
        
        $response = $this->get(route('carrier.medical-records.edit', $this->medicalRecord));
        
        $response->assertStatus(200);
    }

    /**
     * Test that carrier can delete own medical record
     */
    public function test_carrier_can_delete_own_medical_record()
    {
        $this->actingAs($this->carrierUser);
        
        Log::shouldReceive('info')
            ->once()
            ->with('Carrier deleted medical record', \Mockery::type('array'));
        
        $response = $this->delete(route('carrier.medical-records.destroy', $this->medicalRecord));
        
        $response->assertRedirect(route('carrier.medical-records.index'));
        $response->assertSessionHas('success');
        
        $this->assertDatabaseMissing('driver_medical_qualifications', [
            'id' => $this->medicalRecord->id,
        ]);
    }
}
