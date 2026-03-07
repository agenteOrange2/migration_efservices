<?php

namespace App\Services;

use App\Models\User;
use App\Models\Carrier;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Centralized authentication service for all user types.
 * Handles authentication, status validation, logging, and redirect determination.
 */
class AuthenticationService
{
    // Authentication failure reasons
    const REASON_USER_NOT_FOUND = 'user_not_found';
    const REASON_INVALID_PASSWORD = 'invalid_password';
    const REASON_INACTIVE_USER = 'inactive_user';
    
    // Error messages
    const MSG_INVALID_CREDENTIALS = 'The provided credentials do not match our records.';
    const MSG_ACCOUNT_DEACTIVATED = 'Your account has been deactivated. Please contact support.';
    const MSG_RATE_LIMITED = 'Too many login attempts. Please try again later.';

    /**
     * Authenticate a user with email and password.
     * 
     * @param string $email
     * @param string $password
     * @param string $ip
     * @param string $userAgent
     * @return User|null Returns User on success, null on invalid credentials
     * @throws ValidationException When user account is inactive
     */
    public function authenticate(string $email, string $password, string $ip, string $userAgent): ?User
    {
        $this->logAttempt($email, $ip, $userAgent);
        
        // Step 1: Find user by email
        $user = User::where('email', $email)->first();
        
        // Step 2: Validate user exists
        if (!$user) {
            $this->logFailure($email, null, self::REASON_USER_NOT_FOUND, $ip);
            return null;
        }
        
        // Step 3: Validate password
        if (!Hash::check($password, $user->password)) {
            $this->logFailure($email, $user->id, self::REASON_INVALID_PASSWORD, $ip);
            return null;
        }
        
        // Step 4: Validate user status BEFORE creating session
        if ($user->status != 1) {
            $this->logFailure($email, $user->id, self::REASON_INACTIVE_USER, $ip);
            
            throw ValidationException::withMessages([
                'email' => [self::MSG_ACCOUNT_DEACTIVATED]
            ]);
        }
        
        // Step 5: Authentication successful
        $this->logSuccess($user, $ip);
        
        return $user;
    }

    /**
     * Determine the redirect URL after successful authentication.
     * 
     * @param User $user
     * @return string The route URL to redirect to
     */
    public function determineRedirect(User $user): string
    {
        // Superadmin - highest priority
        if ($user->hasRole('superadmin')) {
            Log::info('AUTH_REDIRECT', [
                'user_id' => $user->id,
                'destination' => 'admin_dashboard',
                'role' => 'superadmin'
            ]);
            return route('admin.dashboard');
        }
        
        // Carrier
        if ($user->hasRole('user_carrier')) {
            return $this->getCarrierRedirect($user);
        }
        
        // Driver
        if ($user->hasRole('user_driver')) {
            return $this->getDriverRedirect($user);
        }
        
        // No recognized role - log warning
        Log::warning('AUTH_REDIRECT_NO_ROLE', [
            'user_id' => $user->id,
            'email' => $user->email,
            'roles' => $user->getRoleNames()->toArray(),
            'destination' => 'home'
        ]);
        
        return '/';
    }

