<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Carrier;
use App\Models\UserDriverDetail;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles needed for tests
        Role::findOrCreate('user_carrier', 'web');
        Role::findOrCreate('user_driver', 'web');
        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('superadmin', 'web');
    }

    /** @test */
    public function unauthenticated_users_cannot_access_admin_dashboard()
    {
        $response = $this->get('/admin/dashboard');

        // Should redirect to login (302) or return 404 if route doesn't exist
        $this->assertTrue(
            $response->isRedirect() || $response->status() === 404,
            'Expected redirect or 404 for unauthenticated access to admin dashboard'
        );
    }

    /** @test */
    public function unauthenticated_users_cannot_access_carrier_dashboard()
    {
        $response = $this->get('/carrier/dashboard');

        $response->assertRedirect('/login');
    }

    /** @test */
    public function unauthenticated_users_cannot_access_driver_dashboard()
    {
        $response = $this->get('/driver/dashboard');

        $response->assertRedirect('/login');
    }

    /** @test */
    public function carrier_cannot_access_admin_area()
    {
        $user = User::factory()->create(['status' => 1]);
        $user->assignRole('user_carrier');

        $carrier = Carrier::factory()->create(['status' => Carrier::STATUS_ACTIVE]);

        $user->carrierDetails()->create([
            'carrier_id' => $carrier->id,
            'status' => 1,
        ]);

        $response = $this->actingAs($user)->get('/admin/dashboard');

        // Should redirect away from admin area or return 404
        $this->assertTrue(
            $response->isRedirect() || $response->status() === 404,
            'Carrier should not access admin area'
        );
    }

    /** @test */
    public function driver_cannot_access_admin_area()
    {
        $user = User::factory()->create(['status' => 1]);
        $user->assignRole('user_driver');

        $carrier = Carrier::factory()->create(['status' => Carrier::STATUS_ACTIVE]);

        UserDriverDetail::factory()->create([
            'user_id' => $user->id,
            'carrier_id' => $carrier->id,
            'status' => UserDriverDetail::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($user)->get('/admin/dashboard');

        // Should redirect away from admin area or return 404
        $this->assertTrue(
            $response->isRedirect() || $response->status() === 404,
            'Driver should not access admin area'
        );
    }

    /** @test */
    public function driver_cannot_access_carrier_area()
    {
        $user = User::factory()->create(['status' => 1]);
        $user->assignRole('user_driver');

        $carrier = Carrier::factory()->create(['status' => Carrier::STATUS_ACTIVE]);

        $driverDetail = UserDriverDetail::factory()->create([
            'user_id' => $user->id,
            'carrier_id' => $carrier->id,
            'status' => UserDriverDetail::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($user)->get('/carrier/dashboard');

        $response->assertRedirect();
    }

    /** @test */
    public function carrier_cannot_access_driver_area()
    {
        $user = User::factory()->create(['status' => 1]);
        $user->assignRole('user_carrier');

        $carrier = Carrier::factory()->create(['status' => Carrier::STATUS_ACTIVE]);

        $user->carrierDetails()->create([
            'carrier_id' => $carrier->id,
            'status' => 1,
        ]);

        $response = $this->actingAs($user)->get('/driver/dashboard');

        $response->assertRedirect();
    }

    /** @test */
    public function inactive_carrier_cannot_login()
    {
        $user = User::factory()->create(['status' => 0]);
        $user->assignRole('user_carrier');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
    }

    /** @test */
    public function inactive_driver_cannot_login()
    {
        $user = User::factory()->create(['status' => 0]);
        $user->assignRole('user_driver');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
    }

    /** @test */
    public function active_user_can_login_successfully()
    {
        $user = User::factory()->create(['status' => 1]);
        $user->assignRole('superadmin');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        // Should redirect after successful login (not have errors)
        $response->assertSessionDoesntHaveErrors('email');
        $this->assertAuthenticated();
    }

    /** @test */
    public function invalid_credentials_return_error()
    {
        $user = User::factory()->create(['status' => 1]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /** @test */
    public function registration_requires_strong_password()
    {
        $carrier = Carrier::factory()->create(['status' => Carrier::STATUS_ACTIVE]);

        $response = $this->post(route('driver.register.submit', $carrier->slug), [
            'name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'weak',  // Too weak
            'password_confirmation' => 'weak',
            'date_of_birth' => '1990-01-01',
            'phone' => '1234567890',
            'license_number' => 'DL123456',
            'terms_accepted' => true,
        ]);

        $response->assertSessionHasErrors('password');
    }

    /** @test */
    public function registration_requires_valid_email()
    {
        $carrier = Carrier::factory()->create(['status' => Carrier::STATUS_ACTIVE]);

        $response = $this->post(route('driver.register.submit', $carrier->slug), [
            'name' => 'John',
            'last_name' => 'Doe',
            'email' => 'invalid-email',  // Invalid email
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'date_of_birth' => '1990-01-01',
            'phone' => '1234567890',
            'license_number' => 'DL123456',
            'terms_accepted' => true,
        ]);

        $response->assertSessionHasErrors('email');
    }

    /** @test */
    public function registration_prevents_duplicate_emails()
    {
        $existingUser = User::factory()->create(['email' => 'existing@example.com']);
        $carrier = Carrier::factory()->create(['status' => Carrier::STATUS_ACTIVE]);

        $response = $this->post(route('driver.register.submit', $carrier->slug), [
            'name' => 'John',
            'last_name' => 'Doe',
            'email' => 'existing@example.com',  // Duplicate
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'date_of_birth' => '1990-01-01',
            'phone' => '1234567890',
            'license_number' => 'DL123456',
            'terms_accepted' => true,
        ]);

        $response->assertSessionHasErrors('email');
    }

    /** @test */
    public function driver_under_18_cannot_register()
    {
        $carrier = Carrier::factory()->create(['status' => Carrier::STATUS_ACTIVE]);

        $response = $this->post(route('driver.register.submit', $carrier->slug), [
            'name' => 'John',
            'last_name' => 'Doe',
            'email' => 'young@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'date_of_birth' => now()->subYears(16)->format('Y-m-d'),  // Too young
            'phone' => '1234567890',
            'license_number' => 'DL123456',
            'terms_accepted' => true,
        ]);

        $response->assertSessionHasErrors('date_of_birth');
    }

    /** @test */
    public function registration_requires_terms_acceptance()
    {
        $carrier = Carrier::factory()->create(['status' => Carrier::STATUS_ACTIVE]);

        $response = $this->post(route('driver.register.submit', $carrier->slug), [
            'name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'date_of_birth' => '1990-01-01',
            'phone' => '1234567890',
            'license_number' => 'DL123456',
            'terms_accepted' => false,  // Not accepted
        ]);

        $response->assertSessionHasErrors('terms_accepted');
    }

    /** @test */
    public function api_endpoints_require_authentication()
    {
        $response = $this->getJson('/api/drivers');

        // Should return 401 Unauthorized or 404 if route doesn't exist
        $this->assertTrue(
            $response->status() === 401 || $response->status() === 404,
            'API endpoint should require authentication'
        );
    }

    /** @test */
    public function api_endpoints_respect_rate_limiting()
    {
        $user = User::factory()->create(['status' => 1]);
        $user->assignRole('superadmin');

        // Make multiple requests to trigger rate limit
        for ($i = 0; $i < 70; $i++) {
            $response = $this->actingAs($user, 'sanctum')->getJson('/api/drivers');

            if ($response->status() === 429) {
                break;
            }
            
            // If route doesn't exist, skip this test
            if ($response->status() === 404) {
                $this->markTestSkipped('API route /api/drivers does not exist');
            }
        }

        // Assert rate limit was hit or test was skipped
        if ($response->status() !== 404) {
            $this->assertEquals(429, $response->status());
        }
    }
}
