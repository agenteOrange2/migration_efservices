<?php

namespace App\Http\Controllers\Admin\Driver;

use App\Helpers\Constants;
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
    // -------------------------------------------------------------------------
    // GET admin/drivers/wizard/create
    // -------------------------------------------------------------------------
    public function create(Request $request): Response
    {
        $carriers = Carrier::select('id', 'name')->orderBy('name')->get();

        $selectedCarrierId = $request->integer('carrier_id') ?: null;

        return Inertia::render('admin/drivers/wizard/Wizard', [
            'driver'             => null,
            'stepData'           => null,
            'carriers'           => $carriers,
            'selectedCarrierId'  => $selectedCarrierId,
            'vehicles'           => [], // No driver yet on create — vehicles load after driver is saved
            'vehicleTypes'       => VehicleType::pluck('name'),
            'usStates'           => Constants::usStates(),
            'driverPositions'    => Constants::driverPositions(),
            'referralSources'    => Constants::referralSources(),
            'endorsements'       => LicenseEndorsement::where('is_active', true)->select('id', 'code', 'name')->orderBy('code')->get()->toArray(),
            'equipmentTypes'     => Constants::equipmentTypes(),
        ]);
    }

    // -------------------------------------------------------------------------
    // POST admin/drivers/wizard  (Step 1 – create user + driver)
    // -------------------------------------------------------------------------
    public function store(Request $request)
    {
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
                $driver->addMedia($request->file('photo'))
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
            ->to(route('admin.drivers.wizard.edit', $driver) . '?step=2')
            ->with('success', 'Driver created. Continue filling in the remaining steps.');
    }

    // -------------------------------------------------------------------------
    // GET admin/drivers/{driver}/wizard
    // -------------------------------------------------------------------------
    public function edit(Request $request, UserDriverDetail $driver): Response
    {
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

        $carriers = Carrier::select('id', 'name')->orderBy('name')->get();

        $initialStep = max(1, min($request->integer('step', $driver->current_step), 15));

        return Inertia::render('admin/drivers/wizard/Wizard', [
            'driver'          => $this->formatDriverBase($driver),
            'stepData'        => $this->buildAllStepData($driver),
            'carriers'        => $carriers,
            'initialStep'     => $initialStep,
            'vehicles'        => $this->loadDriverVehicles($driver->id),
            'vehicleTypes'    => VehicleType::pluck('name'),
            'usStates'        => Constants::usStates(),
            'driverPositions' => Constants::driverPositions(),
            'referralSources' => Constants::referralSources(),
            'endorsements'    => LicenseEndorsement::where('is_active', true)->select('id', 'code', 'name')->orderBy('code')->get()->toArray(),
            'equipmentTypes'  => Constants::equipmentTypes(),
        ]);
    }

    // -------------------------------------------------------------------------
    // PUT admin/drivers/{driver}/wizard/{step}
    // -------------------------------------------------------------------------
    public function updateStep(Request $request, UserDriverDetail $driver, int $step)
    {
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

        $nextStep = min($step + 1, 15);

        return redirect()
            ->to(route('admin.drivers.wizard.edit', $driver) . "?step={$nextStep}")
            ->with('success', "Step {$step} saved successfully.");
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
            $driver->clearMediaCollection('profile_photo_driver');
            $driver->addMedia($request->file('photo'))
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
            'license_front'                => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'license_back'                 => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
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
        }

        // License photos (only for first/primary license)
        $primaryLicense = $driver->licenses()->where('is_primary', true)->first()
            ?? $driver->licenses()->first();

        if ($primaryLicense) {
            if ($request->hasFile('license_front')) {
                $primaryLicense->clearMediaCollection('license_front');
                $primaryLicense->addMedia($request->file('license_front'))
                    ->toMediaCollection('license_front');
            }
            if ($request->hasFile('license_back')) {
                $primaryLicense->clearMediaCollection('license_back');
                $primaryLicense->addMedia($request->file('license_back'))
                    ->toMediaCollection('license_back');
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
            $medical->clearMediaCollection('medical_card');
            $medical->addMedia($request->file('medical_card'))->toMediaCollection('medical_card');
        }

        if ($request->hasFile('social_security_card')) {
            $medical->clearMediaCollection('social_security_card');
            $medical->addMedia($request->file('social_security_card'))
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
            'courses'                                  => 'nullable|array',
            'courses.*.organization_name'              => 'required|string|max:255',
            'courses.*.city'                           => 'nullable|string|max:100',
            'courses.*.state'                          => 'nullable|string|max:5',
            'courses.*.certification_date'             => 'nullable|date',
            'courses.*.expiration_date'                => 'nullable|date',
            'courses.*.experience'                     => 'nullable|string|max:255',
            'courses.*.years_experience'               => 'nullable|numeric',
        ]);

        // Delete and recreate training schools
        $driver->trainingSchools()->delete();
        foreach ($request->schools ?? [] as $schoolData) {
            if (empty($schoolData['school_name'])) continue;
            DriverTrainingSchool::create([
                'user_driver_detail_id'           => $driver->id,
                'school_name'                     => $schoolData['school_name'],
                'city'                            => $schoolData['city'] ?? null,
                'state'                           => $schoolData['state'] ?? null,
                'graduated'                       => (bool)($schoolData['graduated'] ?? false),
                'date_start'                      => $this->toDbDate($schoolData['date_start'] ?? null),
                'date_end'                        => $this->toDbDate($schoolData['date_end'] ?? null),
                'subject_to_safety_regulations'   => (bool)($schoolData['subject_to_safety_regulations'] ?? false),
                'performed_safety_functions'      => (bool)($schoolData['performed_safety_functions'] ?? false),
            ]);
        }

        // Delete and recreate courses
        $driver->courses()->delete();
        foreach ($request->courses ?? [] as $courseData) {
            if (empty($courseData['organization_name'])) continue;
            DriverCourse::create([
                'user_driver_detail_id' => $driver->id,
                'organization_name'     => $courseData['organization_name'],
                'city'                  => $courseData['city'] ?? null,
                'state'                 => $courseData['state'] ?? null,
                'certification_date'    => $this->toDbDate($courseData['certification_date'] ?? null),
                'expiration_date'       => $this->toDbDate($courseData['expiration_date'] ?? null),
                'experience'            => $courseData['experience'] ?? null,
                'years_experience'      => $courseData['years_experience'] ?? null,
            ]);
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
        ]);

        $driver->trafficConvictions()->delete();

        if (!$request->boolean('no_traffic_convictions')) {
            foreach ($request->convictions ?? [] as $c) {
                if (empty($c['charge'])) continue;
                DriverTrafficConviction::create([
                    'user_driver_detail_id' => $driver->id,
                    'conviction_date'       => $this->toDbDate($c['conviction_date']),
                    'location'              => $c['location'],
                    'charge'                => $c['charge'],
                    'penalty'               => $c['penalty'] ?? null,
                ]);
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
            'accidents.*.comments'                  => 'nullable|string|max:500',
        ]);

        $driver->accidents()->delete();

        if (!$request->boolean('no_accidents')) {
            foreach ($request->accidents ?? [] as $a) {
                if (empty($a['nature_of_accident'])) continue;
                DriverAccident::create([
                    'user_driver_detail_id'  => $driver->id,
                    'accident_date'          => $this->toDbDate($a['accident_date']),
                    'nature_of_accident'     => $a['nature_of_accident'],
                    'had_fatalities'         => ($a['number_of_fatalities'] ?? 0) > 0,
                    'had_injuries'           => ($a['number_of_injuries'] ?? 0) > 0,
                    'number_of_fatalities'   => $a['number_of_fatalities'] ?? 0,
                    'number_of_injuries'     => $a['number_of_injuries'] ?? 0,
                    'comments'               => $a['comments'] ?? null,
                ]);
            }
        }
    }

    /** Step 9 – FMCSR Data */
    private function saveStep9(Request $request, UserDriverDetail $driver): void
    {
        $request->validate([
            'is_disqualified'        => 'boolean',
            'is_license_suspended'   => 'boolean',
            'is_license_denied'      => 'boolean',
            'has_positive_drug_test' => 'boolean',
            'consent_to_release'     => 'boolean',
            'has_duty_offenses'      => 'boolean',
            'consent_driving_record' => 'boolean',
        ]);

        DriverFmcsrData::updateOrCreate(
            ['user_driver_detail_id' => $driver->id],
            [
                'is_disqualified'        => $request->boolean('is_disqualified'),
                'is_license_suspended'   => $request->boolean('is_license_suspended'),
                'is_license_denied'      => $request->boolean('is_license_denied'),
                'has_positive_drug_test' => $request->boolean('has_positive_drug_test'),
                'consent_to_release'     => $request->boolean('consent_to_release'),
                'has_duty_offenses'      => $request->boolean('has_duty_offenses'),
                'consent_driving_record' => $request->boolean('consent_driving_record'),
            ]
        );
    }

    /** Step 10 – Employment History */
    private function saveStep10(Request $request, UserDriverDetail $driver): void
    {
        $request->validate([
            'companies'                            => 'nullable|array',
            'companies.*.company_name'             => 'required|string|max:255',
            'companies.*.address'                  => 'nullable|string|max:255',
            'companies.*.city'                     => 'nullable|string|max:100',
            'companies.*.state'                    => 'nullable|string|max:5',
            'companies.*.zip'                      => 'nullable|string|max:20',
            'companies.*.phone'                    => 'nullable|string|max:20',
            'companies.*.email'                    => 'nullable|email|max:255',
            'companies.*.employed_from'            => 'nullable|date',
            'companies.*.employed_to'              => 'nullable|date',
            'companies.*.positions_held'           => 'nullable|string|max:255',
            'companies.*.reason_for_leaving'       => 'nullable|string|max:255',
            'companies.*.subject_to_fmcsr'         => 'boolean',
            'companies.*.safety_sensitive_function'=> 'boolean',
            'unemployment_periods'                 => 'nullable|array',
            'unemployment_periods.*.start_date'    => 'required|date',
            'unemployment_periods.*.end_date'      => 'nullable|date',
            'unemployment_periods.*.comments'      => 'nullable|string|max:255',
        ]);

        $driver->employmentCompanies()->delete();
        foreach ($request->companies ?? [] as $c) {
            if (empty($c['company_name'])) continue;

            // Create or find MasterCompany
            $masterCompany = \App\Models\Admin\Driver\MasterCompany::firstOrCreate(
                ['company_name' => $c['company_name']],
                [
                    'address' => $c['address'] ?? null,
                    'city'    => $c['city'] ?? null,
                    'state'   => $c['state'] ?? null,
                    'zip'     => $c['zip'] ?? null,
                    'phone'   => $c['phone'] ?? null,
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
    }

    /** Step 11 – Company Policy */
    private function saveStep11(Request $request, UserDriverDetail $driver): void
    {
        $request->validate([
            'consent_all_policies_attached' => 'boolean',
            'substance_testing_consent'     => 'boolean',
            'authorization_consent'         => 'boolean',
            'fmcsa_clearinghouse_consent'   => 'boolean',
        ]);

        DriverCompanyPolicy::updateOrCreate(
            ['user_driver_detail_id' => $driver->id],
            [
                'consent_all_policies_attached' => $request->boolean('consent_all_policies_attached'),
                'substance_testing_consent'     => $request->boolean('substance_testing_consent'),
                'authorization_consent'         => $request->boolean('authorization_consent'),
                'fmcsa_clearinghouse_consent'   => $request->boolean('fmcsa_clearinghouse_consent'),
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
        $request->validate([
            'name'               => 'required|string|max:255',
            'business_name'      => 'nullable|string|max:255',
            'tax_classification' => 'required|string|max:50',
            'tin_type'           => 'required|string|in:ssn,ein',
            'tin'                => 'required|string|max:20',
            'signature'          => 'nullable|string',
            'signed_date'        => 'nullable|date',
            'address'            => 'nullable|string|max:255',
            'city'               => 'nullable|string|max:100',
            'state'              => 'nullable|string|max:5',
            'zip_code'           => 'nullable|string|max:20',
        ]);

        DriverW9Form::updateOrCreate(
            ['user_driver_detail_id' => $driver->id],
            [
                'name'               => $request->name,
                'business_name'      => $request->business_name,
                'tax_classification' => $request->tax_classification,
                'tin_type'           => $request->tin_type,
                'tin_encrypted'      => $request->tin,
                'signature'          => $request->signature,
                'signed_date'        => $this->toDbDate($request->signed_date),
                'address'            => $request->address,
                'city'               => $request->city,
                'state'              => $request->state,
                'zip_code'           => $request->zip_code,
            ]
        );
    }

    /** Step 14 – Certification */
    private function saveStep14(Request $request, UserDriverDetail $driver): void
    {
        $request->validate([
            'signature'   => 'nullable|string',
            'is_accepted' => 'boolean',
            'signed_at'   => 'nullable|date',
        ]);

        DriverCertification::updateOrCreate(
            ['user_driver_detail_id' => $driver->id],
            [
                'signature'   => $request->signature,
                'is_accepted' => $request->boolean('is_accepted'),
                'signed_at'   => $this->toDbDate($request->signed_at) ?? now(),
            ]
        );
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
            'step15'=> ['application_completed' => $driver->application_completed],
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
                'number_of_fatalities' => $a->number_of_fatalities,
                'number_of_injuries'   => $a->number_of_injuries,
                'comments'             => $a->comments,
            ])->toArray(),
        ];
    }

    private function formatFmcsrData(UserDriverDetail $driver): array
    {
        $f = $driver->fmcsrData;
        if (!$f) return [];

        return [
            'is_disqualified'        => $f->is_disqualified,
            'is_license_suspended'   => $f->is_license_suspended,
            'is_license_denied'      => $f->is_license_denied,
            'has_positive_drug_test' => $f->has_positive_drug_test,
            'consent_to_release'     => $f->consent_to_release,
            'has_duty_offenses'      => $f->has_duty_offenses,
            'consent_driving_record' => $f->consent_driving_record,
        ];
    }

    private function formatEmploymentData(UserDriverDetail $driver): array
    {
        $driver->load('employmentCompanies.masterCompany');

        return [
            'companies' => $driver->employmentCompanies->map(fn($c) => [
                'id'                     => $c->id,
                'company_name'           => $c->masterCompany?->company_name ?? '',
                'address'                => $c->masterCompany?->address,
                'city'                   => $c->masterCompany?->city,
                'state'                  => $c->masterCompany?->state,
                'zip'                    => $c->masterCompany?->zip,
                'phone'                  => $c->masterCompany?->phone,
                'email'                  => $c->email,
                'employed_from'          => $c->employed_from?->format('Y-m-d'),
                'employed_to'            => $c->employed_to?->format('Y-m-d'),
                'positions_held'         => $c->positions_held,
                'reason_for_leaving'     => $c->reason_for_leaving,
                'subject_to_fmcsr'       => $c->subject_to_fmcsr,
                'safety_sensitive_function' => $c->safety_sensitive_function,
            ])->toArray(),
            'unemployment_periods' => $driver->unemploymentPeriods->map(fn($u) => [
                'id'         => $u->id,
                'start_date' => $u->start_date?->format('Y-m-d'),
                'end_date'   => $u->end_date?->format('Y-m-d'),
                'comments'   => $u->comments,
            ])->toArray(),
        ];
    }

    private function formatPolicyData(UserDriverDetail $driver): array
    {
        $p = $driver->companyPolicy;
        if (!$p) return [];

        return [
            'consent_all_policies_attached' => $p->consent_all_policies_attached,
            'substance_testing_consent'     => $p->substance_testing_consent,
            'authorization_consent'         => $p->authorization_consent,
            'fmcsa_clearinghouse_consent'   => $p->fmcsa_clearinghouse_consent,
        ];
    }

    private function formatCriminalData(UserDriverDetail $driver): array
    {
        $c = $driver->criminalHistory;
        if (!$c) return [];

        return [
            'has_criminal_charges'    => $c->has_criminal_charges,
            'has_felony_conviction'   => $c->has_felony_conviction,
            'has_minister_permit'     => $c->has_minister_permit,
            'fcra_consent'            => $c->fcra_consent,
            'background_info_consent' => $c->background_info_consent,
        ];
    }

    private function formatW9Data(UserDriverDetail $driver): array
    {
        $w = $driver->w9Form;
        if (!$w) return [];

        return [
            'name'               => $w->name,
            'business_name'      => $w->business_name,
            'tax_classification' => $w->tax_classification,
            'tin_type'           => $w->tin_type,
            'tin'                => $w->tin_encrypted,
            'signature'          => $w->signature,
            'signed_date'        => $w->signed_date?->format('Y-m-d'),
            'address'            => $w->address,
            'city'               => $w->city,
            'state'              => $w->state,
            'zip_code'           => $w->zip_code,
        ];
    }

    private function formatCertificationData(UserDriverDetail $driver): array
    {
        $c = $driver->certification;
        if (!$c) return [];

        return [
            'signature'   => $c->signature,
            'is_accepted' => $c->is_accepted,
            'signed_at'   => $c->signed_at?->format('Y-m-d'),
        ];
    }
}
