<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Admin\Driver\DriverAdminWizardController;
use App\Models\Admin\Driver\DriverApplication;
use App\Models\Admin\Driver\DriverEmploymentCompany;
use App\Models\Admin\Driver\LicenseEndorsement;
use App\Models\Admin\Vehicle\VehicleType;
use App\Helpers\Constants;
use App\Models\UserDriverDetail;
use App\Services\Driver\StepCompletionCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Driver-facing application wizard.
 *
 * Extends DriverAdminWizardController to reuse all 15 step-save methods
 * and data formatters. The key differences from the admin version:
 *  - Driver is always resolved from the authenticated session (no URL param for GET).
 *  - The carrier is locked — the driver cannot change it.
 *  - Ownership is enforced on every write.
 *  - On final submission (step 15) the application status changes to PENDING,
 *    after which CheckDriverStatus middleware redirects the driver to the pending page.
 */
class DriverApplicationWizardController extends DriverAdminWizardController
{
    // =========================================================================
    // Context overrides
    // =========================================================================

    protected function isCarrierContext(Request $request): bool
    {
        return false;
    }

    protected function wizardPage(Request $request): string
    {
        // Uses RegistrationLayout (no sidebar) — driver hasn't been approved yet.
        // The component wraps admin/drivers/wizard/Wizard.vue as a child so
        // Inertia applies RegistrationLayout instead of RazeLayout.
        return 'driver/registration/ApplicationWizard';
    }

    protected function wizardRouteNames(Request $request): array
    {
        return [
            'index'                     => 'driver.dashboard',
            'create'                    => 'driver.application.wizard',
            'store'                     => 'driver.application.wizard',
            'edit'                      => 'driver.application.wizard',
            'updateStep'                => 'driver.application.wizard.update-step',
            'employmentSearchCompanies' => 'driver.application.employment.search-companies',
            'employmentSendEmail'       => 'driver.application.employment.send-email',
            'employmentResendEmail'     => 'driver.application.employment.resend-email',
            'employmentMarkEmailStatus' => 'driver.application.employment.mark-email-status',
        ];
    }

    // =========================================================================
    // GET /driver/application/wizard
    // =========================================================================

    public function show(Request $request): Response|RedirectResponse
    {
        $driver = $this->resolveAuthDriver();

        // Ensure a DriverApplication exists before showing the wizard
        if (! $driver->application) {
            DriverApplication::create([
                'user_id' => $driver->user_id,
                'status'  => DriverApplication::STATUS_DRAFT,
            ]);
            $driver->refresh();
        }

        $driver->load([
            'licenses',
            'medicalQualification',
            'trainingSchools',
            'courses',
            'testings',
            'inspections',
            'accidents',
            'trafficConvictions',
            'employmentCompanies',
            'application',
            'application.addresses',
            'application.details',
        ]);

        // Drivers may only visit steps they have already completed + the next one.
        $maxAllowed  = ($driver->current_step ?? 0) + 1;
        $requested   = $request->integer('step', $driver->current_step ?? 1);
        $initialStep = max(1, min($requested, $maxAllowed, 15));

        return Inertia::render($this->wizardPage($request), [
            'driver'          => $this->formatDriverBase($driver),
            'stepData'        => $this->buildAllStepData($driver),
            'carriers'        => [['id' => $driver->carrier_id, 'name' => $driver->carrier?->name ?? '']],
            'selectedCarrierId' => $driver->carrier_id,
            'initialStep'     => $initialStep,
            'carrierLocked'   => true,
            'vehicles'        => $this->loadDriverVehicles($driver->id),
            'vehicleTypes'    => VehicleType::pluck('name'),
            'usStates'        => Constants::usStates(),
            'driverPositions' => Constants::driverPositions(),
            'referralSources' => Constants::referralSources(),
            'endorsements'    => LicenseEndorsement::where('is_active', true)
                                    ->select('id', 'code', 'name')
                                    ->orderBy('code')
                                    ->get()
                                    ->toArray(),
            'equipmentTypes'  => Constants::equipmentTypes(),
            'routeNames'      => $this->wizardRouteNames($request),
        ]);
    }

