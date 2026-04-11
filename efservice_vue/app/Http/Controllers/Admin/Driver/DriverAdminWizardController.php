<?php

namespace App\Http\Controllers\Admin\Driver;

use App\Helpers\Constants;
use App\Http\Controllers\Carrier\Concerns\ResolvesCarrierContext;
use App\Services\W9PdfService;
use App\Services\Driver\StepCompletionCalculator;
use App\Http\Controllers\Controller;
use App\Models\Admin\Driver\DriverAccident;
use App\Models\Admin\Driver\DriverApplication;
use App\Models\Admin\Driver\DriverCertification;
use App\Models\Admin\Driver\DriverCompanyPolicy;
use App\Models\Admin\Driver\DriverCriminalHistory;
use App\Models\Admin\Driver\DriverFmcsrData;
use App\Models\Admin\Driver\DriverLicense;
use App\Models\Admin\Driver\DriverMedicalQualification;
use App\Models\Admin\Driver\DriverTrafficConviction;
use App\Models\Admin\Driver\DriverTrainingSchool;
use App\Models\Admin\Driver\DriverCourse;
use App\Models\Admin\Driver\DriverEmploymentCompany;
use App\Models\Admin\Driver\DriverUnemploymentPeriod;
use App\Models\Admin\Driver\DriverExperience;
use App\Models\Admin\Driver\DriverW9Form;
use App\Models\Admin\Driver\LicenseEndorsement;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Admin\Vehicle\VehicleDriverAssignment;
use App\Models\Admin\Vehicle\VehicleType;
use App\Models\Carrier;
use App\Models\CarrierDocument;
use App\Models\DocumentType;
use App\Models\CompanyDriverDetail;
use App\Models\OwnerOperatorDetail;
use App\Models\ThirdPartyDetail;
use App\Models\User;
use App\Models\UserDriverDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DriverAdminWizardController extends Controller
{
    use ResolvesCarrierContext;

    // -------------------------------------------------------------------------
    // GET admin/drivers/wizard/create
    // -------------------------------------------------------------------------
    public function create(Request $request): Response
    {
        $isCarrierContext = $this->isCarrierContext($request);
        $selectedCarrier = $isCarrierContext ? $this->resolveCarrier() : null;
        $carriers = $isCarrierContext
            ? collect([['id' => $selectedCarrier->id, 'name' => $selectedCarrier->name]])
            : Carrier::select('id', 'name')->orderBy('name')->get();

        $selectedCarrierId = $isCarrierContext
            ? $selectedCarrier?->id
            : ($request->integer('carrier_id') ?: null);

        return Inertia::render($this->wizardPage($request), [
            'driver'             => null,
            'stepData'           => null,
            'carriers'           => $carriers,
            'selectedCarrierId'  => $selectedCarrierId,
            'carrierLocked'      => $isCarrierContext,
            'vehicles'           => [], // No driver yet on create — vehicles load after driver is saved
            'vehicleTypes'       => VehicleType::pluck('name'),
            'usStates'           => Constants::usStates(),
            'driverPositions'    => Constants::driverPositions(),
            'referralSources'    => Constants::referralSources(),
            'endorsements'       => LicenseEndorsement::where('is_active', true)->select('id', 'code', 'name')->orderBy('code')->get()->toArray(),
            'equipmentTypes'     => Constants::equipmentTypes(),
            'routeNames'         => $this->wizardRouteNames($request),
        ]);
    }

    // -------------------------------------------------------------------------
    // POST admin/drivers/wizard  (Step 1 – create user + driver)
    // -------------------------------------------------------------------------
    public function store(Request $request)
    {
        if ($this->isCarrierContext($request)) {
            $request->merge([
                'carrier_id' => $this->resolveCarrierId(),
            ]);
        }

        $validated = $request->validate([
            'carrier_id'       => 'required|exists:carriers,id',
            'name'             => 'required|string|max:255',
            'middle_name'      => 'nullable|string|max:255',
            'last_name'        => 'required|string|max:255',
            'email'            => 'required|email|unique:users,email',
            'phone'            => 'required|string|max:20',
            'date_of_birth'    => ['required', 'date', 'before_or_equal:' . now()->subYears(18)->format('m/d/Y')],
            'password'         => 'required|min:8|confirmed',
            'hos_cycle_type'   => 'nullable|string|in:60_7,70_8',
            'photo'            => 'nullable|image|max:10240',
            'status'           => 'nullable|integer|in:0,1,2',
            'terms_accepted'   => 'accepted',
            'use_custom_dates' => 'nullable|boolean',
            'custom_created_at'=> 'nullable|date',
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'name'     => $validated['name'],
                'email'    => $validated['email'],
                'password' => Hash::make($validated['password']),
                'status'   => 1,
            ]);

            $user->assignRole('user_driver');

            $driver = UserDriverDetail::create([
                'user_id'          => $user->id,
                'carrier_id'       => $validated['carrier_id'],
                'middle_name'      => $validated['middle_name'] ?? null,
                'last_name'        => $validated['last_name'],
                'phone'            => $validated['phone'],
                'date_of_birth'    => $this->toDbDate($validated['date_of_birth']),
                'status'           => $validated['status'] ?? UserDriverDetail::STATUS_PENDING,
                'terms_accepted'   => $request->boolean('terms_accepted'),
                'use_custom_dates' => $request->boolean('use_custom_dates'),
                'custom_created_at'=> $this->toDbDate($validated['custom_created_at'] ?? null),
                'current_step'     => 1,
                'confirmation_token' => Str::random(60),
                'created_by_admin' => Auth::id(),
                'hos_cycle_type'   => $validated['hos_cycle_type'] ?? '70_8',
            ]);

            if ($request->hasFile('photo')) {
                $ext = $request->file('photo')->getClientOriginalExtension() ?: 'jpg';
                $driver->addMedia($request->file('photo'))
                    ->usingFileName('profile.' . $ext)
                    ->toMediaCollection('profile_photo_driver');
            }

            // Create draft application
            DriverApplication::create([
                'user_id' => $user->id,
                'status'  => 'draft',
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create driver: ' . $e->getMessage()]);
        }

        return redirect()
            ->to(route($this->wizardRouteNames($request)['edit'], $driver) . '?step=2')
            ->with('success', 'Driver created. Continue filling in the remaining steps.');
    }

    // -------------------------------------------------------------------------
    // GET admin/drivers/{driver}/wizard
    // -------------------------------------------------------------------------
    public function edit(Request $request, UserDriverDetail $driver): Response
    {
        if ($this->isCarrierContext($request)) {
            abort_unless((int) $driver->carrier_id === (int) $this->resolveCarrierId(), 403);
        }

        $driver->load([
            'user',
            'carrier:id,name',
            'licenses.endorsements',
            'medicalQualification',
            'trainingSchools',
            'courses',
            'trafficConvictions',
            'accidents',
            'fmcsrData',
            'employmentCompanies.masterCompany',
            'unemploymentPeriods',
            'companyPolicy',
            'criminalHistory',
            'w9Form',
            'certification',
            'application.addresses',
            'application.details',
        ]);

        $isCarrierContext = $this->isCarrierContext($request);
        $selectedCarrier = $isCarrierContext ? $this->resolveCarrier() : null;
        $carriers = $isCarrierContext
            ? collect([['id' => $selectedCarrier->id, 'name' => $selectedCarrier->name]])
            : Carrier::select('id', 'name')->orderBy('name')->get();

        $initialStep = max(1, min($request->integer('step', $driver->current_step), 15));

        return Inertia::render($this->wizardPage($request), [
            'driver'          => $this->formatDriverBase($driver),
            'stepData'        => $this->buildAllStepData($driver),
            'carriers'        => $carriers,
            'initialStep'     => $initialStep,
            'carrierLocked'   => $isCarrierContext,
            'vehicles'        => $this->loadDriverVehicles($driver->id),
            'vehicleTypes'    => VehicleType::pluck('name'),
            'usStates'        => Constants::usStates(),
            'driverPositions' => Constants::driverPositions(),
            'referralSources' => Constants::referralSources(),
            'endorsements'    => LicenseEndorsement::where('is_active', true)->select('id', 'code', 'name')->orderBy('code')->get()->toArray(),
            'equipmentTypes'  => Constants::equipmentTypes(),
            'routeNames'      => $this->wizardRouteNames($request),
        ]);
    }

    // -------------------------------------------------------------------------
    // PUT admin/drivers/{driver}/wizard/{step}
    // -------------------------------------------------------------------------
    public function updateStep(Request $request, UserDriverDetail $driver, int $step)
    {
        if ($this->isCarrierContext($request)) {
            abort_unless((int) $driver->carrier_id === (int) $this->resolveCarrierId(), 403);
        }

        \Illuminate\Support\Facades\Log::info('UPDATE_STEP_CALLED', [
            'step'      => $step,
            'driver_id' => $driver->id,
            'method'    => $request->method(),
            'input_keys'=> array_keys($request->all()),
            'licenses'  => $request->input('licenses'),
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
            \Illuminate\Support\Facades\Log::warning('STEP_VALIDATION_FAILED', [
                'step'   => $step,
                'errors' => $e->errors(),
            ]);
            throw $e;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('STEP_SAVE_ERROR', [
                'step'    => $step,
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            throw $e;
        }

        // Advance current_step if needed
        if ($step >= $driver->current_step) {
            $driver->update(['current_step' => $step]);
        }

        // Invalidate completion calculator cache so Step 15 shows fresh data
        app(StepCompletionCalculator::class)->invalidateCache($driver->id);

        $nextStep = min($step + 1, 15);

        return redirect()
            ->to(route($this->wizardRouteNames($request)['edit'], $driver) . "?step={$nextStep}")
            ->with('success', "Step {$step} saved successfully.");
    }

    protected function isCarrierContext(Request $request): bool
    {
        return str_starts_with((string) $request->route()?->getName(), 'carrier.');
    }

    protected function wizardPage(Request $request): string
    {
        return $this->isCarrierContext($request)
            ? 'carrier/drivers/Wizard'
            : 'admin/drivers/wizard/Wizard';
    }

    protected function wizardRouteNames(Request $request): array
    {
        if ($this->isCarrierContext($request)) {
            return [
                'index' => 'carrier.drivers.index',
                'create' => 'carrier.drivers.create',
                'store' => 'carrier.drivers.store',
                'edit' => 'carrier.drivers.edit',
                'updateStep' => 'carrier.drivers.wizard.update-step',
                'employmentSearchCompanies' => 'carrier.drivers.employment.search-companies',
                'employmentSendEmail' => 'carrier.drivers.employment.send-email',
                'employmentResendEmail' => 'carrier.drivers.employment.resend-email',
                'employmentMarkEmailStatus' => 'carrier.drivers.employment.mark-email-status',
            ];
        }

        return [
            'index' => 'admin.drivers.index',
            'create' => 'admin.drivers.wizard.create',
            'store' => 'admin.drivers.wizard.store',
            'edit' => 'admin.drivers.wizard.edit',
            'updateStep' => 'admin.drivers.wizard.update-step',
            'employmentSearchCompanies' => 'admin.drivers.employment.search-companies',
            'employmentSendEmail' => 'admin.drivers.employment.send-email',
            'employmentResendEmail' => 'admin.drivers.employment.resend-email',
            'employmentMarkEmailStatus' => 'admin.drivers.employment.mark-email-status',
        ];
    }

    // =========================================================================
    // PRIVATE – helpers
    // =========================================================================

    /**
     * Convert MM/DD/YYYY → Y-m-d for MySQL. Passes through Y-m-d and null unchanged.
     */
    private function toDbDate(?string $value): ?string
    {
        if (!$value) return null;
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) return $value;
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $value, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[1], $m[2]);
        }
        return $value; // let validation fail naturally
    }

    // =========================================================================
    // PRIVATE – step savers
    // =========================================================================

    /** Step 1 – General Info */
    private function saveStep1(Request $request, UserDriverDetail $driver): void
    {
        $validated = $request->validate([
            'carrier_id'       => 'required|exists:carriers,id',
            'name'             => 'required|string|max:255',
            'middle_name'      => 'nullable|string|max:255',
            'last_name'        => 'required|string|max:255',
            'email'            => 'required|email|unique:users,email,' . $driver->user_id,
            'phone'            => 'required|string|max:20',
            'date_of_birth'    => 'required|date',
            'password'         => 'nullable|min:8|confirmed',
            'hos_cycle_type'   => 'nullable|string|in:60_7,70_8',
            'photo'            => 'nullable|image|max:10240',
            'status'           => 'nullable|integer|in:0,1,2',
            'use_custom_dates' => 'nullable|boolean',
            'custom_created_at'=> 'nullable|date',
        ]);

        $driver->user->update([
            'name'  => $validated['name'],
            'email' => $validated['email'],
        ]);

        if (!empty($validated['password'])) {
            $driver->user->update(['password' => Hash::make($validated['password'])]);
        }

        $driver->update([
            'carrier_id'       => $validated['carrier_id'],
            'middle_name'      => $validated['middle_name'] ?? null,
            'last_name'        => $validated['last_name'],
            'phone'            => $validated['phone'],
            'date_of_birth'    => $this->toDbDate($validated['date_of_birth']),
            'hos_cycle_type'   => $validated['hos_cycle_type'] ?? $driver->hos_cycle_type ?? '70_8',
            'status'           => $validated['status'] ?? $driver->status,
            'use_custom_dates' => $request->boolean('use_custom_dates'),
            'custom_created_at'=> $this->toDbDate($validated['custom_created_at'] ?? null),
            'updated_by_admin' => Auth::id(),
        ]);

        if ($request->hasFile('photo')) {
            $ext = $request->file('photo')->getClientOriginalExtension() ?: 'jpg';
            $driver->clearMediaCollection('profile_photo_driver');
            $driver->addMedia($request->file('photo'))
                ->usingFileName('profile.' . $ext)
                ->toMediaCollection('profile_photo_driver');
        }
    }

    /** Step 2 – Address */
    private function saveStep2(Request $request, UserDriverDetail $driver): void
    {
        $request->validate([
            'address_line1'   => 'required|string|max:255',
            'city'            => 'required|string|max:100',
            'state'           => 'required|string|max:5',
            'zip_code'        => 'required|string|max:20',
            'from_date'       => 'required|date',
            'to_date'         => 'nullable|date',
            'lived_three_years' => 'boolean',
            'previous_addresses'   => 'nullable|array',
            'previous_addresses.*.address_line1' => 'required|string|max:255',
            'previous_addresses.*.city'          => 'required|string|max:100',
            'previous_addresses.*.state'         => 'required|string|max:5',
            'previous_addresses.*.zip_code'      => 'required|string|max:20',
            'previous_addresses.*.from_date'     => 'required|date',
            'previous_addresses.*.to_date'       => 'nullable|date',
        ]);

        $application = $driver->application ?? DriverApplication::create([
            'user_id' => $driver->user_id,
            'status'  => 'draft',
        ]);

        // Primary address
        $application->addresses()->updateOrCreate(
            ['primary' => true],
            [
                'address_line1'    => $request->address_line1,
                'address_line2'    => $request->address_line2,
                'city'             => $request->city,
                'state'            => $request->state,
                'zip_code'         => $request->zip_code,
                'from_date'        => $this->toDbDate($request->from_date),
                'to_date'          => $this->toDbDate($request->to_date),
                'lived_three_years'=> $request->boolean('lived_three_years'),
            ]
        );

        // Previous addresses
        $application->addresses()->where('primary', false)->delete();

        if (!$request->boolean('lived_three_years') && is_array($request->previous_addresses)) {
            foreach ($request->previous_addresses as $addr) {
                if (empty($addr['address_line1'])) continue;
                $application->addresses()->create([
                    'primary'       => false,
                    'address_line1' => $addr['address_line1'],
                    'address_line2' => $addr['address_line2'] ?? null,
                    'city'          => $addr['city'],
                    'state'         => $addr['state'],
                    'zip_code'      => $addr['zip_code'],
                    'from_date'     => $this->toDbDate($addr['from_date']),
                    'to_date'       => $this->toDbDate($addr['to_date'] ?? null),
                    'lived_three_years' => false,
                ]);
            }
        }
    }

    /** Step 3 – Application Details */
    private function saveStep3(Request $request, UserDriverDetail $driver): void
    {
        \Illuminate\Support\Facades\Log::info('STEP3_ENTRY', [
            'driver_id'        => $driver->id,
            'all_input'        => $request->all(),
        ]);

        $assignmentType = $request->vehicle_assignment_type;
        $needsVehicle   = in_array($assignmentType, ['owner_operator', 'third_party']);

        $request->validate([
            'applying_position'       => 'required|string',
            'applying_position_other' => 'nullable|string|max:255',
            'applying_location'       => 'nullable|string|max:255',
            // eligible_to_work is required AND must be true
            'eligible_to_work'        => 'required|accepted',
            'can_speak_english'       => 'boolean',
            'has_twic_card'           => 'boolean',
            'twic_expiration_date'    => 'nullable|date',
            'expected_pay'            => 'nullable|numeric',
            'how_did_hear'            => 'nullable|string',
            'how_did_hear_other'      => 'nullable|string|max:255',
            'referral_employee_name'  => 'nullable|string|max:255',
            // Vehicle assignment type
            'vehicle_assignment_type' => 'required|in:owner_operator,third_party,company',
            // Vehicle & owner/third-party fields only apply for non-company types
            'vehicle_id'              => 'nullable|integer|exists:vehicles,id',
            'owner_name'              => 'required_if:vehicle_assignment_type,owner_operator|nullable|string|max:255',
            'owner_phone'             => 'nullable|string|max:30',
            'owner_email'             => 'nullable|email|max:255',
            'third_party_name'        => 'required_if:vehicle_assignment_type,third_party|nullable|string|max:255',
            'third_party_phone'       => 'nullable|string|max:30',
            'third_party_email'       => 'nullable|email|max:255',
            'third_party_dba'         => 'nullable|string|max:255',
            'third_party_address'     => 'nullable|string|max:255',
            'third_party_contact'     => 'nullable|string|max:255',
            'third_party_fein'        => 'nullable|string|max:30',
            // New vehicle fields (only for owner_operator / third_party)
            'new_vehicle_make'                         => $needsVehicle ? 'nullable|string|max:100' : 'sometimes|nullable',
            'new_vehicle_model'                        => 'nullable|string|max:100',
            'new_vehicle_year'                         => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'new_vehicle_vin'                          => 'nullable|string|max:50',
            'new_vehicle_type'                         => 'nullable|string|max:100',
            'new_vehicle_company_unit_number'          => 'nullable|string|max:50',
            'new_vehicle_gvwr'                         => 'nullable|string|max:50',
            'new_vehicle_tire_size'                    => 'nullable|string|max:50',
            'new_vehicle_fuel_type'                    => 'nullable|string|max:50',
            'new_vehicle_irp_apportioned_plate'        => 'nullable|boolean',
            'new_vehicle_registration_state'           => 'nullable|string|max:2',
            'new_vehicle_registration_number'          => 'nullable|string|max:50',
            'new_vehicle_registration_expiration_date' => 'nullable|date',
            'new_vehicle_permanent_tag'                => 'nullable|boolean',
            'new_vehicle_location'                     => 'nullable|string|max:255',
            'new_vehicle_notes'                        => 'nullable|string|max:1000',
        ], [
            'eligible_to_work.accepted' => 'The applicant must be eligible to work in the United States.',
            'eligible_to_work.required' => 'Eligibility to work in the US is required.',
        ]);

        $application = $driver->application ?? DriverApplication::create([
            'user_id' => $driver->user_id,
            'status'  => 'draft',
        ]);

        \Illuminate\Support\Facades\Log::info('STEP3_DEBUG', [
            'driver_id'         => $driver->id,
            'carrier_id'        => $driver->carrier_id,
            'application_id'    => $application->id,
            'assignment_type'   => $assignmentType,
            'needs_vehicle'     => $needsVehicle,
            'eligible_to_work'  => $request->input('eligible_to_work'),
            'applying_position' => $request->applying_position,
            'input_keys'        => array_keys($request->all()),
        ]);

        // Base fields always saved
        $detailData = [
            'applying_position'       => $request->applying_position,
            'applying_position_other' => $request->applying_position_other,
            'applying_location'       => $request->applying_location ?? '',
            'eligible_to_work'        => true, // validated as required+accepted above
            'can_speak_english'       => $request->boolean('can_speak_english'),
            'has_twic_card'           => $request->boolean('has_twic_card'),
            'twic_expiration_date'    => $this->toDbDate($request->twic_expiration_date),
            'expected_pay'            => $request->expected_pay ?? 0,
            'how_did_hear'            => $request->how_did_hear ?? 'internet',
            'how_did_hear_other'      => $request->how_did_hear_other,
            'referral_employee_name'  => $request->referral_employee_name,
        ];

        // Only save owner/third-party fields for the relevant types
        if ($assignmentType === 'owner_operator') {
            $detailData['owner_name']  = $request->owner_name;
            $detailData['owner_phone'] = $request->owner_phone;
            $detailData['owner_email'] = $request->owner_email;
        } elseif ($assignmentType === 'third_party') {
            $detailData['third_party_name']    = $request->third_party_name;
            $detailData['third_party_phone']   = $request->third_party_phone;
            $detailData['third_party_email']   = $request->third_party_email;
            $detailData['third_party_dba']     = $request->third_party_dba;
            $detailData['third_party_address'] = $request->third_party_address;
            $detailData['third_party_contact'] = $request->third_party_contact;
            $detailData['third_party_fein']    = $request->third_party_fein;
        }

        $details = $application->details()->updateOrCreate(
            ['driver_application_id' => $application->id],
            $detailData
        );

        \Illuminate\Support\Facades\Log::info('STEP3_DETAILS_SAVED', [
            'details_id'    => $details->id,
            'was_created'   => $details->wasRecentlyCreated,
        ]);

        // ── Company type: assignment + CompanyDriverDetail (no vehicle required) ──────
        if (!$needsVehicle) {
            VehicleDriverAssignment::where('user_driver_detail_id', $driver->id)
                ->whereIn('status', ['active', 'pending'])
                ->update(['status' => 'inactive', 'end_date' => now()->toDateString()]);

            $assignment = VehicleDriverAssignment::create([
                'vehicle_id'            => null,
                'user_driver_detail_id' => $driver->id,
                'driver_type'           => 'company',
                'start_date'            => now()->toDateString(),
                'status'                => 'pending',
                'assigned_by'           => Auth::id(),
            ]);

            $company = CompanyDriverDetail::create([
                'vehicle_driver_assignment_id' => $assignment->id,
                'carrier_id'                   => $driver->carrier_id,
            ]);

            $details->update(['vehicle_driver_assignment_id' => $assignment->id]);

            \Illuminate\Support\Facades\Log::info('STEP3_COMPANY_SAVED', [
                'assignment_id'         => $assignment->id,
                'company_detail_id'     => $company->id,
            ]);
            return;
        }

        $vehicleId = $request->integer('vehicle_id') ?: null;

        // Create new vehicle if the form was filled and no existing selected
        if (!$vehicleId && $request->filled('new_vehicle_make')) {
            $vehicle = Vehicle::create([
                'carrier_id'                   => $driver->carrier_id,
                'make'                         => $request->new_vehicle_make,
                'model'                        => $request->new_vehicle_model,
                'year'                         => $request->new_vehicle_year,
                'vin'                          => $request->new_vehicle_vin,
                'type'                         => $request->new_vehicle_type,
                'company_unit_number'          => $request->new_vehicle_company_unit_number,
                'gvwr'                         => $request->new_vehicle_gvwr,
                'tire_size'                    => $request->new_vehicle_tire_size,
                'fuel_type'                    => $request->new_vehicle_fuel_type,
                'irp_apportioned_plate'        => $request->boolean('new_vehicle_irp_apportioned_plate'),
                'registration_state'           => $request->new_vehicle_registration_state,
                'registration_number'          => $request->new_vehicle_registration_number,
                'registration_expiration_date' => $this->toDbDate($request->new_vehicle_registration_expiration_date),
                'permanent_tag'                => $request->boolean('new_vehicle_permanent_tag'),
                'location'                     => $request->new_vehicle_location,
                'notes'                        => $request->new_vehicle_notes,
                'driver_type'                  => $assignmentType,
                'status'                       => 'pending',
            ]);
            $vehicleId = $vehicle->id;
        }

        if ($vehicleId) {
            // End any existing active assignment for this driver
            VehicleDriverAssignment::where('user_driver_detail_id', $driver->id)
                ->whereIn('status', ['active', 'pending'])
                ->update(['status' => 'inactive', 'end_date' => now()->toDateString()]);

            $assignment = VehicleDriverAssignment::create([
                'vehicle_id'            => $vehicleId,
                'user_driver_detail_id' => $driver->id,
                'driver_type'           => $assignmentType,
                'start_date'            => now()->toDateString(),
                'status'                => 'pending',
                'assigned_by'           => Auth::id(),
            ]);

            if ($assignmentType === 'owner_operator') {
                OwnerOperatorDetail::updateOrCreate(
                    ['vehicle_driver_assignment_id' => $assignment->id],
                    [
                        'owner_name'  => $request->owner_name,
                        'owner_phone' => $request->owner_phone,
                        'owner_email' => $request->owner_email,
                    ]
                );
            } elseif ($assignmentType === 'third_party') {
                ThirdPartyDetail::updateOrCreate(
                    ['vehicle_driver_assignment_id' => $assignment->id],
                    [
                        'third_party_name'    => $request->third_party_name,
                        'third_party_phone'   => $request->third_party_phone,
                        'third_party_email'   => $request->third_party_email,
                        'third_party_dba'     => $request->third_party_dba,
                        'third_party_address' => $request->third_party_address,
                        'third_party_contact' => $request->third_party_contact,
                        'third_party_fein'    => $request->third_party_fein,
                    ]
                );

                // Send document signing email
                if ($request->filled('third_party_email')) {
                    try {
                        $veh = Vehicle::find($vehicleId);
                        \Illuminate\Support\Facades\Mail::to($request->third_party_email)
                            ->send(new \App\Mail\ThirdPartyVehicleVerification(
                                thirdPartyName:    $request->third_party_name ?? '',
                                driverName:        $driver->user->name ?? '',
                                vehicleData:       [
                                    'make'  => $veh?->make,
                                    'model' => $veh?->model,
                                    'year'  => $veh?->year,
                                    'vin'   => $veh?->vin,
                                ],
                                verificationToken: \Illuminate\Support\Str::uuid()->toString(),
                                driverId:          $driver->id,
                                applicationId:     $application->id
                            ));

                        $details->update(['email_sent' => true]);
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::warning('Could not send third-party vehicle verification email', [
                            'error'  => $e->getMessage(),
                            'email'  => $request->third_party_email,
                            'driver' => $driver->id,
                        ]);
                    }
                }
            }

            $details->update([
                'vehicle_id'                   => $vehicleId,
                'vehicle_driver_assignment_id' => $assignment->id,
            ]);
        }
    }

    /** Step 4 – License */
    private function saveStep4(Request $request, UserDriverDetail $driver): void
    {
        \Illuminate\Support\Facades\Log::info('STEP4_INPUT', [
            'all' => $request->except(['license_front', 'license_back', '_method']),
            'has_front' => $request->hasFile('license_front'),
            'has_back'  => $request->hasFile('license_back'),
        ]);

        $request->validate([
            'licenses'                     => 'required|array|min:1',
            'licenses.*.license_number'    => 'required|string|max:50',
            'licenses.*.state_of_issue'    => 'required|string|max:5',
            'licenses.*.license_class'     => 'required|string|max:10',
            'licenses.*.expiration_date'   => 'required|date',
            'licenses.*.is_cdl'            => 'boolean',
            'licenses.*.restrictions'      => 'nullable|string|max:255',
            'licenses.*.endorsements'      => 'nullable|array',
            'licenses.*.endorsements.*'    => 'integer|exists:license_endorsements,id',
            'license_front_*'              => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'license_back_*'               => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            // Driving experience
            'experiences'                  => 'nullable|array',
            'experiences.*.equipment_type' => 'required|string|max:100',
            'experiences.*.years_experience' => 'required|integer|min:0',
            'experiences.*.miles_driven'   => 'required|integer|min:0',
            'experiences.*.requires_cdl'   => 'boolean',
        ]);

        \Illuminate\Support\Facades\Log::info('STEP4_AFTER_VALIDATION', [
            'licenses_count' => count($request->licenses ?? []),
            'licenses' => $request->licenses,
        ]);

        // Delete removed licenses
        $incomingIds = collect($request->licenses)->pluck('id')->filter()->values()->toArray();
        $driver->licenses()->whereNotIn('id', $incomingIds)->delete();

        foreach ($request->licenses as $index => $licenseData) {
            \Illuminate\Support\Facades\Log::info('STEP4_LICENSE_ROW', ['index' => $index, 'data' => $licenseData]);
            $isCdl    = isset($licenseData['is_cdl']) && $licenseData['is_cdl'];
            $isPrimary = $index === 0;

            if (!empty($licenseData['id'])) {
                $license = DriverLicense::find($licenseData['id']);
                $license?->update([
                    'license_number'  => $licenseData['license_number'],
                    'state_of_issue'  => $licenseData['state_of_issue'],
                    'license_class'   => $licenseData['license_class'],
                    'expiration_date' => $this->toDbDate($licenseData['expiration_date']),
                    'is_cdl'          => $isCdl,
                    'restrictions'    => $licenseData['restrictions'] ?? null,
                    'is_primary'      => $isPrimary,
                ]);
            } else {
                $license = DriverLicense::create([
                    'user_driver_detail_id' => $driver->id,
                    'license_number'        => $licenseData['license_number'],
                    'state_of_issue'        => $licenseData['state_of_issue'],
                    'license_class'         => $licenseData['license_class'],
                    'expiration_date'       => $this->toDbDate($licenseData['expiration_date']),
                    'is_cdl'                => $isCdl,
                    'restrictions'          => $licenseData['restrictions'] ?? null,
                    'is_primary'            => $isPrimary,
                ]);
            }

            if ($license && !empty($licenseData['endorsements'])) {
                $license->endorsements()->sync($licenseData['endorsements']);
            }

            // License images per license
            if ($license) {
                if ($request->hasFile("license_front_{$index}")) {
                    $file = $request->file("license_front_{$index}");
                    $ext  = $file->getClientOriginalExtension() ?: 'jpg';
                    $license->clearMediaCollection('license_front');
                    $license->addMedia($file)->usingFileName('front.' . $ext)->toMediaCollection('license_front');
                }
                if ($request->hasFile("license_back_{$index}")) {
                    $file = $request->file("license_back_{$index}");
                    $ext  = $file->getClientOriginalExtension() ?: 'jpg';
                    $license->clearMediaCollection('license_back');
                    $license->addMedia($file)->usingFileName('back.' . $ext)->toMediaCollection('license_back');
                }
            }
        }

        // Driving experience — delete and recreate
        $driver->experiences()->delete();
        foreach ($request->experiences ?? [] as $exp) {
            if (empty($exp['equipment_type'])) continue;
            DriverExperience::create([
                'user_driver_detail_id' => $driver->id,
                'equipment_type'        => $exp['equipment_type'],
                'years_experience'      => (int)($exp['years_experience'] ?? 0),
                'miles_driven'          => (int)($exp['miles_driven'] ?? 0),
                'requires_cdl'          => (bool)($exp['requires_cdl'] ?? false),
            ]);
        }
    }

    /** Step 5 – Medical */
    private function saveStep5(Request $request, UserDriverDetail $driver): void
    {
        $request->validate([
            'hire_date'                        => 'nullable|date',
            'location'                         => 'nullable|string|max:255',
            'is_suspended'                     => 'boolean',
            'suspension_date'                  => 'nullable|date',
            'is_terminated'                    => 'boolean',
            'termination_date'                 => 'nullable|date',
            'social_security_number'           => 'nullable|string|max:20',
            'medical_examiner_name'            => 'nullable|string|max:255',
            'medical_examiner_registry_number' => 'nullable|string|max:50',
            'medical_card_expiration_date'     => 'nullable|date',
            'medical_card'                     => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'social_security_card'             => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        $isSuspended = $request->boolean('is_suspended');
        $isTerminated = $request->boolean('is_terminated');

        $medical = DriverMedicalQualification::updateOrCreate(
            ['user_driver_detail_id' => $driver->id],
            [
                'hire_date'                        => $this->toDbDate($request->hire_date),
                'location'                         => $request->location,
                'is_suspended'                     => $isSuspended,
                'suspension_date'                  => $isSuspended ? $this->toDbDate($request->suspension_date) : null,
                'is_terminated'                    => $isTerminated,
                'termination_date'                 => $isTerminated ? $this->toDbDate($request->termination_date) : null,
                'social_security_number'           => $request->social_security_number,
                'medical_examiner_name'            => $request->medical_examiner_name,
                'medical_examiner_registry_number' => $request->medical_examiner_registry_number,
                'medical_card_expiration_date'     => $this->toDbDate($request->medical_card_expiration_date),
            ]
        );

        if ($request->hasFile('medical_card')) {
            $ext = $request->file('medical_card')->getClientOriginalExtension() ?: 'jpg';
            $medical->clearMediaCollection('medical_card');
            $medical->addMedia($request->file('medical_card'))
                ->usingFileName('medical_card.' . $ext)
                ->toMediaCollection('medical_card');
        }

        if ($request->hasFile('social_security_card')) {
            $ext = $request->file('social_security_card')->getClientOriginalExtension() ?: 'jpg';
            $medical->clearMediaCollection('social_security_card');
            $medical->addMedia($request->file('social_security_card'))
                ->usingFileName('social_security_card.' . $ext)
                ->toMediaCollection('social_security_card');
        }
    }

    /** Step 6 – Training */
    private function saveStep6(Request $request, UserDriverDetail $driver): void
    {
        $request->validate([
            'schools'                                  => 'nullable|array',
            'schools.*.school_name'                    => 'required|string|max:255',
            'schools.*.city'                           => 'nullable|string|max:100',
            'schools.*.state'                          => 'nullable|string|max:5',
            'schools.*.graduated'                      => 'boolean',
            'schools.*.date_start'                     => 'nullable|date',
            'schools.*.date_end'                       => 'nullable|date',
            'schools.*.subject_to_safety_regulations'  => 'boolean',
            'schools.*.performed_safety_functions'     => 'boolean',
            'schools.*.training_skills'                => 'nullable|array',
            'schools.*.training_skills.*'              => 'string|max:100',
            'school_certificates'                      => 'nullable|array',
            'school_certificates.*'                    => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'courses'                                  => 'nullable|array',
            'courses.*.organization_name'              => 'required|string|max:255',
            'courses.*.city'                           => 'nullable|string|max:100',
            'courses.*.state'                          => 'nullable|string|max:5',
            'courses.*.certification_date'             => 'nullable|date',
            'courses.*.expiration_date'                => 'nullable|date',
            'courses.*.experience'                     => 'nullable|string|max:255',
            'courses.*.years_experience'               => 'nullable|numeric',
            'course_certificates'                      => 'nullable|array',
            'course_certificates.*'                    => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        // Training schools – upsert by ID to preserve existing files
        $incomingSchoolIds = collect($request->schools ?? [])->pluck('id')->filter()->values()->toArray();
        $driver->trainingSchools()->whereNotIn('id', $incomingSchoolIds)->get()->each(function ($s) {
            $s->clearMediaCollection('school_certificates');
            $s->delete();
        });

        foreach ($request->schools ?? [] as $index => $schoolData) {
            if (empty($schoolData['school_name'])) continue;

            $skills = $schoolData['training_skills'] ?? null;
            $attrs = [
                'user_driver_detail_id'           => $driver->id,
                'school_name'                     => $schoolData['school_name'],
                'city'                            => $schoolData['city'] ?? null,
                'state'                           => $schoolData['state'] ?? null,
                'graduated'                       => (bool)($schoolData['graduated'] ?? false),
                'date_start'                      => $this->toDbDate($schoolData['date_start'] ?? null),
                'date_end'                        => $this->toDbDate($schoolData['date_end'] ?? null),
                'subject_to_safety_regulations'   => (bool)($schoolData['subject_to_safety_regulations'] ?? false),
                'performed_safety_functions'      => (bool)($schoolData['performed_safety_functions'] ?? false),
                'training_skills'                 => is_array($skills) && count($skills) > 0 ? $skills : null,
            ];

            if (!empty($schoolData['id'])) {
                $school = DriverTrainingSchool::find($schoolData['id']);
                $school?->update($attrs);
            } else {
                $school = DriverTrainingSchool::create($attrs);
            }

            if ($school && $request->hasFile("school_certificates.{$index}")) {
                $file = $request->file("school_certificates.{$index}");
                $ext = $file->getClientOriginalExtension() ?: 'pdf';
                $school->clearMediaCollection('school_certificates');
                $school->addMedia($file)
                    ->usingFileName('certificate.' . $ext)
                    ->toMediaCollection('school_certificates');
            }
        }

        // Courses – upsert by ID to preserve existing files
        $incomingCourseIds = collect($request->courses ?? [])->pluck('id')->filter()->values()->toArray();
        $driver->courses()->whereNotIn('id', $incomingCourseIds)->get()->each(function ($c) {
            $c->clearMediaCollection('course_certificates');
            $c->delete();
        });

        foreach ($request->courses ?? [] as $index => $courseData) {
            if (empty($courseData['organization_name'])) continue;

            $attrs = [
                'user_driver_detail_id' => $driver->id,
                'organization_name'     => $courseData['organization_name'],
                'city'                  => $courseData['city'] ?? null,
                'state'                 => $courseData['state'] ?? null,
                'certification_date'    => $this->toDbDate($courseData['certification_date'] ?? null),
                'expiration_date'       => $this->toDbDate($courseData['expiration_date'] ?? null),
                'experience'            => $courseData['experience'] ?? null,
                'years_experience'      => $courseData['years_experience'] ?? null,
            ];

            if (!empty($courseData['id'])) {
                $course = DriverCourse::find($courseData['id']);
                $course?->update($attrs);
            } else {
                $course = DriverCourse::create($attrs);
            }

            if ($course && $request->hasFile("course_certificates.{$index}")) {
                $file = $request->file("course_certificates.{$index}");
                $ext = $file->getClientOriginalExtension() ?: 'pdf';
                $course->clearMediaCollection('course_certificates');
                $course->addMedia($file)
                    ->usingFileName('certificate.' . $ext)
                    ->toMediaCollection('course_certificates');
            }
        }
    }

    /** Step 7 – Traffic Convictions */
    private function saveStep7(Request $request, UserDriverDetail $driver): void
    {
        $request->validate([
            'no_traffic_convictions'            => 'boolean',
            'convictions'                        => 'nullable|array',
            'convictions.*.conviction_date'      => 'required|date',
            'convictions.*.location'             => 'required|string|max:255',
            'convictions.*.charge'               => 'required|string|max:255',
            'convictions.*.penalty'              => 'nullable|string|max:255',
            'conviction_images'                  => 'nullable|array',
            'conviction_images.*'               => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        if ($request->boolean('no_traffic_convictions')) {
            $driver->trafficConvictions()->get()->each(function ($c) {
                $c->clearMediaCollection('traffic_images');
                $c->delete();
            });
            return;
        }

        // Upsert by ID to preserve existing media
        $incomingIds = collect($request->convictions ?? [])->pluck('id')->filter()->values()->toArray();
        $driver->trafficConvictions()->whereNotIn('id', $incomingIds)->get()->each(function ($c) {
            $c->clearMediaCollection('traffic_images');
            $c->delete();
        });

        foreach ($request->convictions ?? [] as $index => $c) {
            if (empty($c['charge'])) continue;

            $attrs = [
                'user_driver_detail_id' => $driver->id,
                'conviction_date'       => $this->toDbDate($c['conviction_date']),
                'location'              => $c['location'],
                'charge'                => $c['charge'],
                'penalty'               => $c['penalty'] ?? null,
            ];

            if (!empty($c['id'])) {
                $conviction = DriverTrafficConviction::find($c['id']);
                $conviction?->update($attrs);
            } else {
                $conviction = DriverTrafficConviction::create($attrs);
            }

            if ($conviction && $request->hasFile("conviction_images.{$index}")) {
                $file = $request->file("conviction_images.{$index}");
                $ext = $file->getClientOriginalExtension() ?: 'jpg';
                $conviction->clearMediaCollection('traffic_images');
                $conviction->addMedia($file)
                    ->usingFileName('conviction.' . $ext)
                    ->toMediaCollection('traffic_images');
            }
        }
    }

    /** Step 8 – Accidents */
    private function saveStep8(Request $request, UserDriverDetail $driver): void
    {
        $request->validate([
            'no_accidents'                          => 'boolean',
            'accidents'                             => 'nullable|array',
            'accidents.*.accident_date'             => 'required|date',
            'accidents.*.nature_of_accident'        => 'required|string|max:255',
            'accidents.*.number_of_fatalities'      => 'nullable|integer|min:0',
            'accidents.*.number_of_injuries'        => 'nullable|integer|min:0',
            'accidents.*.hazmat_spill'              => 'boolean',
            'accidents.*.comments'                  => 'nullable|string|max:500',
            'accident_images'                       => 'nullable|array',
            'accident_images.*'                     => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        if ($request->boolean('no_accidents')) {
            $driver->accidents()->get()->each(function ($a) {
                $a->clearMediaCollection('accident-images');
                $a->delete();
            });
            return;
        }

        // Upsert by ID to preserve existing media
        $incomingIds = collect($request->accidents ?? [])->pluck('id')->filter()->values()->toArray();
        $driver->accidents()->whereNotIn('id', $incomingIds)->get()->each(function ($a) {
            $a->clearMediaCollection('accident-images');
            $a->delete();
        });

        foreach ($request->accidents ?? [] as $index => $a) {
            if (empty($a['nature_of_accident'])) continue;

            $attrs = [
                'user_driver_detail_id'  => $driver->id,
                'accident_date'          => $this->toDbDate($a['accident_date']),
                'nature_of_accident'     => $a['nature_of_accident'],
                'had_fatalities'         => ($a['number_of_fatalities'] ?? 0) > 0,
                'had_injuries'           => ($a['number_of_injuries'] ?? 0) > 0,
                'number_of_fatalities'   => $a['number_of_fatalities'] ?? 0,
                'number_of_injuries'     => $a['number_of_injuries'] ?? 0,
                'comments'               => $a['comments'] ?? null,
            ];

            if (!empty($a['id'])) {
                $accident = DriverAccident::find($a['id']);
                $accident?->update($attrs);
            } else {
                $accident = DriverAccident::create($attrs);
            }

            if ($accident && $request->hasFile("accident_images.{$index}")) {
                $file = $request->file("accident_images.{$index}");
                $ext = $file->getClientOriginalExtension() ?: 'jpg';
                $accident->clearMediaCollection('accident-images');
                $accident->addMedia($file)
                    ->usingFileName('accident.' . $ext)
                    ->toMediaCollection('accident-images');
            }
        }
    }

    /** Step 9 – FMCSR Data */
    private function saveStep9(Request $request, UserDriverDetail $driver): void
    {
        $request->validate([
            'is_disqualified'             => 'boolean',
            'disqualified_details'        => 'nullable|string|max:1000',
            'is_license_suspended'        => 'boolean',
            'suspension_details'          => 'nullable|string|max:1000',
            'is_license_denied'           => 'boolean',
            'denial_details'              => 'nullable|string|max:1000',
            'has_positive_drug_test'      => 'boolean',
            'substance_abuse_professional'=> 'nullable|string|max:255',
            'sap_phone'                   => 'nullable|string|max:30',
            'return_duty_agency'          => 'nullable|string|max:255',
            'consent_to_release'          => 'boolean',
            'has_duty_offenses'           => 'boolean',
            'recent_conviction_date'      => 'nullable|date',
            'offense_details'             => 'nullable|string|max:1000',
            'consent_driving_record'      => 'boolean',
        ]);

        DriverFmcsrData::updateOrCreate(
            ['user_driver_detail_id' => $driver->id],
            [
                'is_disqualified'             => $request->boolean('is_disqualified'),
                'disqualified_details'        => $request->boolean('is_disqualified') ? $request->input('disqualified_details') : null,
                'is_license_suspended'        => $request->boolean('is_license_suspended'),
                'suspension_details'          => $request->boolean('is_license_suspended') ? $request->input('suspension_details') : null,
                'is_license_denied'           => $request->boolean('is_license_denied'),
                'denial_details'              => $request->boolean('is_license_denied') ? $request->input('denial_details') : null,
                'has_positive_drug_test'      => $request->boolean('has_positive_drug_test'),
                'substance_abuse_professional'=> $request->boolean('has_positive_drug_test') ? $request->input('substance_abuse_professional') : null,
                'sap_phone'                   => $request->boolean('has_positive_drug_test') ? $request->input('sap_phone') : null,
                'return_duty_agency'          => $request->boolean('has_positive_drug_test') ? $request->input('return_duty_agency') : null,
                'consent_to_release'          => $request->boolean('consent_to_release'),
                'has_duty_offenses'           => $request->boolean('has_duty_offenses'),
                'recent_conviction_date'      => $request->boolean('has_duty_offenses') ? $this->toDbDate($request->input('recent_conviction_date')) : null,
                'offense_details'             => $request->boolean('has_duty_offenses') ? $request->input('offense_details') : null,
                'consent_driving_record'      => $request->boolean('consent_driving_record'),
            ]
        );
    }

    /** Step 10 – Employment History */
    private function saveStep10(Request $request, UserDriverDetail $driver): void
    {
        $request->validate([
            'companies'                                => 'nullable|array',
            'companies.*.company_name'                 => 'required|string|max:255',
            'companies.*.address'                      => 'nullable|string|max:255',
            'companies.*.city'                         => 'nullable|string|max:100',
            'companies.*.state'                        => 'nullable|string|max:5',
            'companies.*.zip'                          => 'nullable|string|max:20',
            'companies.*.phone'                        => 'nullable|string|max:20',
            'companies.*.fax'                          => 'nullable|string|max:20',
            'companies.*.contact'                      => 'nullable|string|max:100',
            'companies.*.email'                        => 'nullable|email|max:255',
            'companies.*.employed_from'                => 'nullable|date',
            'companies.*.employed_to'                  => 'nullable|date',
            'companies.*.positions_held'               => 'nullable|string|max:255',
            'companies.*.reason_for_leaving'           => 'nullable|string|max:100',
            'companies.*.other_reason_description'     => 'nullable|string|max:255',
            'companies.*.explanation'                  => 'nullable|string|max:1000',
            'companies.*.subject_to_fmcsr'             => 'boolean',
            'companies.*.safety_sensitive_function'    => 'boolean',
            'unemployment_periods'                     => 'nullable|array',
            'unemployment_periods.*.start_date'        => 'required|date',
            'unemployment_periods.*.end_date'          => 'nullable|date',
            'unemployment_periods.*.comments'          => 'nullable|string|max:255',
            'related_employments'                      => 'nullable|array',
            'related_employments.*.start_date'         => 'required|date',
            'related_employments.*.end_date'           => 'nullable|date',
            'related_employments.*.position'           => 'nullable|string|max:255',
            'related_employments.*.comments'           => 'nullable|string|max:255',
            'has_correct_information'                  => 'boolean',
        ]);

        $driver->employmentCompanies()->delete();
        foreach ($request->companies ?? [] as $c) {
            if (empty($c['company_name'])) continue;

            $masterCompany = \App\Models\Admin\Driver\MasterCompany::firstOrCreate(
                ['company_name' => $c['company_name']],
                [
                    'address' => $c['address'] ?? null,
                    'city'    => $c['city'] ?? null,
                    'state'   => $c['state'] ?? null,
                    'zip'     => $c['zip'] ?? null,
                    'phone'   => $c['phone'] ?? null,
                    'fax'     => $c['fax'] ?? null,
                    'contact' => $c['contact'] ?? null,
                    'email'   => $c['email'] ?? null,
                ]
            );

            DriverEmploymentCompany::create([
                'user_driver_detail_id'    => $driver->id,
                'master_company_id'        => $masterCompany->id,
                'employed_from'            => $this->toDbDate($c['employed_from'] ?? null),
                'employed_to'              => $this->toDbDate($c['employed_to'] ?? null),
                'positions_held'           => $c['positions_held'] ?? null,
                'reason_for_leaving'       => $c['reason_for_leaving'] ?? null,
                'other_reason_description' => $c['other_reason_description'] ?? null,
                'explanation'              => $c['explanation'] ?? null,
                'subject_to_fmcsr'         => (bool)($c['subject_to_fmcsr'] ?? false),
                'safety_sensitive_function'=> (bool)($c['safety_sensitive_function'] ?? false),
                'email'                    => $c['email'] ?? null,
            ]);
        }

        $driver->unemploymentPeriods()->delete();
        foreach ($request->unemployment_periods ?? [] as $u) {
            if (empty($u['start_date'])) continue;
            DriverUnemploymentPeriod::create([
                'user_driver_detail_id' => $driver->id,
                'start_date'            => $this->toDbDate($u['start_date']),
                'end_date'              => $this->toDbDate($u['end_date'] ?? null),
                'comments'              => $u['comments'] ?? null,
            ]);
        }

        // Save confirmation flag on the driver record
        if ($request->has('has_correct_information')) {
            $driver->update(['has_completed_employment_history' => $request->boolean('has_correct_information')]);
        }

        $driver->relatedEmployments()->delete();
        foreach ($request->related_employments ?? [] as $r) {
            if (empty($r['start_date'])) continue;
            \App\Models\Admin\Driver\DriverRelatedEmployment::create([
                'user_driver_detail_id' => $driver->id,
                'start_date'            => $this->toDbDate($r['start_date']),
                'end_date'              => $this->toDbDate($r['end_date'] ?? null),
                'position'              => $r['position'] ?? null,
                'comments'              => $r['comments'] ?? null,
            ]);
        }
    }

    /** Step 11 – Company Policy */
    private function saveStep11(Request $request, UserDriverDetail $driver): void
    {
        $request->validate([
            'consent_all_policies_attached' => 'boolean',
            'substance_testing_consent'     => 'boolean',
            'authorization_consent'         => 'boolean',
            'fmcsa_clearinghouse_consent'   => 'boolean',
            'company_name'                  => 'nullable|string|max:255',
        ]);

        DriverCompanyPolicy::updateOrCreate(
            ['user_driver_detail_id' => $driver->id],
            [
                'consent_all_policies_attached' => $request->boolean('consent_all_policies_attached'),
                'substance_testing_consent'     => $request->boolean('substance_testing_consent'),
                'authorization_consent'         => $request->boolean('authorization_consent'),
                'fmcsa_clearinghouse_consent'   => $request->boolean('fmcsa_clearinghouse_consent'),
                'company_name'                  => $request->input('company_name', ''),
            ]
        );
    }

    /** Step 12 – Criminal History */
    private function saveStep12(Request $request, UserDriverDetail $driver): void
    {
        $request->validate([
            'has_criminal_charges'       => 'boolean',
            'has_felony_conviction'      => 'boolean',
            'has_minister_permit'        => 'boolean',
            'fcra_consent'               => 'boolean',
            'background_info_consent'    => 'boolean',
        ]);

        DriverCriminalHistory::updateOrCreate(
            ['user_driver_detail_id' => $driver->id],
            [
                'has_criminal_charges'    => $request->boolean('has_criminal_charges'),
                'has_felony_conviction'   => $request->boolean('has_felony_conviction'),
                'has_minister_permit'     => $request->boolean('has_minister_permit'),
                'fcra_consent'            => $request->boolean('fcra_consent'),
                'background_info_consent' => $request->boolean('background_info_consent'),
            ]
        );
    }

    /** Step 13 – W-9 */
    private function saveStep13(Request $request, UserDriverDetail $driver): void
    {
        $taxClass = $request->input('tax_classification', '');

        $request->validate([
            'name'                 => 'required|string|max:255',
            'business_name'        => 'nullable|string|max:255',
            'tax_classification'   => 'required|string|in:individual,c_corporation,s_corporation,partnership,trust_estate,llc,other',
            'llc_classification'   => $taxClass === 'llc'   ? 'required|in:C,S,P' : 'nullable|string|max:5',
            'other_classification' => $taxClass === 'other' ? 'required|string|max:255' : 'nullable|string|max:255',
            'has_foreign_partners' => 'boolean',
            'exempt_payee_code'    => 'nullable|string|max:50',
            'fatca_exemption_code' => 'nullable|string|max:50',
            'address'              => 'required|string|max:255',
            'city'                 => 'required|string|max:100',
            'state'                => 'required|string|max:5',
            'zip_code'             => 'required|string|max:20',
            'account_numbers'      => 'nullable|string|max:255',
            'tin_type'             => 'required|string|in:ssn,ein',
            'tin'                  => 'required|string|max:20',
            'signature'            => 'nullable|string',
            'signed_date'          => 'nullable|date',
        ]);

        $w9 = DriverW9Form::updateOrCreate(
            ['user_driver_detail_id' => $driver->id],
            [
                'name'                 => $request->name,
                'business_name'        => $request->business_name,
                'tax_classification'   => $request->tax_classification,
                'llc_classification'   => $taxClass === 'llc'   ? $request->llc_classification   : null,
                'other_classification' => $taxClass === 'other' ? $request->other_classification : null,
                'has_foreign_partners' => $request->boolean('has_foreign_partners'),
                'exempt_payee_code'    => $request->exempt_payee_code,
                'fatca_exemption_code' => $request->fatca_exemption_code,
                'address'              => $request->address,
                'city'                 => $request->city,
                'state'                => strtoupper($request->state ?? ''),
                'zip_code'             => $request->zip_code,
                'account_numbers'      => $request->account_numbers,
                'tin_type'             => $request->tin_type,
                'tin_encrypted'        => preg_replace('/\D/', '', $request->tin),
                'signature'            => $request->signature,
                'signed_date'          => $this->toDbDate($request->signed_date) ?? now()->toDateString(),
            ]
        );

        // Generate W-9 PDF (same logic as Livewire DriverW9Step)
        try {
            $pdfService = app(W9PdfService::class);
            $pdfPath = $pdfService->generate($w9);
            $w9->update(['pdf_path' => $pdfPath]);

            if (file_exists($pdfPath)) {
                $driver->clearMediaCollection('w9_documents');
                $driver->addMedia($pdfPath)
                    ->preservingOriginal()
                    ->usingFileName('W9_' . str_replace(' ', '_', $w9->name) . '_' . now()->format('Y-m-d') . '.pdf')
                    ->toMediaCollection('w9_documents');
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('W9 PDF generation failed in admin wizard, data saved successfully', [
                'error'  => $e->getMessage(),
                'w9_id'  => $w9->id,
                'driver' => $driver->id,
            ]);
        }
    }

    /** Step 14 – Certification */
    private function saveStep14(Request $request, UserDriverDetail $driver): void
    {
        $request->validate([
            'signature'   => 'required|string',
            'is_accepted' => 'boolean',
            'signed_at'   => 'nullable|date',
        ]);

        $cert = DriverCertification::updateOrCreate(
            ['user_driver_detail_id' => $driver->id],
            [
                'signature'   => $request->signature,
                'is_accepted' => $request->boolean('is_accepted'),
                'signed_at'   => now(),
            ]
        );

        // Save signature image to Spatie Media Library
        $signature = $request->signature;
        if (str_starts_with((string) $signature, 'data:image')) {
            $this->saveCertificationSignature($cert, $signature);
        }

        // Mark application as completed
        $driver->update(['application_completed' => true]);

        // Regenerate W-9 PDF with the certification signature
        $this->regenerateW9WithSignature($driver, $signature);

        // Regenerate DOT Policy PDF with the certification signature
        $this->regenerateDotPolicyWithSignature($driver, $signature);

        // Generate all application PDFs (individual steps + combined + criminal history)
        app(\App\Services\ApplicationPdfService::class)->generate($driver, $signature);
    }

    private function regenerateW9WithSignature(UserDriverDetail $driver, string $signature): void
    {
        try {
            $w9 = $driver->w9Form;
            if (!$w9) return;

            $pdfService = app(W9PdfService::class);
            $pdfPath = $pdfService->generate($w9, $signature);
            $w9->update(['pdf_path' => $pdfPath]);

            if (file_exists($pdfPath)) {
                $driver->clearMediaCollection('w9_documents');
                $driver->addMedia($pdfPath)
                    ->preservingOriginal()
                    ->usingFileName('W9_' . str_replace(' ', '_', $w9->name) . '_' . now()->format('Y-m-d') . '.pdf')
                    ->toMediaCollection('w9_documents');
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('W9 PDF re-generation with signature failed', [
                'driver' => $driver->id, 'error' => $e->getMessage(),
            ]);
        }
    }

    private function regenerateDotPolicyWithSignature(UserDriverDetail $driver, string $signature): void
    {
        try {
            $carrier = $driver->carrier ?? Carrier::find($driver->carrier_id);
            if (!$carrier) return;

            $pdfService = app(\App\Services\DotPolicyPdfService::class);
            $pdfPath = $pdfService->generate($carrier, $driver, $signature);

            if (file_exists($pdfPath)) {
                $driver->clearMediaCollection('dot_policy_documents');
                $driver->addMedia($pdfPath)
                    ->preservingOriginal()
                    ->usingFileName('DOT_Policy_' . str_replace(' ', '_', $carrier->name) . '_' . now()->format('Y-m-d') . '.pdf')
                    ->toMediaCollection('dot_policy_documents');
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('DOT Policy PDF re-generation with signature failed', [
                'driver' => $driver->id, 'error' => $e->getMessage(),
            ]);
        }
    }

    /** Step 15 – Clearinghouse / Finalize */
    private function saveStep15(Request $request, UserDriverDetail $driver): void
    {
        $request->validate([
            'clearinghouse_consent'       => 'boolean',
            'clearinghouse_query_date'    => 'nullable|date',
            'clearinghouse_result'        => 'nullable|string|max:50',
        ]);

        $driver->update([
            'application_completed' => true,
            'current_step'          => 15,
        ]);

        if ($driver->application) {
            $driver->application->update([
                'status'       => 'pending',
                'completed_at' => now(),
            ]);
        }
    }

    // =========================================================================
    // PRIVATE – data formatters
    // =========================================================================

    private function formatDriverBase(UserDriverDetail $driver): array
    {
        return [
            'id'                   => $driver->id,
            'user_id'              => $driver->user_id,
            'carrier_id'           => $driver->carrier_id,
            'carrier_name'         => $driver->carrier?->name,
            'name'                 => $driver->user?->name ?? '',
            'middle_name'          => $driver->middle_name,
            'last_name'            => $driver->last_name,
            'email'                => $driver->user?->email ?? '',
            'phone'                => $driver->phone,
            'date_of_birth'        => $driver->date_of_birth?->format('Y-m-d'),
            'status'               => $driver->status,
            'current_step'         => $driver->current_step ?? 1,
            'application_completed'=> $driver->application_completed,
            'hos_cycle_type'       => $driver->hos_cycle_type ?? '70_8',
            'photo_url'            => $driver->getFirstMediaUrl('profile_photo_driver') ?: null,
        ];
    }

    private function buildAllStepData(UserDriverDetail $driver): array
    {
        $app = $driver->application;

        return [
            'step2' => $this->formatAddressData($app),
            'step3' => $this->formatApplicationData($app),
            'step4' => $this->formatLicenseData($driver),
            'step5' => $this->formatMedicalData($driver),
            'step6' => $this->formatTrainingData($driver),
            'step7' => $this->formatTrafficData($driver),
            'step8' => $this->formatAccidentData($driver),
            'step9' => $this->formatFmcsrData($driver),
            'step10'=> $this->formatEmploymentData($driver),
            'step11'=> $this->formatPolicyData($driver),
            'step12'=> $this->formatCriminalData($driver),
            'step13'=> $this->formatW9Data($driver),
            'step14'=> $this->formatCertificationData($driver),
            'step15'=> $this->formatClearinghouseData($driver),
        ];
    }

    private function formatAddressData(?DriverApplication $app): array
    {
        if (!$app) return ['primary' => null, 'previous' => []];

        $primary = $app->addresses()->where('primary', true)->first();
        $previous = $app->addresses()->where('primary', false)->get();

        return [
            'primary'  => $primary ? [
                'address_line1'    => $primary->address_line1,
                'address_line2'    => $primary->address_line2,
                'city'             => $primary->city,
                'state'            => $primary->state,
                'zip_code'         => $primary->zip_code,
                'from_date'        => $primary->from_date?->format('Y-m-d'),
                'to_date'          => $primary->to_date?->format('Y-m-d'),
                'lived_three_years'=> $primary->lived_three_years,
            ] : null,
            'previous' => $previous->map(fn($a) => [
                'id'           => $a->id,
                'address_line1'=> $a->address_line1,
                'address_line2'=> $a->address_line2,
                'city'         => $a->city,
                'state'        => $a->state,
                'zip_code'     => $a->zip_code,
                'from_date'    => $a->from_date?->format('Y-m-d'),
                'to_date'      => $a->to_date?->format('Y-m-d'),
            ])->toArray(),
        ];
    }

    private function formatApplicationData(?DriverApplication $app): array
    {
        $details = $app?->details;
        if (!$details) return [];

        return [
            'applying_position'       => $details->applying_position,
            'applying_position_other' => $details->applying_position_other,
            'applying_location'       => $details->applying_location,
            'eligible_to_work'        => $details->eligible_to_work ?? true,
            'can_speak_english'       => $details->can_speak_english ?? true,
            'has_twic_card'           => $details->has_twic_card ?? false,
            'twic_expiration_date'    => $details->twic_expiration_date?->format('Y-m-d'),
            'expected_pay'            => $details->expected_pay,
            'how_did_hear'            => $details->how_did_hear,
            'how_did_hear_other'      => $details->how_did_hear_other,
            'referral_employee_name'  => $details->referral_employee_name,
            // Vehicle assignment — only set if the driver has an actual assignment saved
            'vehicle_assignment_type' => $details->vehicleDriverAssignment?->driver_type ?? null,
            'vehicle_id'              => $details->vehicle_id,
            'owner_name'              => $details->owner_name,
            'owner_phone'             => $details->owner_phone,
            'owner_email'             => $details->owner_email,
            'third_party_name'        => $details->third_party_name,
            'third_party_phone'       => $details->third_party_phone,
            'third_party_email'       => $details->third_party_email,
            'third_party_dba'         => $details->third_party_dba,
            'third_party_address'     => $details->third_party_address,
            'third_party_contact'     => $details->third_party_contact,
            'third_party_fein'        => $details->third_party_fein,
        ];
    }

    private function deriveAssignmentType(\App\Models\Admin\Driver\DriverApplicationDetail $d): string
    {
        return match ($d->applying_position) {
            'owner_operator'     => 'owner_operator',
            'third_party_driver' => 'third_party',
            default              => 'company',
        };
    }

    /**
     * Load only vehicles registered/assigned to this specific driver.
     * Vehicles are personal (owner_operator / third_party) — not shared across drivers.
     */
    private function loadDriverVehicles(?int $driverDetailId): array
    {
        if (!$driverDetailId) return [];
        return Vehicle::whereHas('driverAssignments', function ($q) use ($driverDetailId) {
                $q->where('user_driver_detail_id', $driverDetailId);
            })
            ->select('id', 'make', 'model', 'year', 'vin', 'type', 'company_unit_number')
            ->orderBy('make')
            ->get()
            ->toArray();
    }

    private function formatLicenseData(UserDriverDetail $driver): array
    {
        $driver->load('experiences');
        return [
            'licenses' => $driver->licenses->map(fn($l) => [
                'id'             => $l->id,
                'license_number' => $l->license_number,
                'state_of_issue' => $l->state_of_issue,
                'license_class'  => $l->license_class,
                'expiration_date'=> $l->expiration_date?->format('Y-m-d'),
                'is_cdl'         => $l->is_cdl,
                'restrictions'   => $l->restrictions,
                'is_primary'     => $l->is_primary,
                'endorsements'   => $l->endorsements->pluck('id')->toArray(),
                'front_url'      => $l->getFirstMediaUrl('license_front') ?: null,
                'back_url'       => $l->getFirstMediaUrl('license_back') ?: null,
            ])->toArray(),
            'experiences' => $driver->experiences->map(fn($e) => [
                'id'              => $e->id,
                'equipment_type'  => $e->equipment_type,
                'years_experience'=> $e->years_experience,
                'miles_driven'    => $e->miles_driven,
                'requires_cdl'    => $e->requires_cdl,
            ])->toArray(),
        ];
    }

    private function formatMedicalData(UserDriverDetail $driver): array
    {
        $m = $driver->medicalQualification;
        if (!$m) return [];

        return [
            'hire_date'                        => $m->hire_date?->format('Y-m-d'),
            'location'                         => $m->location,
            'is_suspended'                     => $m->is_suspended,
            'suspension_date'                  => $m->suspension_date?->format('Y-m-d'),
            'is_terminated'                    => $m->is_terminated,
            'termination_date'                 => $m->termination_date?->format('Y-m-d'),
            'social_security_number'           => $m->social_security_number,
            'ss_card_url'                      => $m->getFirstMediaUrl('social_security_card') ?: null,
            'medical_examiner_name'            => $m->medical_examiner_name,
            'medical_examiner_registry_number' => $m->medical_examiner_registry_number,
            'medical_card_expiration_date'     => $m->medical_card_expiration_date?->format('Y-m-d'),
            'medical_card_url'                 => $m->getFirstMediaUrl('medical_card') ?: null,
        ];
    }

    private function formatTrainingData(UserDriverDetail $driver): array
    {
        return [
            'schools' => $driver->trainingSchools->map(fn($s) => [
                'id'                            => $s->id,
                'school_name'                   => $s->school_name,
                'city'                          => $s->city,
                'state'                         => $s->state,
                'graduated'                     => $s->graduated,
                'date_start'                    => $s->date_start?->format('Y-m-d'),
                'date_end'                      => $s->date_end?->format('Y-m-d'),
                'subject_to_safety_regulations' => $s->subject_to_safety_regulations,
                'performed_safety_functions'    => $s->performed_safety_functions,
                'training_skills'               => $s->training_skills ?? [],
                'certificate_url'               => $s->getFirstMediaUrl('school_certificates') ?: null,
            ])->toArray(),
            'courses' => $driver->courses->map(fn($c) => [
                'id'                 => $c->id,
                'organization_name'  => $c->organization_name,
                'city'               => $c->city,
                'state'              => $c->state,
                'certification_date' => $c->certification_date?->format('Y-m-d'),
                'expiration_date'    => $c->expiration_date?->format('Y-m-d'),
                'experience'         => $c->experience,
                'years_experience'   => $c->years_experience,
                'certificate_url'    => $c->getFirstMediaUrl('course_certificates') ?: null,
            ])->toArray(),
        ];
    }

    private function formatTrafficData(UserDriverDetail $driver): array
    {
        return [
            'no_traffic_convictions' => $driver->trafficConvictions->isEmpty(),
            'convictions' => $driver->trafficConvictions->map(fn($c) => [
                'id'             => $c->id,
                'conviction_date'=> $c->conviction_date?->format('Y-m-d'),
                'location'       => $c->location,
                'charge'         => $c->charge,
                'penalty'        => $c->penalty,
                'image_url'      => $c->getFirstMediaUrl('traffic_images') ?: null,
            ])->toArray(),
        ];
    }

    private function formatAccidentData(UserDriverDetail $driver): array
    {
        return [
            'no_accidents' => $driver->accidents->isEmpty(),
            'accidents' => $driver->accidents->map(fn($a) => [
                'id'                   => $a->id,
                'accident_date'        => $a->accident_date?->format('Y-m-d'),
                'nature_of_accident'   => $a->nature_of_accident,
                'had_fatalities'       => $a->had_fatalities,
                'had_injuries'         => $a->had_injuries,
                'number_of_fatalities' => $a->number_of_fatalities,
                'number_of_injuries'   => $a->number_of_injuries,
                'comments'             => $a->comments,
                'image_url'            => $a->getFirstMediaUrl('accident-images') ?: null,
            ])->toArray(),
        ];
    }

    private function formatFmcsrData(UserDriverDetail $driver): array
    {
        $f = $driver->fmcsrData;
        if (!$f) return [];

        return [
            'is_disqualified'             => $f->is_disqualified,
            'disqualified_details'        => $f->disqualified_details,
            'is_license_suspended'        => $f->is_license_suspended,
            'suspension_details'          => $f->suspension_details,
            'is_license_denied'           => $f->is_license_denied,
            'denial_details'              => $f->denial_details,
            'has_positive_drug_test'      => $f->has_positive_drug_test,
            'substance_abuse_professional'=> $f->substance_abuse_professional,
            'sap_phone'                   => $f->sap_phone,
            'return_duty_agency'          => $f->return_duty_agency,
            'consent_to_release'          => $f->consent_to_release,
            'has_duty_offenses'           => $f->has_duty_offenses,
            'recent_conviction_date'      => $f->recent_conviction_date?->format('Y-m-d'),
            'offense_details'             => $f->offense_details,
            'consent_driving_record'      => $f->consent_driving_record,
        ];
    }

    private function formatEmploymentData(UserDriverDetail $driver): array
    {
        $driver->load('employmentCompanies.masterCompany', 'unemploymentPeriods', 'relatedEmployments');

        return [
            'companies' => $driver->employmentCompanies->map(fn($c) => [
                'id'                        => $c->id,
                'company_name'              => $c->masterCompany?->company_name ?? '',
                'address'                   => $c->masterCompany?->address,
                'city'                      => $c->masterCompany?->city,
                'state'                     => $c->masterCompany?->state,
                'zip'                       => $c->masterCompany?->zip,
                'phone'                     => $c->masterCompany?->phone,
                'fax'                       => $c->masterCompany?->fax,
                'contact'                   => $c->masterCompany?->contact,
                'email'                     => $c->email,
                'employed_from'             => $c->employed_from?->format('Y-m-d'),
                'employed_to'               => $c->employed_to?->format('Y-m-d'),
                'positions_held'            => $c->positions_held,
                'reason_for_leaving'        => $c->reason_for_leaving,
                'other_reason_description'  => $c->other_reason_description,
                'explanation'               => $c->explanation,
                'subject_to_fmcsr'          => $c->subject_to_fmcsr,
                'safety_sensitive_function' => $c->safety_sensitive_function,
                'email_sent'                => $c->email_sent,
                'verification_status'       => $c->verification_status,
            ])->toArray(),
            'unemployment_periods' => $driver->unemploymentPeriods->map(fn($u) => [
                'id'         => $u->id,
                'start_date' => $u->start_date?->format('Y-m-d'),
                'end_date'   => $u->end_date?->format('Y-m-d'),
                'comments'   => $u->comments,
            ])->toArray(),
            'related_employments' => $driver->relatedEmployments->map(fn($r) => [
                'id'         => $r->id,
                'start_date' => $r->start_date?->format('Y-m-d'),
                'end_date'   => $r->end_date?->format('Y-m-d'),
                'position'   => $r->position,
                'comments'   => $r->comments,
            ])->toArray(),
            'has_correct_information' => (bool) $driver->has_completed_employment_history,
        ];
    }

    private function formatPolicyData(UserDriverDetail $driver): array
    {
        $p = $driver->companyPolicy;

        // --- company_name ---
        $companyName = $p?->company_name ?? null;
        if (empty($companyName)) {
            $carrier = $driver->carrier ?? Carrier::find($driver->carrier_id);
            $companyName = $carrier?->name ?? 'EF Services';
        }

        // --- license info ---
        $license = $driver->licenses()->first();
        $licenseNumber = $license?->license_number;
        $licenseState  = $license?->state_of_issue;

        // --- policy document URL ---
        $policyDocumentUrl = $this->resolvePolicyDocumentUrl($driver);

        $base = [
            'company_name'        => $companyName,
            'license_number'      => $licenseNumber,
            'license_state'       => $licenseState,
            'policy_document_url' => $policyDocumentUrl,
        ];

        if (!$p) return $base;

        return array_merge($base, [
            'consent_all_policies_attached' => $p->consent_all_policies_attached,
            'substance_testing_consent'     => $p->substance_testing_consent,
            'authorization_consent'         => $p->authorization_consent,
            'fmcsa_clearinghouse_consent'   => $p->fmcsa_clearinghouse_consent,
        ]);
    }

    private function resolvePolicyDocumentUrl(UserDriverDetail $driver): string
    {
        $carrierId = $driver->carrier_id;

        if ($carrierId) {
            $carrier = $driver->carrier ?? Carrier::find($carrierId);

            // 1. Carrier's generated DOT Policy PDF
            if ($carrier) {
                $dotMedia = $carrier->getFirstMedia('dot_policy_documents');
                if ($dotMedia) {
                    return $dotMedia->getUrl();
                }
            }

            // 2. Carrier's custom 'Politics' document upload
            $policyType = DocumentType::where('name', 'Politics')
                ->orWhere('name', 'Policy Document')
                ->first();

            if ($policyType) {
                $carrierDoc = CarrierDocument::where('carrier_id', $carrierId)
                    ->where('document_type_id', $policyType->id)
                    ->first();

                if ($carrierDoc) {
                    $media = $carrierDoc->getFirstMedia('carrier_documents');
                    if ($media) {
                        return $media->getUrl();
                    }

                    // 3. Carrier approved the default document
                    if ($carrierDoc->status == CarrierDocument::STATUS_APPROVED) {
                        $defaultMedia = $policyType->getFirstMedia('default_documents');
                        if ($defaultMedia) {
                            return $defaultMedia->getUrl();
                        }
                    }
                }

                // 4. DocumentType global default
                $defaultMedia = $policyType->getFirstMedia('default_documents');
                if ($defaultMedia) {
                    return $defaultMedia->getUrl();
                }
            }
        }

        // 5. Generic fallback
        return asset('storage/documents/company_policy.pdf');
    }

    private function formatCriminalData(UserDriverDetail $driver): array
    {
        // Reference display data (read-only)
        $ssn = $driver->medicalQualification?->social_security_number;
        $ssnLastFour = $ssn ? substr(preg_replace('/\D/', '', $ssn), -4) : null;
        $license = $driver->licenses()->first();

        $ref = [
            'full_name'       => $driver->user?->name ?? null,
            'middle_name'     => $driver->middle_name,
            'last_name'       => $driver->last_name,
            'date_of_birth'   => $driver->date_of_birth?->format('Y-m-d'),
            'ssn_last_four'   => $ssnLastFour,
            'license_number'  => $license?->license_number,
            'license_state'   => $license?->state_of_issue,
        ];

        $c = $driver->criminalHistory;
        if (!$c) return $ref;

        return array_merge($ref, [
            'has_criminal_charges'    => $c->has_criminal_charges,
            'has_felony_conviction'   => $c->has_felony_conviction,
            'has_minister_permit'     => $c->has_minister_permit,
            'fcra_consent'            => $c->fcra_consent,
            'background_info_consent' => $c->background_info_consent,
        ]);
    }

    private function formatW9Data(UserDriverDetail $driver): array
    {
        $w = $driver->w9Form;

        // Pre-fill defaults when no W9 yet
        if (!$w) {
            $fullName = trim(($driver->user?->name ?? '') . ' ' . ($driver->last_name ?? ''));
            $addr = $driver->application?->addresses()->orderByDesc('id')->first();
            return [
                'name'                 => $fullName ?: null,
                'business_name'        => null,
                'tax_classification'   => '',
                'llc_classification'   => '',
                'other_classification' => '',
                'has_foreign_partners' => false,
                'exempt_payee_code'    => '',
                'fatca_exemption_code' => '',
                'address'              => $addr?->address_line1,
                'city'                 => $addr?->city,
                'state'                => $addr?->state,
                'zip_code'             => $addr?->zip_code,
                'account_numbers'      => '',
                'tin_type'             => 'ssn',
                'tin'                  => null,
                'signature'            => null,
                'signed_date'          => null,
            ];
        }

        // PDF URL — prefer Spatie media, fall back to pdf_path
        $pdfMedia = $driver->getFirstMedia('w9_documents');
        $pdfUrl   = $pdfMedia?->getUrl() ?? ($w->pdf_path ? asset('storage/' . ltrim($w->pdf_path, 'public/')) : null);

        return [
            'name'                 => $w->name,
            'business_name'        => $w->business_name,
            'tax_classification'   => $w->tax_classification,
            'llc_classification'   => $w->llc_classification ?? '',
            'other_classification' => $w->other_classification ?? '',
            'has_foreign_partners' => $w->has_foreign_partners,
            'exempt_payee_code'    => $w->exempt_payee_code ?? '',
            'fatca_exemption_code' => $w->fatca_exemption_code ?? '',
            'address'              => $w->address,
            'city'                 => $w->city,
            'state'                => $w->state,
            'zip_code'             => $w->zip_code,
            'account_numbers'      => $w->account_numbers ?? '',
            'tin_type'             => $w->tin_type,
            'tin'                  => $w->tin_encrypted,
            'signature'            => $w->signature,
            'signed_date'          => $w->signed_date?->format('Y-m-d'),
            'pdf_url'              => $pdfUrl,
        ];
    }

    private function formatCertificationData(UserDriverDetail $driver): array
    {
        // Employment history for the Safety Performance History table
        $employmentHistory = $driver->employmentCompanies()
            ->with('masterCompany')
            ->orderByDesc('employed_from')
            ->get()
            ->map(function ($c) {
                $mc = $c->masterCompany;
                return [
                    'company_name'  => $c->company_name  ?? $mc?->company_name ?? 'N/A',
                    'address'       => $c->address       ?? $mc?->address      ?? 'N/A',
                    'city'          => $c->city          ?? $mc?->city         ?? 'N/A',
                    'state'         => $c->state         ?? $mc?->state        ?? 'N/A',
                    'zip'           => $c->zip           ?? $mc?->zip          ?? 'N/A',
                    'employed_from' => $c->employed_from?->format('M d, Y') ?? 'N/A',
                    'employed_to'   => $c->employed_to?->format('M d, Y')   ?? 'Present',
                ];
            })->toArray();

        $cert = $driver->certification;

        // Signature URL from Spatie media (preferred) or base64 stored in DB
        $signatureUrl = $cert?->getFirstMediaUrl('signature') ?: null;

        return [
            'signature'          => $cert?->signature,
            'signature_url'      => $signatureUrl,
            'is_accepted'        => $cert?->is_accepted ?? false,
            'signed_at'          => $cert?->signed_at?->format('Y-m-d'),
            'employment_history' => $employmentHistory,
        ];
    }

    private function formatClearinghouseData(UserDriverDetail $driver): array
    {
        $calculator = app(StepCompletionCalculator::class);
        $summary    = $calculator->getCompletionSummary($driver->id);

        $stepNames = [
            1  => 'General Info',
            2  => 'Address',
            3  => 'Application',
            4  => 'License',
            5  => 'Medical',
            6  => 'Training',
            7  => 'Traffic',
            8  => 'Accident',
            9  => 'FMCSR',
            10 => 'Employment',
            11 => 'Policy',
            12 => 'Criminal',
            13 => 'W-9',
            14 => 'Certification',
        ];

        $stepsNeedingAttention = collect($summary['steps_needing_attention'])
            ->map(fn ($s) => [
                'step'       => $s['step'],
                'name'       => $stepNames[$s['step']] ?? 'Step ' . $s['step'],
                'percentage' => $s['percentage'],
            ])->values()->toArray();

        return [
            'application_completed'   => (bool) $driver->application_completed,
            'total_percentage'        => $summary['total_percentage'],
            'steps_needing_attention' => $stepsNeedingAttention,
            'is_complete'             => empty($stepsNeedingAttention),
        ];
    }

    private function saveCertificationSignature(DriverCertification $cert, string $base64): void
    {
        // Decode base64 data URL → PNG file → Spatie Media Library
        if (!str_starts_with($base64, 'data:image')) return;

        $data     = base64_decode(explode(',', $base64)[1]);
        $tmpFile  = tempnam(sys_get_temp_dir(), 'sig_') . '.png';
        file_put_contents($tmpFile, $data);

        try {
            $cert->clearMediaCollection('signature');
            $cert->addMedia($tmpFile)->toMediaCollection('signature');
        } finally {
            @unlink($tmpFile);
        }
    }
}
