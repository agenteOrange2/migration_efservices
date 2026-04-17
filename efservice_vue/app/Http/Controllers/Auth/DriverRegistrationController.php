<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterDriverRequest;
use App\Models\Carrier;
use App\Models\User;
use App\Models\UserDriverDetail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Admin\Driver\DriverApplication;
use App\Notifications\Admin\Driver\NewDriverRegisteredNotification;

class DriverRegistrationController extends Controller
{
    /**
     * Redirect authenticated users to their respective dashboards.
     */
    private function redirectIfAuthenticated(): ?RedirectResponse
    {
        if (! Auth::check()) {
            return null;
        }

        $user = Auth::user();

        if ($user->hasRole('superadmin')) {
            return redirect()->route('admin.dashboard')
                ->with('warning', 'You are already logged in. Please log out first to register as a driver.');
        }

        if ($user->hasRole('user_carrier')) {
            return redirect()->route('carrier.dashboard')
                ->with('warning', 'You are already logged in as a carrier.');
        }

        if ($user->hasRole('user_driver')) {
            $driverDetails = $user->driverDetails;
            if ($driverDetails?->application_completed) {
                return redirect()->route('driver.dashboard')
                    ->with('warning', 'You are already registered as a driver.');
            }
            return null;
        }

        return redirect()->route('login')
            ->with('warning', 'Please log out first to register as a driver.');
    }

    /**
     * Show registration form for drivers arriving via carrier referral link.
     */
    public function showRegistrationForm(Request $request, Carrier $carrier): Response|RedirectResponse
    {
        if ($redirect = $this->redirectIfAuthenticated()) {
            return $redirect;
        }

        $token         = $request->route('token') ?? $request->query('token');
        $isIndependent = empty($token);

        $carrierData = $carrier->only(['id', 'name', 'slug', 'status', 'address', 'state']);
        $carrierData['logo_url'] = $carrier->getFirstMediaUrl('logo_carrier') ?: null;

        if ($carrier->status !== Carrier::STATUS_ACTIVE) {
            return Inertia::render('driver/registration/CarrierInactiveError', [
                'carrier' => $carrierData,
            ]);
        }

        if (! $isIndependent && ! $this->validateTokenAndCarrier($carrier, $token)) {
            if ($carrier->referrer_token === $token) {
                return Inertia::render('driver/registration/CarrierInactiveError', [
                    'carrier' => $carrierData,
                ]);
            }
            return redirect()->route('driver.register.error');
        }

        return Inertia::render('driver/registration/Register', [
            'carrier'       => $carrierData,
            'isIndependent' => $isIndependent,
            'token'         => $token,
        ]);
    }

    /**
     * Show carrier selection for independent driver registration.
     */
    public function showIndependentCarrierSelection(): Response|RedirectResponse
    {
        if ($redirect = $this->redirectIfAuthenticated()) {
            return $redirect;
        }

        $carriers = Carrier::where('status', Carrier::STATUS_ACTIVE)
            ->with(['membership:id,name,max_drivers'])
            ->get()
            ->map(function ($carrier) {
                $driverCount = $carrier->userDrivers()->count();
                $maxDrivers  = $carrier->membership->max_drivers ?? 1;

                return [
                    'id' => $carrier->id,
                    'name' => $carrier->name,
                    'slug' => $carrier->slug,
                    'address' => $carrier->address,
                    'state' => $carrier->state,
                    'logo_url' => $carrier->getFirstMediaUrl('logo_carrier') ?: null,
                    'driver_count' => $driverCount,
                    'max_drivers' => $maxDrivers,
                    'is_full' => $driverCount >= $maxDrivers,
                    'membership_name' => $carrier->membership->name ?? null,
                ];
            });

        $states = Carrier::where('status', Carrier::STATUS_ACTIVE)
            ->distinct()
            ->orderBy('state')
            ->pluck('state')
            ->filter()
            ->values();

        return Inertia::render('driver/registration/SelectCarrier', [
            'carriers' => $carriers,
            'states'   => $states,
        ]);
    }

    /**
     * Show registration form for independent drivers with a selected carrier.
     */
    public function showIndependentRegistrationForm(string $carrierSlug): Response|RedirectResponse
    {
        if ($redirect = $this->redirectIfAuthenticated()) {
            return $redirect;
        }

        $carrier = Carrier::where('slug', $carrierSlug)->with('membership:id,max_drivers')->firstOrFail();

        $carrierData = $carrier->only(['id', 'name', 'slug', 'status', 'address', 'state']);
        $carrierData['logo_url'] = $carrier->getFirstMediaUrl('logo_carrier') ?: null;

        if ($carrier->status !== Carrier::STATUS_ACTIVE) {
            return Inertia::render('driver/registration/CarrierInactiveError', [
                'carrier' => $carrierData,
            ]);
        }

        $driverCount = $carrier->userDrivers()->count();
        $maxDrivers  = $carrier->membership->max_drivers ?? 1;

        if ($driverCount >= $maxDrivers) {
            return Inertia::render('driver/registration/CarrierLimitError', [
                'carrier'      => $carrierData,
                'driver_count' => $driverCount,
                'max_drivers'  => $maxDrivers,
            ]);
        }

        return Inertia::render('driver/registration/Register', [
            'isIndependent' => true,
            'carrier'       => $carrierData,
            'token'         => null,
        ]);
    }

