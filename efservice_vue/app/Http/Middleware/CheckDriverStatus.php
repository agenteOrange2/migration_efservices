<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Carrier;
use Illuminate\Http\Request;
use App\Models\UserDriverDetail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Admin\Driver\DriverApplication;
use Symfony\Component\HttpFoundation\Response;

class CheckDriverStatus
{
    /**
     * Handle an incoming request for driver users.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user || !$user->hasRole('user_driver')) {
            return redirect()->route('login');
        }

        // Check if user account is active
        if ($user->status != 1) {
            Auth::logout();
            return redirect()->route('login')
                ->withErrors(['email' => 'Your account has been deactivated. Please contact support.']);
        }

        // ──────────────────────────────────────────────────────────────
        // Carrier gate — a driver's access must mirror the parent carrier's
        // status. If the carrier is inactive/pending/rejected or its banking
        // has been rejected, the driver cannot reach the portal regardless
        // of their own driver/application status.
        // ──────────────────────────────────────────────────────────────
        if ($carrierRedirect = $this->enforceCarrierGate($request, $user)) {
            return $carrierRedirect;
        }

        // Skip validation for setup/public routes
        if ($this->isDriverSetupRoute($request)) {
            return $next($request);
        }

        Log::info('Driver status check', [
            'user_id' => $user->id,
            'has_driver_details' => $user->driverDetails ? 'yes' : 'no',
            'path' => $request->path()
        ]);

        // Ensure driver registration is complete
        if (!$user->driverDetails) {
            return redirect()->route('driver.complete_registration')
                ->with('warning', 'Please complete your initial registration.');
        }

        $driverDetail = $user->driverDetails;
        $application = $user->driverApplication;

        // Create application if it doesn't exist
        if (!$application) {
            $application = DriverApplication::create([
                'user_id' => $user->id,
                'status' => DriverApplication::STATUS_DRAFT
            ]);
            Log::info('Created driver application', [
                'user_id' => $user->id,
                'application_id' => $application->id
            ]);
        }

        // Check driver detail status
        if ($driverDetail->status != UserDriverDetail::STATUS_ACTIVE) {
            return redirect()->route('driver.pending')
                ->with('warning', 'Your driver account is pending approval.');
        }

        // Validate application status
        return $this->handleApplicationStatus($request, $next, $user, $driverDetail, $application);
    }

    /**
     * Handle driver application status validation
     */
    private function handleApplicationStatus(
        Request $request,
        Closure $next,
        $user,
        UserDriverDetail $driverDetail,
        DriverApplication $application
    ): Response {
        Log::info('Driver application check', [
            'user_id' => $user->id,
            'application_status' => $application->status,
            'application_completed' => $driverDetail->application_completed,
            'current_step' => $driverDetail->current_step
        ]);

        // Handle DRAFT status
        if ($application->status === DriverApplication::STATUS_DRAFT &&
            !$driverDetail->application_completed) {

            $step = $driverDetail->current_step ?? 1;

            return redirect()->route('driver.registration.continue', ['step' => $step])
                ->with('info', 'Please complete your application to continue.');
        }

        // Handle PENDING status
        if ($application->status === DriverApplication::STATUS_PENDING &&
            !$request->is('driver/pending') &&
            !$request->is('driver/profile') &&
            !$request->is('logout')) {

            return redirect()->route('driver.pending')
                ->with('info', 'Your application is under review. We will notify you once it has been processed.');
        }

        // Handle REJECTED status
        if ($application->status === DriverApplication::STATUS_REJECTED &&
            !$request->is('driver/rejected') &&
            !$request->is('driver/profile') &&
            !$request->is('logout')) {

            return redirect()->route('driver.rejected')
                ->with('error', 'Your application has been rejected. Please contact support for more information.');
        }

        // Handle APPROVED status with missing documents
        if ($application->status === DriverApplication::STATUS_APPROVED &&
            !$driverDetail->hasRequiredDocuments() &&
            !$request->is('driver/documents*') &&
            !$request->is('driver/profile') &&
            !$request->is('logout')) {

            return redirect()->route('driver.documents.pending')
                ->with('warning', 'Please upload all required documents to complete your registration.');
        }

        // Prevent access to admin and carrier areas
        if ($request->is('admin*') || $request->is('carrier*')) {
            return redirect()->route('driver.dashboard')
                ->with('warning', 'Access denied to this area.');
        }

        return $next($request);
    }