    /**
     * Determine redirect URL for carrier users.
     * 
     * @param User $user
     * @return string
     */
    private function getCarrierRedirect(User $user): string
    {
        $carrierDetails = $user->carrierDetails;
        
        // No carrier details or no carrier_id - needs to complete registration
        if (!$carrierDetails || !$carrierDetails->carrier_id) {
            Log::info('AUTH_REDIRECT', [
                'user_id' => $user->id,
                'destination' => 'carrier_wizard_step2',
                'reason' => 'no_carrier_details'
            ]);
            return route('carrier.wizard.step2');
        }
        
        $carrier = $carrierDetails->carrier;
        
        // No carrier record found
        if (!$carrier) {
            Log::info('AUTH_REDIRECT', [
                'user_id' => $user->id,
                'destination' => 'carrier_wizard_step2',
                'reason' => 'carrier_not_found'
            ]);
            return route('carrier.wizard.step2');
        }
        
        // Redirect based on carrier status
        switch ($carrier->status) {
            case Carrier::STATUS_PENDING:
                Log::info('AUTH_REDIRECT', [
                    'user_id' => $user->id,
                    'carrier_id' => $carrier->id,
                    'destination' => 'carrier_pending_validation',
                    'carrier_status' => 'pending'
                ]);
                return route('carrier.pending.validation');
                
            case Carrier::STATUS_INACTIVE:
                Log::info('AUTH_REDIRECT', [
                    'user_id' => $user->id,
                    'carrier_id' => $carrier->id,
                    'destination' => 'login',
                    'carrier_status' => 'inactive'
                ]);
                return route('login');
                
            case Carrier::STATUS_ACTIVE:
                // Check if documents are in progress
                if ($carrier->document_status === Carrier::DOCUMENT_STATUS_IN_PROGRESS) {
                    Log::info('AUTH_REDIRECT', [
                        'user_id' => $user->id,
                        'carrier_id' => $carrier->id,
                        'destination' => 'carrier_documents',
                        'carrier_status' => 'active',
                        'document_status' => 'in_progress'
                    ]);
                    return route('carrier.documents.index', $carrier->slug);
                }
                
                Log::info('AUTH_REDIRECT', [
                    'user_id' => $user->id,
                    'carrier_id' => $carrier->id,
                    'destination' => 'carrier_dashboard',
                    'carrier_status' => 'active'
                ]);
                return route('carrier.dashboard');
                
            default:
                Log::info('AUTH_REDIRECT', [
                    'user_id' => $user->id,
                    'carrier_id' => $carrier->id,
                    'destination' => 'carrier_wizard_step2',
                    'carrier_status' => $carrier->status,
                    'reason' => 'unknown_status'
                ]);
                return route('carrier.wizard.step2');
        }
    }

    /**
     * Determine redirect URL for driver users.
     * 
     * @param User $user
     * @return string
     */
    private function getDriverRedirect(User $user): string
    {
        $driverDetails = $user->driverDetails;
        
        // No driver details - needs to complete registration
        if (!$driverDetails) {
            Log::info('AUTH_REDIRECT', [
                'user_id' => $user->id,
                'destination' => 'driver_complete_registration',
                'reason' => 'no_driver_details'
            ]);
            return route('driver.complete_registration');
        }
        
        // Check if driver has an active carrier
        $carrier = $driverDetails->carrier;
        
        if ($carrier && $carrier->status == Carrier::STATUS_ACTIVE) {
            // Check driver's own status
            if ($driverDetails->status == \App\Models\UserDriverDetail::STATUS_ACTIVE) {
                Log::info('AUTH_REDIRECT', [
                    'user_id' => $user->id,
                    'driver_id' => $driverDetails->id,
                    'carrier_id' => $carrier->id,
                    'destination' => 'driver_dashboard'
                ]);
                return route('driver.dashboard');
            }
        }
        
        // Driver pending or carrier not active
        Log::info('AUTH_REDIRECT', [
            'user_id' => $user->id,
            'driver_id' => $driverDetails->id ?? null,
            'destination' => 'driver_pending',
            'reason' => 'driver_or_carrier_not_active'
        ]);
        return route('driver.pending');
    }

    /**
     * Log authentication attempt.
     */
    private function logAttempt(string $email, string $ip, string $userAgent): void
    {
        Log::info('AUTH_ATTEMPT', [
            'email' => $email,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'timestamp' => now()->toIso8601String()
        ]);
    }

    /**
     * Log authentication failure.
     */
    private function logFailure(string $email, ?int $userId, string $reason, string $ip): void
    {
        Log::warning('AUTH_FAILED', [
            'email' => $email,
            'user_id' => $userId,
            'reason' => $reason,
            'ip' => $ip,
            'timestamp' => now()->toIso8601String()
        ]);
    }

    /**
     * Log successful authentication.
     */
    private function logSuccess(User $user, string $ip): void
    {
        Log::info('AUTH_SUCCESS', [
            'user_id' => $user->id,
            'email' => $user->email,
            'roles' => $user->getRoleNames()->toArray(),
            'ip' => $ip,
            'timestamp' => now()->toIso8601String()
        ]);
    }
}