    /**
     * Process driver registration (with carrier referral).
     */
    public function register(RegisterDriverRequest $request, string $carrierSlug): RedirectResponse
    {
        $carrier = Carrier::where('slug', $carrierSlug)
            ->where('referrer_token', $request->token)
            ->firstOrFail();

        $validated = $request->validated();

        $user          = $this->createUser($validated);
        $driverDetails = $this->createDriverDetails($user, $validated, $carrier->id);

        $this->notifyNewDriverRegistration($user, $carrier);

        return $this->loginAndRedirectToWizard($user);
    }

    /**
     * Process independent driver registration (with selected carrier).
     */
    public function registerIndependent(RegisterDriverRequest $request): RedirectResponse
    {
        try {
            $validated = $request->validated();
            $carrier   = Carrier::where('slug', $validated['carrier_slug'])->firstOrFail();

            $user          = $this->createUser($validated);
            $driverDetails = $this->createDriverDetails($user, $validated, $carrier->id);

            $this->notifyNewDriverRegistration($user, $carrier);

            return $this->loginAndRedirectToWizard($user);
        } catch (\Exception $e) {
            Log::error('Error in registerIndependent', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Error processing registration. Please try again.']);
        }
    }

    /**
     * Confirm driver email.
     */
    public function confirmEmail(string $token): RedirectResponse
    {
        $driver = UserDriverDetail::where('confirmation_token', $token)->firstOrFail();

        // Clear the token on the driver record
        $driver->update(['confirmation_token' => null]);

        // Mark the User account as email-verified (email_verified_at lives on users table)
        $driver->user()->update(['email_verified_at' => now()]);

        return redirect()->route('login')
            ->with('success', 'Email confirmed successfully. Please log in to complete your registration.');
    }

    /**
     * Log the newly registered driver in and send them directly to the
     * application wizard. The driver fills their application immediately
     * after registration — admin review happens only after they submit.
     */
    private function loginAndRedirectToWizard(User $user): RedirectResponse
    {
        Auth::login($user);

        request()->session()->regenerate();

        // Create the DriverApplication in DRAFT status so the wizard can open.
        $driverDetails = $user->driverDetails;
        if ($driverDetails && ! $user->driverApplication) {
            DriverApplication::create([
                'user_id' => $user->id,
                'status'  => DriverApplication::STATUS_DRAFT,
            ]);
        }

        Log::info('Driver auto-login after registration', ['user_id' => $user->id]);

        return redirect()
            ->route('driver.application.wizard', ['step' => 1])
            ->with('success', 'Account created! Please complete your application below.');
    }

    private function validateTokenAndCarrier(Carrier $carrier, ?string $token): bool
    {
        if ($carrier->referrer_token !== $token) {
            return false;
        }

        if ($carrier->status !== Carrier::STATUS_ACTIVE) {
            return false;
        }

        if ($carrier->userDrivers()->count() >= ($carrier->membership->max_drivers ?? 1)) {
            return false;
        }

        return true;
    }

    private function createUser(array $data): User
    {
        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user->assignRole('user_driver');

        return $user;
    }

    private function createDriverDetails(User $user, array $data, ?int $carrierId = null): UserDriverDetail
    {
        return $user->driverDetails()->create([
            'carrier_id'         => $carrierId,
            'middle_name'        => $data['middle_name'] ?? null,
            'last_name'          => $data['last_name'],
            'date_of_birth'      => $data['date_of_birth'],
            'license_number'     => $data['license_number'] ?? null,
            'phone'              => $data['phone'],
            'status'             => UserDriverDetail::STATUS_PENDING,
            'confirmation_token' => Str::random(32),
            'current_step'       => 1,
        ]);
    }

    private function notifyNewDriverRegistration(User $user, Carrier $carrier): void
    {
        try {
            $notification = new NewDriverRegisteredNotification($user, $carrier);

            $admins = User::role('superadmin')->get();
            foreach ($admins as $admin) {
                $admin->notify($notification);
            }

            $carrierUsers = $carrier->userCarriers()->with('user')->get();
            foreach ($carrierUsers as $carrierDetail) {
                $carrierDetail->user?->notify($notification);
            }

            Log::info('New driver registration notifications sent', [
                'driver_user_id' => $user->id,
                'carrier_id'     => $carrier->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send driver registration notifications', [
                'driver_user_id' => $user->id,
                'carrier_id'     => $carrier->id,
                'error'          => $e->getMessage(),
            ]);
        }
    }
}