    /**
     * Block the driver when the parent carrier is not fully operational.
     * Allowed escape hatches: /driver/pending (to see the status),
     * /driver/rejected, and logout.
     */
    private function enforceCarrierGate(Request $request, $user): ?Response
    {
        $driverDetail = $user->driverDetails;
        if (!$driverDetail) {
            return null; // no driver record yet — handled by later checks
        }

        $carrier = $driverDetail->carrier;
        if (!$carrier) {
            return null; // nothing to gate against
        }

        $onAllowedRoute = $request->is('driver/pending')
            || $request->is('driver/rejected')
            || $request->is('logout');

        // CARRIER INACTIVE — fully blocked
        if ((int) $carrier->status === Carrier::STATUS_INACTIVE) {
            if ($onAllowedRoute) return null;

            Log::warning('Driver blocked — carrier inactive', [
                'user_id'    => $user->id,
                'carrier_id' => $carrier->id,
            ]);

            return redirect()->route('driver.pending')
                ->with('error', 'Your carrier account is currently inactive. You cannot access the portal until your carrier is reactivated.')
                ->with('status_code', 'carrier_inactive');
        }

        // CARRIER REJECTED — fully blocked
        if ((int) $carrier->status === Carrier::STATUS_REJECTED) {
            if ($onAllowedRoute) return null;

            Log::warning('Driver blocked — carrier rejected', [
                'user_id'    => $user->id,
                'carrier_id' => $carrier->id,
            ]);

            return redirect()->route('driver.pending')
                ->with('error', "Your carrier's registration was rejected. Please contact support for more information.")
                ->with('status_code', 'carrier_rejected');
        }

        // CARRIER PENDING APPROVAL / PENDING VALIDATION — blocked until activated
        if (in_array((int) $carrier->status, [Carrier::STATUS_PENDING, Carrier::STATUS_PENDING_VALIDATION], true)) {
            if ($onAllowedRoute) return null;

            Log::info('Driver blocked — carrier pending', [
                'user_id'       => $user->id,
                'carrier_id'    => $carrier->id,
                'carrier_status'=> $carrier->status,
            ]);

            return redirect()->route('driver.pending')
                ->with('warning', 'Your carrier is pending approval. You will be able to access the portal once your carrier is activated.')
                ->with('status_code', 'carrier_pending');
        }

        // CARRIER ACTIVE — also require approved banking before letting drivers in
        if ((int) $carrier->status === Carrier::STATUS_ACTIVE) {
            $banking = $carrier->bankingDetails;

            if ($banking && $banking->isRejected()) {
                if ($onAllowedRoute) return null;

                Log::warning('Driver blocked — carrier banking rejected', [
                    'user_id'    => $user->id,
                    'carrier_id' => $carrier->id,
                ]);

                return redirect()->route('driver.pending')
                    ->with('error', "Your carrier's payment information was rejected. Please contact your carrier administrator.")
                    ->with('status_code', 'carrier_banking_rejected');
            }

            if ($banking && $banking->isPending()) {
                if ($onAllowedRoute) return null;

                Log::info('Driver blocked — carrier banking pending', [
                    'user_id'    => $user->id,
                    'carrier_id' => $carrier->id,
                ]);

                return redirect()->route('driver.pending')
                    ->with('warning', "Your carrier's payment information is being validated. You will be able to access the portal once validation completes.")
                    ->with('status_code', 'carrier_banking_pending');
            }
        }

        return null;
    }

    /**
     * Check if route is part of driver setup process
     */
    private function isDriverSetupRoute(Request $request): bool
    {
        $setupRoutes = [
            'driver/complete_registration',
            'driver/registration/*',
            'driver/registration/continue',
            'driver/application/wizard',
            'driver/application/wizard/*',
            'driver/application/employment/*',
            'driver/pending',
            'driver/rejected',
            'driver/documents/*',
            'driver/profile',
            'driver/logout',
            'logout',
            'livewire/*'
        ];

        // Routes that require full validation
        $nonSetupRoutes = [
            'driver/dashboard',
            'driver/loads/*',
            'driver/profile/edit'
        ];

        // Block non-setup routes
        foreach ($nonSetupRoutes as $route) {
            if ($request->is($route)) {
                return false;
            }
        }

        // Allow setup routes
        foreach ($setupRoutes as $route) {
            if ($request->is($route)) {
                return true;
            }
        }

        return false;
    }
}