    // =========================================================================
    // POST|PUT /driver/application/wizard/{driver}/{step}
    // =========================================================================

    public function updateStep(Request $request, UserDriverDetail $driver, int $step): RedirectResponse
    {
        $this->guardOwnership($driver);

        Log::info('DRIVER_WIZARD_UPDATE_STEP', [
            'user_id'   => Auth::id(),
            'driver_id' => $driver->id,
            'step'      => $step,
        ]);

        try {
            match ($step) {
                1  => $this->saveStep1($request, $driver),
                2  => $this->saveStep2($request, $driver),
                3  => $this->saveStep3($request, $driver),
                4  => $this->saveStep4($request, $driver),
                5  => $this->saveStep5($request, $driver),
                6  => $this->saveStep6($request, $driver),
                7  => $this->saveStep7($request, $driver),
                8  => $this->saveStep8($request, $driver),
                9  => $this->saveStep9($request, $driver),
                10 => $this->saveStep10($request, $driver),
                11 => $this->saveStep11($request, $driver),
                12 => $this->saveStep12($request, $driver),
                13 => $this->saveStep13($request, $driver),
                14 => $this->saveStep14($request, $driver),
                15 => $this->saveStep15($request, $driver),
                default => null,
            };
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('DRIVER_WIZARD_VALIDATION_FAILED', [
                'step'   => $step,
                'errors' => $e->errors(),
            ]);
            throw $e;
        } catch (\Throwable $e) {
            Log::error('DRIVER_WIZARD_STEP_ERROR', [
                'step'    => $step,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }

        // Advance current_step if the driver is moving forward
        if ($step >= ($driver->current_step ?? 0)) {
            $driver->update(['current_step' => $step]);
        }

        app(StepCompletionCalculator::class)->invalidateCache($driver->id);

        // After step 15 the application is PENDING.
        // CheckDriverStatus will redirect to driver.pending automatically.
        if ($step >= 15) {
            return redirect()
                ->route('driver.pending')
                ->with('success', 'Your application has been submitted successfully. It is now under review.');
        }

        $nextStep = $step + 1;

        return redirect()
            ->route('driver.application.wizard', ['step' => $nextStep])
            ->with('success', "Step {$step} saved. Continue to step {$nextStep}.");
    }

    // =========================================================================
    // Employment verification proxies
    // =========================================================================

    public function employmentSearchCompanies(Request $request)
    {
        // Delegate to the shared employment controller
        return app(\App\Http\Controllers\Admin\Driver\DriverEmploymentController::class)
            ->searchCompanies($request);
    }

    public function employmentSendEmail(Request $request, UserDriverDetail $driver, DriverEmploymentCompany $company): \Illuminate\Http\JsonResponse
    {
        $this->guardOwnership($driver);

        return app(\App\Http\Controllers\Admin\Driver\DriverEmploymentController::class)
            ->sendEmail($request, $driver, $company);
    }

    public function employmentResendEmail(Request $request, UserDriverDetail $driver, DriverEmploymentCompany $company): \Illuminate\Http\JsonResponse
    {
        $this->guardOwnership($driver);

        return app(\App\Http\Controllers\Admin\Driver\DriverEmploymentController::class)
            ->resendEmail($request, $driver, $company);
    }

    public function employmentMarkEmailStatus(Request $request, UserDriverDetail $driver, DriverEmploymentCompany $company): \Illuminate\Http\JsonResponse
    {
        $this->guardOwnership($driver);

        return app(\App\Http\Controllers\Admin\Driver\DriverEmploymentController::class)
            ->markEmailStatus($request, $driver, $company);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function resolveAuthDriver(): UserDriverDetail
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        $driver = $user?->driverDetails ?? $user?->driverDetail;

        abort_unless($driver, 403, 'No driver profile associated with this account.');

        $driver->loadMissing(['carrier:id,name', 'user:id,name,email']);

        return $driver;
    }

    private function guardOwnership(UserDriverDetail $driver): void
    {
        abort_unless(
            (int) $driver->user_id === (int) Auth::id(),
            403,
            'You can only update your own application.'
        );
    }
}
