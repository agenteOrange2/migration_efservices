<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Carrier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckCarrierStatus
{
    /**
     * Handle an incoming request for carrier users.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user || !$user->hasRole('user_carrier')) {
            return redirect()->route('login');
        }

        // Check if user account is active
        if ($user->status != 1) {
            Auth::logout();
            return redirect()->route('login')
                ->withErrors(['email' => 'Your account has been deactivated. Please contact support.']);
        }

        // Allow access to wizard if carrier is already complete and active
        if ($this->shouldRedirectFromWizard($request, $user)) {
            return redirect()->route('carrier.dashboard')
                ->with('info', 'You have already completed the registration process.');
        }

        // Skip validation for setup/public routes
        if ($this->isCarrierSetupRoute($request)) {
            return $next($request);
        }

        // Ensure carrier registration is complete
        if (!$user->carrierDetails || !$user->carrierDetails->carrier_id) {
            Log::warning('Carrier registration incomplete', [
                'user_id' => $user->id,
                'path' => $request->path()
            ]);

            return redirect()->route('carrier.wizard.step2')
                ->with('warning', 'Please complete your registration first.');
        }

        // Check user_carrier status
        if ($user->carrierDetails->status != 1) {
            return redirect()->route('carrier.pending')
                ->with('warning', 'Your user account is pending approval.');
        }

        // Validate carrier status
        $carrier = $user->carrierDetails->carrier()->first();
        return $this->handleCarrierStatus($request, $next, $user, $carrier);
    }

    /**
     * Check if user should be redirected from wizard to dashboard
     */
    private function shouldRedirectFromWizard(Request $request, $user): bool
    {
        if (!$request->is('carrier/wizard*') || !$user->carrierDetails || !$user->carrierDetails->carrier_id) {
            return false;
        }

        $carrier = $user->carrierDetails->carrier()->first();

        // Redirect if carrier is active
        if ($carrier && $carrier->status === Carrier::STATUS_ACTIVE) {
            return true;
        }

        // Redirect if wizard is complete (has plan and banking), except for step4
        if ($carrier && $carrier->id_plan && !$request->is('carrier/wizard/step4')) {
            return $carrier->bankingDetails()->exists();
        }

        return false;
    }

    /**
     * Handle carrier status validation and redirects
     */
    private function handleCarrierStatus(Request $request, Closure $next, $user, Carrier $carrier): Response
    {
        Log::info('Carrier status check', [
            'user_id' => $user->id,
            'carrier_id' => $carrier->id,
            'status' => $carrier->status,
            'path' => $request->path()
        ]);

        // Handle PENDING status
        if ($carrier->status === Carrier::STATUS_PENDING &&
            !$request->is('carrier/pending-validation') &&
            !$request->is('carrier/*/documents*') &&
            !$request->is('logout')) {

            return redirect()->route('carrier.pending.validation')
                ->with('info', 'Your account is pending administrative validation.');
        }

        // Handle INACTIVE status
        if ($carrier->status === Carrier::STATUS_INACTIVE &&
            !$request->is('carrier/inactive') &&
            !$request->is('carrier/request-reactivation') &&
            !$request->is('logout')) {

            return redirect()->route('carrier.inactive')
                ->with('warning', 'Your carrier account is currently inactive.');
        }

        // Handle PENDING_VALIDATION status
        if ($carrier->status === Carrier::STATUS_PENDING_VALIDATION &&
            !$request->is('carrier/pending-validation') &&
            !$request->is('logout')) {

            return redirect()->route('carrier.pending.validation')
                ->with('info', 'Your payment is being validated. Please wait for confirmation.');
        }

        // Handle ACTIVE status with banking validation
        if ($carrier->status === Carrier::STATUS_ACTIVE) {
            return $this->handleActiveCarrierStatus($request, $next, $user, $carrier);
        }

        // Handle other statuses
        if (!in_array($carrier->status, [
            Carrier::STATUS_ACTIVE,
            Carrier::STATUS_PENDING,
            Carrier::STATUS_INACTIVE,
            Carrier::STATUS_PENDING_VALIDATION
        ]) && !$request->is('carrier/*/documents*') &&
            !$request->is('carrier/confirmation') &&
            !$request->is('carrier/wizard*') &&
            !$request->is('logout')) {

            return redirect()->route('carrier.confirmation')
                ->with('warning', 'Your carrier account is pending approval.');
        }

        // Prevent access to admin area
        if ($request->is('admin*')) {
            return redirect()->route('carrier.dashboard')
                ->with('warning', 'Access denied to admin area.');
        }

        return $next($request);
    }

    /**
     * Handle active carrier with banking validation
     */
    private function handleActiveCarrierStatus(Request $request, Closure $next, $user, Carrier $carrier): Response
    {
        $bankingDetails = $carrier->bankingDetails;

        if (!$bankingDetails) {
            if (!$request->is('carrier/wizard*') && !$request->is('logout')) {
                Log::warning('No banking details found', [
                    'user_id' => $user->id,
                    'carrier_id' => $carrier->id
                ]);

                return redirect()->route('carrier.wizard.step4')
                    ->with('warning', 'Please complete your banking information to continue.');
            }
            return $next($request);
        }

        // Handle REJECTED banking
        if ($bankingDetails->isRejected() &&
            !$request->is('carrier/banking-rejected') &&
            !$request->is('logout')) {

            Log::warning('Banking rejected - access blocked', [
                'user_id' => $user->id,
                'carrier_id' => $carrier->id
            ]);

            return redirect()->route('carrier.banking.rejected')
                ->with('error', 'Your banking information has been rejected. Please update your payment method.');
        }

        // Handle PENDING banking
        if ($bankingDetails->isPending() &&
            !$request->is('carrier/pending-validation') &&
            !$request->is('logout')) {

            return redirect()->route('carrier.pending.validation')
                ->with('info', 'Your banking information is being validated.');
        }

        // Ensure banking is approved
        if (!$bankingDetails->isApproved() &&
            !$request->is('carrier/banking-rejected') &&
            !$request->is('carrier/pending-validation') &&
            !$request->is('logout')) {

            Log::warning('Banking not approved - access blocked', [
                'user_id' => $user->id,
                'carrier_id' => $carrier->id,
                'banking_status' => $bankingDetails->status
            ]);

            return redirect()->route('carrier.pending.validation')
                ->with('warning', 'Your banking information needs to be approved before accessing the dashboard.');
        }

        // Handle document completion requirement
        if ($request->is('carrier/dashboard') &&
            !$carrier->documents_completed &&
            !$request->session()->has('skip_documents_' . $carrier->id)) {

            return redirect()->route('carrier.documents.index', $carrier->slug)
                ->with('info', 'Please upload your carrier documents. You can skip documents you don\'t have ready.');
        }

        return $next($request);
    }

    /**
     * Check if route is part of carrier setup process
     */
    private function isCarrierSetupRoute(Request $request): bool
    {
        // Always allow document routes
        if (preg_match('#^carrier/[^/]+/documents#', $request->path())) {
            return true;
        }

        $setupRoutes = [
            'carrier/complete-registration',
            'carrier/confirmation',
            'carrier/pending',
            'carrier/pending-validation',
            'carrier/inactive',
            'carrier/banking-rejected',
            'carrier/request-reactivation',
            'carrier/register',
            'carrier/confirm/*',
            'carrier/*/documents*',
            'carrier/wizard/step1',
            'carrier/wizard/step2',
            'carrier/wizard/step3',
            'carrier/wizard/step4',
            'carrier/wizard/check-uniqueness',
            'carrier/wizard/check-verification'
        ];

        foreach ($setupRoutes as $route) {
            if ($request->is($route)) {
                return true;
            }
        }

        return false;
    }
}
