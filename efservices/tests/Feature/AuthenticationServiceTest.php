<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Carrier;
use App\Models\UserDriverDetail;
use App\Models\UserCarrierDetail;
use App\Services\AuthenticationService;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Tests for AuthenticationService
 * 
 * These tests validate the correctness properties defined in the design document:
 * - Property 1: Valid Credentials Authentication
 * - Property 2: Invalid Credentials Rejection
 * - Property 3: Inactive User Blocking
 * - Property 4: Comprehensive Authentication Logging
 * - Property 5: Admin Role Redirect
 * - Property 6: Carrier Role Redirect
 * - Property 7: Driver Role Redirect
 */
class AuthenticationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AuthenticationService $authService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles needed for tests
        Role::findOrCreate('user_carrier', 'web');
        Role::findOrCreate('user_driver', 'web');
        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('superadmin', 'web');
        
        $this->authService = new AuthenticationService();
    }

    // =========================================================================
    // Property 1: Valid Credentials Authentication
    // For any user with status=1 and valid password, authentication SHALL succeed
    // Validates: Requirements 1.1
    // =========================================================================

    /** @test */
    public function valid_credentials_authenticate_active_user()
    {
        $user = User::factory()->create([
            'status' => 1,
            'password' => Hash::make('test-password-123')
        ]);

        $result = $this->authService->authenticate(
            $user->email,
            'test-password-123',
            '127.0.0.1',
            'PHPUnit Test'
        );

        $this->assertNotNull($result);
        $this->assertEquals($user->id, $result->id);
    }

    /** @test */
    public function valid_credentials_authenticate_superadmin()
    {
        $user = User::factory()->create(['status' => 1]);
        $user->assignRole('superadmin');

        $result = $this->authService->authenticate(
            $user->email,
            'password', // Default factory password
            '127.0.0.1',
            'PHPUnit Test'
        );

        $this->assertNotNull($result);
        $this->assertTrue($result->hasRole('superadmin'));
    }

    /** @test */
    public function valid_credentials_authenticate_carrier_user()
    {
        $user = User::factory()->create(['status' => 1]);
        $user->assignRole('user_carrier');

        $result = $this->authService->authenticate(
            $user->email,
            'password',
            '127.0.0.1',
            'PHPUnit Test'
        );

        $this->assertNotNull($result);
        $this->assertTrue($result->hasRole('user_carrier'));
    }

    /** @test */
    public function valid_credentials_authenticate_driver_user()
    {
        $user = User::factory()->create(['status' => 1]);
        $user->assignRole('user_driver');

        $result = $this->authService->authenticate(
            $user->email,
            'password',
            '127.0.0.1',
            'PHPUnit Test'
        );

        $this->assertNotNull($result);
        $this->assertTrue($result->hasRole('user_driver'));
    }

    // =========================================================================
    // Property 2: Invalid Credentials Rejection
    // For any authentication attempt with non-existent email OR wrong password,
    // authentication SHALL fail and return null
    // Validates: Requirements 1.2, 5.1, 5.4
    // =========================================================================

    /** @test */
    public function invalid_email_returns_null()
    {
        $result = $this->authService->authenticate(
            'nonexistent@example.com',
            'any-password',
            '127.0.0.1',
            'PHPUnit Test'
        );

        $this->assertNull($result);
    }

    /** @test */
    public function invalid_password_returns_null()
    {
        $user = User::factory()->create(['status' => 1]);

        $result = $this->authService->authenticate(
            $user->email,
            'wrong-password',
            '127.0.0.1',
            'PHPUnit Test'
        );

        $this->assertNull($result);
    }

    /** @test */
    public function empty_email_returns_null()
    {
        $result = $this->authService->authenticate(
            '',
            'password',
            '127.0.0.1',
            'PHPUnit Test'
        );

        $this->assertNull($result);
    }

    /** @test */
    public function empty_password_returns_null()
    {
        $user = User::factory()->create(['status' => 1]);

        $result = $this->authService->authenticate(
            $user->email,
            '',
            '127.0.0.1',
            'PHPUnit Test'
        );

        $this->assertNull($result);
    }

    // =========================================================================
    // Property 3: Inactive User Blocking
    // For any user with status=0, authentication SHALL be rejected with a
    // ValidationException containing the deactivation message
    // Validates: Requirements 1.3, 3.1, 3.2, 3.3, 5.2
    // =========================================================================

    /** @test */
    public function inactive_user_throws_validation_exception()
    {
        $user = User::factory()->create(['status' => 0]);

        $this->expectException(ValidationException::class);

        $this->authService->authenticate(
            $user->email,
            'password',
            '127.0.0.1',
            'PHPUnit Test'
        );
    }

    /** @test */
    public function inactive_user_exception_contains_deactivation_message()
    {
        $user = User::factory()->create(['status' => 0]);

        try {
            $this->authService->authenticate(
                $user->email,
                'password',
                '127.0.0.1',
                'PHPUnit Test'
            );
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $this->assertArrayHasKey('email', $errors);
            $this->assertStringContainsString('deactivated', $errors['email'][0]);
        }
    }

    /** @test */
    public function inactive_carrier_cannot_authenticate()
    {
        $user = User::factory()->create(['status' => 0]);
        $user->assignRole('user_carrier');

        $this->expectException(ValidationException::class);

        $this->authService->authenticate(
            $user->email,
            'password',
            '127.0.0.1',
            'PHPUnit Test'
        );
    }

    /** @test */
    public function inactive_driver_cannot_authenticate()
    {
        $user = User::factory()->create(['status' => 0]);
        $user->assignRole('user_driver');

        $this->expectException(ValidationException::class);

        $this->authService->authenticate(
            $user->email,
            'password',
            '127.0.0.1',
            'PHPUnit Test'
        );
    }

    /** @test */
    public function inactive_admin_cannot_authenticate()
    {
        $user = User::factory()->create(['status' => 0]);
        $user->assignRole('admin');

        $this->expectException(ValidationException::class);

        $this->authService->authenticate(
            $user->email,
            'password',
            '127.0.0.1',
            'PHPUnit Test'
        );
    }

    // =========================================================================
    // Property 4: Comprehensive Authentication Logging
    // For any authentication attempt, the system SHALL create a log entry
    // Validates: Requirements 1.4, 1.5, 4.1, 4.2, 4.3
    // =========================================================================

    /** @test */
    public function successful_authentication_logs_attempt_and_success()
    {
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'AUTH_ATTEMPT' && 
                       isset($context['email']) && 
                       isset($context['ip']) &&
                       isset($context['user_agent']);
            });

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'AUTH_SUCCESS' && 
                       isset($context['user_id']) && 
                       isset($context['roles']);
            });

        $user = User::factory()->create(['status' => 1]);

        $this->authService->authenticate(
            $user->email,
            'password',
            '192.168.1.1',
            'Mozilla/5.0'
        );
    }

    /** @test */
    public function failed_authentication_logs_attempt_and_failure()
    {
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message) {
                return $message === 'AUTH_ATTEMPT';
            });

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'AUTH_FAILED' && 
                       $context['reason'] === AuthenticationService::REASON_USER_NOT_FOUND;
            });

        $this->authService->authenticate(
            'nonexistent@example.com',
            'password',
            '192.168.1.1',
            'Mozilla/5.0'
        );
    }

    /** @test */
    public function invalid_password_logs_correct_reason()
    {
        Log::shouldReceive('info')->once();
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'AUTH_FAILED' && 
                       $context['reason'] === AuthenticationService::REASON_INVALID_PASSWORD;
            });

        $user = User::factory()->create(['status' => 1]);

        $this->authService->authenticate(
            $user->email,
            'wrong-password',
            '192.168.1.1',
            'Mozilla/5.0'
        );
    }

    /** @test */
    public function inactive_user_logs_correct_reason()
    {
        Log::shouldReceive('info')->once();
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'AUTH_FAILED' && 
                       $context['reason'] === AuthenticationService::REASON_INACTIVE_USER;
            });

        $user = User::factory()->create(['status' => 0]);

        try {
            $this->authService->authenticate(
                $user->email,
                'password',
                '192.168.1.1',
                'Mozilla/5.0'
            );
        } catch (ValidationException $e) {
            // Expected
        }
    }

    // =========================================================================
    // Property 5: Admin Role Redirect
    // For any authenticated user with role 'superadmin' OR 'admin',
    // the redirect destination SHALL be the admin dashboard route
    // Validates: Requirements 2.1
    // =========================================================================

    /** @test */
    public function superadmin_redirects_to_admin_dashboard()
    {
        $user = User::factory()->create(['status' => 1]);
        $user->assignRole('superadmin');

        $redirect = $this->authService->determineRedirect($user);

        $this->assertEquals(route('dashboard'), $redirect);
    }

    /** @test */
    public function admin_redirects_to_admin_dashboard()
    {
        $user = User::factory()->create(['status' => 1]);
        $user->assignRole('admin');

        $redirect = $this->authService->determineRedirect($user);

        $this->assertEquals(route('dashboard'), $redirect);
    }

    // =========================================================================
    // Property 6: Carrier Role Redirect
    // For any authenticated user with role 'user_carrier', the redirect
    // destination SHALL be determined by carrier status
    // Validates: Requirements 2.2, 2.3
    // =========================================================================

    /** @test */
    public function carrier_with_active_carrier_redirects_to_dashboard()
    {
        $user = User::factory()->create(['status' => 1]);
        $user->assignRole('user_carrier');

        // Note: document_status enum only allows: 'pending', 'in_progress', 'skipped'
        // 'skipped' means documents were skipped, carrier is active
        $carrier = Carrier::factory()->create([
            'status' => Carrier::STATUS_ACTIVE,
            'document_status' => Carrier::DOCUMENT_STATUS_SKIPPED
        ]);

        $user->carrierDetails()->create([
            'carrier_id' => $carrier->id,
            'phone' => '555-123-4567',
            'job_position' => 'Manager',
            'status' => 1,
        ]);

        $redirect = $this->authService->determineRedirect($user);

        $this->assertEquals(route('carrier.dashboard'), $redirect);
    }

    /** @test */
    public function carrier_with_pending_carrier_redirects_to_pending_validation()
    {
        $user = User::factory()->create(['status' => 1]);
        $user->assignRole('user_carrier');

        $carrier = Carrier::factory()->create([
            'status' => Carrier::STATUS_PENDING
        ]);

        $user->carrierDetails()->create([
            'carrier_id' => $carrier->id,
            'phone' => '555-123-4567',
            'job_position' => 'Manager',
            'status' => 1,
        ]);

        $redirect = $this->authService->determineRedirect($user);

        $this->assertEquals(route('carrier.pending.validation'), $redirect);
    }

    /** @test */
    public function carrier_without_carrier_details_redirects_to_wizard()
    {
        $user = User::factory()->create(['status' => 1]);
        $user->assignRole('user_carrier');

        $redirect = $this->authService->determineRedirect($user);

        $this->assertEquals(route('carrier.wizard.step2'), $redirect);
    }

    /** @test */
    public function carrier_with_documents_in_progress_redirects_to_documents()
    {
        $user = User::factory()->create(['status' => 1]);
        $user->assignRole('user_carrier');

        $carrier = Carrier::factory()->create([
            'status' => Carrier::STATUS_ACTIVE,
            'document_status' => Carrier::DOCUMENT_STATUS_IN_PROGRESS
        ]);

        $user->carrierDetails()->create([
            'carrier_id' => $carrier->id,
            'phone' => '555-123-4567',
            'job_position' => 'Manager',
            'status' => 1,
        ]);

        $redirect = $this->authService->determineRedirect($user);

        $this->assertEquals(route('carrier.documents.index', $carrier->slug), $redirect);
    }

    // =========================================================================
    // Property 7: Driver Role Redirect
    // For any authenticated user with role 'user_driver', the redirect
    // destination SHALL be determined by driver profile status
    // Validates: Requirements 2.4, 2.5
    // =========================================================================

    /** @test */
    public function driver_with_active_profile_redirects_to_dashboard()
    {
        $user = User::factory()->create(['status' => 1]);
        $user->assignRole('user_driver');

        $carrier = Carrier::factory()->create([
            'status' => Carrier::STATUS_ACTIVE
        ]);

        UserDriverDetail::factory()->create([
            'user_id' => $user->id,
            'carrier_id' => $carrier->id,
            'status' => UserDriverDetail::STATUS_ACTIVE,
        ]);

        $redirect = $this->authService->determineRedirect($user);

        $this->assertEquals(route('driver.dashboard'), $redirect);
    }

    /** @test */
    public function driver_without_driver_details_redirects_to_complete_registration()
    {
        $user = User::factory()->create(['status' => 1]);
        $user->assignRole('user_driver');

        $redirect = $this->authService->determineRedirect($user);

        $this->assertEquals(route('driver.complete_registration'), $redirect);
    }

    /** @test */
    public function driver_with_inactive_carrier_redirects_to_pending()
    {
        $user = User::factory()->create(['status' => 1]);
        $user->assignRole('user_driver');

        $carrier = Carrier::factory()->create([
            'status' => Carrier::STATUS_PENDING
        ]);

        UserDriverDetail::factory()->create([
            'user_id' => $user->id,
            'carrier_id' => $carrier->id,
            'status' => UserDriverDetail::STATUS_ACTIVE,
        ]);

        $redirect = $this->authService->determineRedirect($user);

        $this->assertEquals(route('driver.pending'), $redirect);
    }

    // =========================================================================
    // User without role
    // =========================================================================

    /** @test */
    public function user_without_role_redirects_to_home()
    {
        $user = User::factory()->create(['status' => 1]);
        // No role assigned

        $redirect = $this->authService->determineRedirect($user);

        $this->assertEquals('/', $redirect);
    }

    // =========================================================================
    // Property 8: Session Regeneration on Login
    // For any successful authentication, the session ID after login SHALL be
    // different from the session ID before login
    // Validates: Requirements 6.1
    // =========================================================================

    /** @test */
    public function session_id_changes_after_successful_login()
    {
        $user = User::factory()->create(['status' => 1]);
        $user->assignRole('admin');

        // Get initial session ID by making a request
        $initialResponse = $this->get('/login');
        $initialSessionId = session()->getId();

        // Perform login
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        // Get new session ID after login
        $newSessionId = session()->getId();

        // Session ID should be different (regenerated)
        $this->assertNotEquals($initialSessionId, $newSessionId);
    }

    /** @test */
    public function session_regeneration_occurs_for_carrier_login()
    {
        $user = User::factory()->create(['status' => 1]);
        $user->assignRole('user_carrier');

        // Get initial session ID
        $this->get('/login');
        $initialSessionId = session()->getId();

        // Perform login
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        // Session ID should be regenerated
        $this->assertNotEquals($initialSessionId, session()->getId());
    }

    /** @test */
    public function session_regeneration_occurs_for_driver_login()
    {
        $user = User::factory()->create(['status' => 1]);
        $user->assignRole('user_driver');

        // Get initial session ID
        $this->get('/login');
        $initialSessionId = session()->getId();

        // Perform login
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        // Session ID should be regenerated
        $this->assertNotEquals($initialSessionId, session()->getId());
    }

    // =========================================================================
    // Property 9: Session Invalidation on Logout
    // For any logout action, the session SHALL be completely invalidated
    // and the user SHALL no longer be authenticated
    // Validates: Requirements 6.2
    // =========================================================================

    /** @test */
    public function session_is_invalidated_on_logout()
    {
        $user = User::factory()->create(['status' => 1]);
        $user->assignRole('admin');

        // Login first
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        // Verify user is authenticated
        $this->assertAuthenticated();

        // Get session ID before logout
        $sessionIdBeforeLogout = session()->getId();

        // Perform logout
        $this->post('/logout');

        // Verify user is no longer authenticated
        $this->assertGuest();
    }

    /** @test */
    public function user_cannot_access_protected_routes_after_logout()
    {
        $user = User::factory()->create(['status' => 1]);
        $user->assignRole('admin');

        // Login
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();

        // Logout
        $this->post('/logout');

        // Try to access protected route - should redirect to login
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    /** @test */
    public function session_data_is_cleared_on_logout()
    {
        $user = User::factory()->create(['status' => 1]);
        $user->assignRole('admin');

        // Login
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        // Store some session data
        session(['test_key' => 'test_value']);
        $this->assertEquals('test_value', session('test_key'));

        // Logout
        $this->post('/logout');

        // Session data should be cleared
        $this->assertNull(session('test_key'));
    }

    // =========================================================================
    // Property 10: Remember Token Creation
    // For any successful authentication with "remember me" enabled,
    // a remember token SHALL be created and stored for the user
    // Validates: Requirements 6.4
    // =========================================================================

    /** @test */
    public function remember_token_is_created_when_remember_me_is_checked()
    {
        $user = User::factory()->create([
            'status' => 1,
            'remember_token' => null
        ]);
        $user->assignRole('admin');

        // Verify no remember token initially
        $this->assertNull($user->remember_token);

        // Login with remember me
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'remember' => 'on',
        ]);

        // Refresh user from database
        $user->refresh();

        // Remember token should be set
        $this->assertNotNull($user->remember_token);
    }

    /** @test */
    public function remember_token_is_not_created_without_remember_me()
    {
        $user = User::factory()->create([
            'status' => 1,
            'remember_token' => null
        ]);
        $user->assignRole('admin');

        // Login without remember me
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        // Refresh user from database
        $user->refresh();

        // Remember token should still be null
        $this->assertNull($user->remember_token);
    }

    /** @test */
    public function remember_cookie_is_set_when_remember_me_is_checked()
    {
        $user = User::factory()->create(['status' => 1]);
        $user->assignRole('admin');

        // Login with remember me
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'remember' => 'on',
        ]);

        // Check that remember cookie is set
        $response->assertCookie(\Illuminate\Support\Facades\Auth::guard()->getRecallerName());
    }
}
