<?php

namespace App\Http\Controllers\Admin\Driver;

use App\Http\Controllers\Controller;
use App\Models\Admin\Driver\DriverAccident;
use App\Models\Admin\Driver\DriverApplication;
use App\Models\Admin\Driver\DriverCourse;
use App\Models\Admin\Driver\DriverLicense;
use App\Models\Admin\Driver\DriverRecruitmentVerification;
use App\Models\Admin\Driver\DriverRelatedEmployment;
use App\Models\Admin\Driver\DriverTrafficConviction;
use App\Models\Admin\Driver\DriverTrainingSchool;
use App\Models\Admin\Driver\DriverUnemploymentPeriod;
use App\Models\Carrier;
use App\Services\Driver\StepCompletionCalculator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Models\UserDriverDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DriverRecruitmentController extends Controller
{
    // -----------------------------------------------------------------------
    // Checklist items definition (same as efservices Livewire component)
    // -----------------------------------------------------------------------
    private function defaultChecklistItems(): array
    {
        return [
            'general_info'             => ['checked' => false, 'label' => 'Complete and valid general information'],
            'contact_info'             => ['checked' => false, 'label' => 'Verified contact information'],
            'address_info'             => ['checked' => false, 'label' => 'Validated current address and history'],
            'license_info'             => ['checked' => false, 'label' => 'Valid and current drivers license'],
            'license_image'            => ['checked' => false, 'label' => 'Attached, legible license images'],
            'medical_info'             => ['checked' => false, 'label' => 'Complete medical information'],
            'medical_image'            => ['checked' => false, 'label' => 'Medical card attached and current'],
            'experience_info'          => ['checked' => false, 'label' => 'Verified driving experience'],
            'training_verified'        => ['checked' => false, 'label' => 'Training history verified'],
            'traffic_verified'         => ['checked' => false, 'label' => 'Traffic violations reviewed'],
            'accident_verified'        => ['checked' => false, 'label' => 'Accident history reviewed'],
            'driving_record'           => ['checked' => false, 'label' => 'Driving record checked (MVR)'],
            'criminal_record'          => ['checked' => false, 'label' => 'Criminal record reviewed'],
            'clearing_house'           => ['checked' => false, 'label' => 'Clearinghouse query completed'],
            'history_info'             => ['checked' => false, 'label' => 'Employment history verified'],
            'criminal_check'           => ['checked' => false, 'label' => 'Criminal background check completed'],
            'drug_test'                => ['checked' => false, 'label' => 'Drug/alcohol test completed'],
            'mvr_check'                => ['checked' => false, 'label' => 'MVR report obtained'],
            'policy_agreed'            => ['checked' => false, 'label' => 'Company policy acknowledged'],
            'application_certification'=> ['checked' => false, 'label' => 'Application certification signed'],
            'documents_checked'        => ['checked' => false, 'label' => 'All documents reviewed and filed'],
            'vehicle_info'             => ['checked' => false, 'label' => 'Vehicle information verified'],
        ];
    }

    // -----------------------------------------------------------------------
    // GET  admin/driver-recruitment
    // -----------------------------------------------------------------------
    public function index(Request $request): Response
    {
        $search        = $request->input('search', '');
        $statusFilter  = $request->input('status', '');
        $carrierFilter = $request->input('carrier', '');
        $perPage       = (int) $request->input('per_page', 15);

        $query = UserDriverDetail::query()
            ->select('user_driver_details.*')
            ->with([
                'user:id,name,email',
                'carrier:id,name',
                'application',
                'application.verifications' => fn ($q) => $q->latest('verified_at')->limit(1),
            ])
            ->orderBy('user_driver_details.created_at', 'desc');

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', fn ($uq) =>
                    $uq->where('name', 'like', "%{$search}%")
                       ->orWhere('email', 'like', "%{$search}%"))
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (! empty($statusFilter)) {
            $query->whereHas('application', fn ($q) => $q->where('status', $statusFilter));
        }

        if (! empty($carrierFilter)) {
            $query->where('carrier_id', $carrierFilter);
        }

        $drivers = $query->paginate($perPage);

        // Transform each driver for the frontend
        $drivers->getCollection()->transform(fn ($driver) => $this->transformDriverForList($driver));

        $carriers = Carrier::orderBy('name')->get(['id', 'name']);

        // Stats
        $stats = [
            'total'    => UserDriverDetail::count(),
            'pending'  => UserDriverDetail::whereHas('application', fn ($q) => $q->where('status', DriverApplication::STATUS_PENDING))->count(),
            'approved' => UserDriverDetail::whereHas('application', fn ($q) => $q->where('status', DriverApplication::STATUS_APPROVED))->count(),
            'rejected' => UserDriverDetail::whereHas('application', fn ($q) => $q->where('status', DriverApplication::STATUS_REJECTED))->count(),
        ];

        return Inertia::render('admin/drivers/recruitment/Index', [
            'drivers'  => $drivers,
            'carriers' => $carriers,
            'filters'  => [
                'search'   => $search,
                'status'   => $statusFilter,
                'carrier'  => $carrierFilter,
                'per_page' => $perPage,
            ],
            'stats'    => $stats,
            'applicationStatuses' => [
                DriverApplication::STATUS_DRAFT    => 'Draft',
                DriverApplication::STATUS_PENDING  => 'Pending',
                DriverApplication::STATUS_APPROVED => 'Approved',
                DriverApplication::STATUS_REJECTED => 'Rejected',
            ],
        ]);
    }

    // -----------------------------------------------------------------------
    // GET  admin/driver-recruitment/{driver}
    // -----------------------------------------------------------------------
    public function show(int $driverId): Response
    {
        $driver = UserDriverDetail::with([
            'user',
            'carrier',
            'application.addresses',
            'application.details',
            'application.verifications' => fn ($q) => $q->with('verifier:id,name')->latest('verified_at'),
            'licenses.endorsements',
            'medicalQualification',
            'experiences',
            'trainingSchools',
            'courses',
            'trafficConvictions',
            'accidents',
            'testings',
            'fmcsrData',
            'employmentCompanies.company',
            'unemploymentPeriods',
            'relatedEmployments',
            'driverTrainings.training:id,title,content_type',
            'criminalHistory',
            'certification',
            'media',
        ])->findOrFail($driverId);

        $application = $driver->application;

        // Build checklist: start with defaults, then overlay saved values
        $checklistItems = $this->defaultChecklistItems();
        $savedVerification = null;

        if ($application) {
            $savedVerification = $application->verifications()->latest('verified_at')->first();
            if ($savedVerification && ! empty($savedVerification->verification_items)) {
                foreach ($savedVerification->verification_items as $key => $item) {
                    if (isset($checklistItems[$key])) {
                        if (is_array($item) && isset($item['checked'])) {
                            $checklistItems[$key]['checked'] = (bool) $item['checked'];
                        } elseif ($item === true) {
                            $checklistItems[$key]['checked'] = true;
                        }
                    }
                }
            }
        }

        $totalItems   = count($checklistItems);
        $checkedItems = collect($checklistItems)->where('checked', true)->count();
        $checklistPct = $totalItems > 0 ? round(($checkedItems / $totalItems) * 100) : 0;

        // Steps status (same mapping as efservices)
        $stepsStatus = $this->buildStepsStatus($driver);

        return Inertia::render('admin/drivers/recruitment/Show', [
            'driver'              => $this->transformDriverForShow($driver),
            'checklistItems'      => $checklistItems,
            'checklistPct'        => $checklistPct,
            'checkedCount'        => $checkedItems,
            'totalCount'          => $totalItems,
            'savedVerification'   => $savedVerification ? [
                'id'          => $savedVerification->id,
                'verified_at' => $savedVerification->verified_at?->format('m/d/Y H:i'),
                'verifier'    => $savedVerification->verifier?->name,
                'notes'       => $savedVerification->notes,
            ] : null,
            'stepsStatus'         => $stepsStatus,
        ]);
    }

    // -----------------------------------------------------------------------
    // POST admin/driver-recruitment/{driver}/checklist
    // -----------------------------------------------------------------------
    public function updateChecklist(Request $request, int $driverId)
    {
        $driver = UserDriverDetail::findOrFail($driverId);
        $application = $driver->application;

        if (! $application) {
            return response()->json(['error' => 'No application found for this driver.'], 422);
        }

        $items = $request->input('checklist_items', []);
        $notes = $request->input('notes', '');

        // Merge with defaults so all keys are present
        $defaults = $this->defaultChecklistItems();
        $merged   = $defaults;
        foreach ($items as $key => $value) {
            if (isset($merged[$key])) {
                $merged[$key]['checked'] = (bool) ($value['checked'] ?? false);
            }
        }

        DriverRecruitmentVerification::create([
            'driver_application_id' => $application->id,
            'verified_by_user_id'   => Auth::id(),
            'verification_items'    => $merged,
            'notes'                 => $notes,
            'verified_at'           => now(),
        ]);

        return back()->with('success', 'Verification checklist saved successfully.');
    }

    // -----------------------------------------------------------------------
    // POST admin/driver-recruitment/{driver}/approve
    // -----------------------------------------------------------------------
    public function approve(int $driverId)
    {
        $driver = UserDriverDetail::findOrFail($driverId);
        $application = $driver->application;

        if (! $application) {
            return back()->with('error', 'No application found for this driver.');
        }

        DB::transaction(function () use ($application, $driver) {
            $application->update([
                'status'       => DriverApplication::STATUS_APPROVED,
                'completed_at' => now(),
            ]);
            // Activate the driver
            $driver->update(['status' => UserDriverDetail::STATUS_ACTIVE]);
        });

        return back()->with('success', 'Application approved successfully.');
    }

    // -----------------------------------------------------------------------
    // POST admin/driver-recruitment/{driver}/reject
    // -----------------------------------------------------------------------
    public function reject(Request $request, int $driverId)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:2000',
        ]);

        $driver = UserDriverDetail::findOrFail($driverId);
        $application = $driver->application;

        if (! $application) {
            return back()->with('error', 'No application found for this driver.');
        }

        $application->update([
            'status'           => DriverApplication::STATUS_REJECTED,
            'rejection_reason' => $request->input('rejection_reason'),
            'completed_at'     => now(),
        ]);

        return back()->with('success', 'Application rejected.');
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------
    private function transformDriverForList(UserDriverDetail $driver): array
    {
        $application = $driver->application;
        $pct = $this->calculateChecklistPct($driver);

        return [
            'id'              => $driver->id,
            'name'            => $driver->user?->name ?? '',
            'last_name'       => $driver->last_name ?? '',
            'middle_name'     => $driver->middle_name ?? '',
            'email'           => $driver->user?->email ?? '',
            'phone'           => $driver->phone ?? '',
            'carrier_name'    => $driver->carrier?->name ?? 'N/A',
            'profile_photo'   => $driver->getFirstMediaUrl('profile_photo_driver') ?: null,
            'application_date'=> $driver->created_at?->format('m/d/Y') ?? '',
            'status'          => $application?->status ?? 'draft',
            'checklist_pct'   => $pct,
        ];
    }

    private function transformDriverForShow(UserDriverDetail $driver): array
    {
        $application = $driver->application;

        return [
            'id'                => $driver->id,
            'name'              => $driver->user?->name ?? '',
            'last_name'         => $driver->last_name ?? '',
            'middle_name'       => $driver->middle_name ?? '',
            'email'             => $driver->user?->email ?? '',
            'phone'             => $driver->phone ?? '',
            'date_of_birth'     => $driver->date_of_birth?->format('m/d/Y') ?? '',
            'carrier_name'      => $driver->carrier?->name ?? 'N/A',
            'profile_photo'     => $driver->getFirstMediaUrl('profile_photo_driver') ?: null,
            'application_date'  => $driver->created_at?->format('m/d/Y') ?? '',
            'status'            => $application?->status ?? 'draft',
            'completion_pct'    => (int) round(app(StepCompletionCalculator::class)->calculateTotalCompletion($driver->id)),
            'application'       => $application ? [
                'id'               => $application->id,
                'status'           => $application->status,
                'rejection_reason' => $application->rejection_reason,
                'completed_at'     => $this->safeDate($application->completed_at),
                'pdf_url'          => $application->getFirstMediaUrl('application_pdf') ?: null,
                'details'          => $application->details ? [
                    'applying_position'       => $application->details->applying_position,
                    'applying_position_other' => $application->details->applying_position_other,
                    'applying_location'       => $application->details->applying_location,
                    'eligible_to_work'        => $application->details->eligible_to_work,
                    'can_speak_english'       => $application->details->can_speak_english,
                    'has_twic_card'           => $application->details->has_twic_card,
                    'twic_expiration_date'    => $this->safeDate($application->details->twic_expiration_date),
                    'how_did_hear'            => $application->details->how_did_hear,
                    'how_did_hear_other'      => $application->details->how_did_hear_other,
                    'referral_employee_name'  => $application->details->referral_employee_name,
                ] : null,
                'addresses' => $application->addresses->map(fn ($a) => [
                    'id'             => $a->id,
                    'address_line1'  => $a->address_line1,
                    'address_line2'  => $a->address_line2,
                    'city'           => $a->city,
                    'state'          => $a->state,
                    'zip_code'       => $a->zip_code,
                    'from_date'      => $this->safeDate($a->from_date),
                    'to_date'        => $this->safeDate($a->to_date),
                    'primary'        => $a->primary ?? false,
                    'lived_three_years' => $a->lived_three_years ?? false,
                ])->values()->all(),
            ] : null,
            'licenses' => $driver->licenses->map(fn ($l) => [
                'id'              => $l->id,
                'license_number'  => $l->license_number,
                'state_of_issue'  => $l->state_of_issue,
                'license_class'   => $l->license_class,
                'expiration_date' => $this->safeDate($l->expiration_date),
                'is_cdl'          => $l->is_cdl,
                'status'          => $l->status,
                'is_expired'      => $l->expiration_date ? Carbon::parse($l->expiration_date)->isPast() : false,
                'front_image'     => $l->getFirstMediaUrl('license_front') ?: null,
                'back_image'      => $l->getFirstMediaUrl('license_back') ?: null,
                'endorsements'    => $l->endorsements->map(fn ($e) => [
                    'code' => $e->code,
                    'name' => $e->name,
                ])->all(),
            ])->values()->all(),
            'medical' => $driver->medicalQualification ? [
                'id'                      => $driver->medicalQualification->id,
                'medical_examiner_name'   => $driver->medicalQualification->medical_examiner_name ?? null,
                'medical_examiner_registry_number' => $driver->medicalQualification->medical_examiner_registry_number ?? null,
                'medical_card_expiration_date' => $this->safeDate($driver->medicalQualification->medical_card_expiration_date),
                'ssn_last4'               => $driver->medicalQualification->social_security_number
                    ? substr($driver->medicalQualification->social_security_number, -4)
                    : null,
                'medical_card_url'        => $driver->medicalQualification->getFirstMediaUrl('medical_card') ?: null,
                'is_expired'              => $driver->medicalQualification->medical_card_expiration_date
                    ? Carbon::parse($driver->medicalQualification->medical_card_expiration_date)->isPast()
                    : false,
            ] : null,
            'experiences' => $driver->experiences->map(fn ($e) => [
                'equipment_type'   => $e->equipment_type,
                'years_experience' => $e->years_experience,
                'miles_driven'     => $e->miles_driven,
                'requires_cdl'     => $e->requires_cdl,
            ])->values()->all(),
            'training_schools' => $driver->trainingSchools->map(fn ($t) => [
                'id'                            => $t->id,
                'school_name'                   => $t->school_name,
                'city'                          => $t->city,
                'state'                         => $t->state,
                'date_start'                    => $t->date_start?->format('m/d/Y'),
                'date_end'                      => $t->date_end?->format('m/d/Y'),
                'graduated'                     => $t->graduated,
                'subject_to_safety_regulations' => $t->subject_to_safety_regulations,
                'performed_safety_functions'    => $t->performed_safety_functions,
                'training_skills'               => $t->training_skills ?? [],
                'certificates'                  => $t->getMedia('school_certificates')->map(fn ($m) => [
                    'id'       => $m->id,
                    'url'      => $m->getUrl(),
                    'name'     => $m->file_name,
                    'is_image' => str_starts_with($m->mime_type, 'image/'),
                ])->all(),
            ])->values()->all(),
            'employment_companies' => $driver->employmentCompanies->map(fn ($c) => [
                'company_name'       => $c->company?->company_name ?? 'N/A',
                'city'               => $c->company?->city ?? '',
                'state'              => $c->company?->state ?? '',
                'phone'              => $c->company?->phone ?? '',
                'position_held'      => $c->positions_held ?? '',
                'from_date'          => $this->safeDate($c->employed_from),
                'to_date'            => $this->safeDate($c->employed_to),
                'from_date_raw'      => $c->employed_from ? Carbon::parse($c->employed_from)->toDateString() : null,
                'to_date_raw'        => $c->employed_to   ? Carbon::parse($c->employed_to)->toDateString()   : null,
                'reason_for_leaving' => $c->reason_for_leaving,
                'subject_to_fmcsr'   => $c->subject_to_fmcsr,
                'safety_sensitive'   => $c->safety_sensitive_function,
                'verification_status'=> $c->verification_status,
            ])->values()->all(),
            'unemployment_periods' => $driver->unemploymentPeriods->map(fn ($u) => [
                'id'           => $u->id,
                'start_date'   => $this->safeDate($u->start_date),
                'end_date'     => $this->safeDate($u->end_date),
                'start_date_raw' => $u->start_date?->toDateString(),
                'end_date_raw'   => $u->end_date?->toDateString(),
                'comments'     => $u->comments,
            ])->values()->all(),
            'related_employments' => $driver->relatedEmployments->map(fn ($r) => [
                'id'           => $r->id,
                'start_date'   => $this->safeDate($r->start_date),
                'end_date'     => $this->safeDate($r->end_date),
                'start_date_raw' => $r->start_date?->toDateString(),
                'end_date_raw'   => $r->end_date?->toDateString(),
                'position'     => $r->position,
                'comments'     => $r->comments,
            ])->values()->all(),
            'driving_records' => $driver->getMedia('driving_records')->map(fn ($m) => [
                'id'       => $m->id,
                'name'     => $m->file_name,
                'url'      => $m->getUrl(),
                'is_image' => str_starts_with($m->mime_type, 'image/'),
                'size'     => $m->human_readable_size,
            ])->values()->all(),
            'criminal_records' => $driver->getMedia('criminal_records')->map(fn ($m) => [
                'id'       => $m->id,
                'name'     => $m->file_name,
                'url'      => $m->getUrl(),
                'is_image' => str_starts_with($m->mime_type, 'image/'),
                'size'     => $m->human_readable_size,
            ])->values()->all(),
            'medical_records' => $driver->getMedia('medical_records')->map(fn ($m) => [
                'id'       => $m->id,
                'name'     => $m->file_name,
                'url'      => $m->getUrl(),
                'is_image' => str_starts_with($m->mime_type, 'image/'),
                'size'     => $m->human_readable_size,
            ])->values()->all(),
            'clearing_house' => $driver->getMedia('clearing_house')->map(fn ($m) => [
                'id'       => $m->id,
                'name'     => $m->file_name,
                'url'      => $m->getUrl(),
                'is_image' => str_starts_with($m->mime_type, 'image/'),
                'size'     => $m->human_readable_size,
            ])->values()->all(),
            'driver_trainings' => $driver->driverTrainings->map(fn ($dt) => [
                'id'            => $dt->id,
                'training_id'   => $dt->training_id,
                'title'         => $dt->training?->title ?? 'N/A',
                'content_type'  => $dt->training?->content_type ?? '',
                'assigned_date' => $this->safeDate($dt->assigned_date),
                'due_date'      => $this->safeDate($dt->due_date),
                'completed_date'=> $this->safeDate($dt->completed_date),
                'status'        => $dt->status ?? 'assigned',
                'is_overdue'    => $dt->due_date && $dt->status !== 'completed'
                    ? Carbon::parse($dt->due_date)->isPast()
                    : false,
            ])->values()->all(),
            'criminal_history' => $driver->criminalHistory ? [
                'has_criminal_charges'   => $driver->criminalHistory->has_criminal_charges,
                'has_felony_conviction'  => $driver->criminalHistory->has_felony_conviction,
                'has_minister_permit'    => $driver->criminalHistory->has_minister_permit,
                'fcra_consent'           => $driver->criminalHistory->fcra_consent,
            ] : null,
            'courses' => $driver->courses->map(fn ($c) => [
                'id'                => $c->id,
                'organization_name' => $c->organization_name,
                'city'              => $c->city,
                'state'             => $c->state,
                'certification_date'=> $this->safeDate($c->certification_date),
                'expiration_date'   => $this->safeDate($c->expiration_date),
                'experience'        => $c->experience,
                'years_experience'  => $c->years_experience,
                'status'            => $c->status ?? 'Active',
                'certificates'      => $c->getMedia('course_certificates')->map(fn ($m) => [
                    'id'       => $m->id,
                    'url'      => $m->getUrl(),
                    'name'     => $m->file_name,
                    'is_image' => str_starts_with($m->mime_type, 'image/'),
                ])->all(),
            ])->values()->all(),
            'traffic_convictions' => $driver->trafficConvictions->map(fn ($t) => [
                'id'              => $t->id,
                'conviction_date' => $this->safeDate($t->conviction_date),
                'location'        => $t->location,
                'charge'          => $t->charge,
                'penalty'         => $t->penalty,
                'conviction_type' => $t->conviction_type,
                'description'     => $t->description,
            ])->values()->all(),
            'accidents' => $driver->accidents->map(fn ($a) => [
                'id'                   => $a->id,
                'accident_date'        => $this->safeDate($a->accident_date),
                'nature_of_accident'   => $a->nature_of_accident,
                'had_fatalities'       => $a->had_fatalities,
                'had_injuries'         => $a->had_injuries,
                'number_of_fatalities' => $a->number_of_fatalities,
                'number_of_injuries'   => $a->number_of_injuries,
                'comments'             => $a->comments,
            ])->values()->all(),
            'testings' => $driver->testings->map(fn ($t) => [
                'id'          => $t->id,
                'test_date'   => $this->safeDate($t->test_date),
                'test_type'   => $t->test_type,
                'test_result' => $t->test_result,
                'status'      => $t->status,
                'administered_by' => $t->administered_by,
                'is_pre_employment_test' => $t->is_pre_employment_test,
                'is_random_test'         => $t->is_random_test,
                'is_post_accident_test'  => $t->is_post_accident_test,
            ])->values()->all(),
            'fmcsr_data' => $driver->fmcsrData ? [
                'is_disqualified'        => $driver->fmcsrData->is_disqualified,
                'disqualified_details'   => $driver->fmcsrData->disqualified_details,
                'is_license_suspended'   => $driver->fmcsrData->is_license_suspended,
                'suspension_details'     => $driver->fmcsrData->suspension_details,
                'is_license_denied'      => $driver->fmcsrData->is_license_denied,
                'denial_details'         => $driver->fmcsrData->denial_details,
                'has_positive_drug_test' => $driver->fmcsrData->has_positive_drug_test,
                'has_duty_offenses'      => $driver->fmcsrData->has_duty_offenses,
                'offense_details'        => $driver->fmcsrData->offense_details,
                'consent_driving_record' => $driver->fmcsrData->consent_driving_record,
            ] : null,
        ];
    }

    private function calculateChecklistPct(UserDriverDetail $driver): int
    {
        $application = $driver->application;
        if (! $application) {
            return 0;
        }
        if ($application->status === DriverApplication::STATUS_APPROVED) {
            return 100;
        }

        $verification = $application->verifications()->latest('verified_at')->first();
        if (! $verification || empty($verification->verification_items)) {
            return 0;
        }

        $items   = $verification->verification_items;
        $total   = count($items);
        $checked = 0;
        foreach ($items as $item) {
            if (is_array($item) && ($item['checked'] ?? false) === true) {
                $checked++;
            } elseif ($item === true) {
                $checked++;
            }
        }

        return $total > 0 ? (int) round(($checked / $total) * 100) : 0;
    }

    // -----------------------------------------------------------------------
    // POST admin/driver-recruitment/{driver}/licenses/{license}/upload-image
    // -----------------------------------------------------------------------
    public function uploadLicenseImage(Request $request, int $driverId, int $licenseId)
    {
        $request->validate([
            'image' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'side'  => 'required|in:front,back',
        ]);

        $driver  = UserDriverDetail::findOrFail($driverId);
        $license = DriverLicense::where('user_driver_detail_id', $driver->id)->findOrFail($licenseId);

        $collection = $request->input('side') === 'front' ? 'license_front' : 'license_back';
        $license->clearMediaCollection($collection);
        $license->addMediaFromRequest('image')->toMediaCollection($collection);

        return back()->with('success', 'License image updated.');
    }

    // -----------------------------------------------------------------------
    // POST admin/driver-recruitment/{driver}/upload-medical-image
    // -----------------------------------------------------------------------
    public function uploadMedicalImage(Request $request, int $driverId)
    {
        $request->validate([
            'image' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        $driver = UserDriverDetail::findOrFail($driverId);
        if (! $driver->medicalQualification) {
            return back()->with('error', 'No medical qualification record found.');
        }

        $driver->medicalQualification->clearMediaCollection('medical_card');
        $driver->medicalQualification->addMediaFromRequest('image')->toMediaCollection('medical_card');

        return back()->with('success', 'Medical card image updated.');
    }

    // -----------------------------------------------------------------------
    // POST admin/driver-recruitment/{driver}/training-schools
    // -----------------------------------------------------------------------
    public function storeTrainingSchool(Request $request, int $driverId)
    {
        $request->validate([
            'school_name'                   => 'required|string|max:255',
            'city'                          => 'nullable|string|max:100',
            'state'                         => 'nullable|string|max:5',
            'date_start'                    => 'nullable|string',
            'date_end'                      => 'nullable|string',
            'graduated'                     => 'boolean',
            'subject_to_safety_regulations' => 'boolean',
            'performed_safety_functions'    => 'boolean',
            'training_skills'               => 'nullable|array',
            'training_skills.*'             => 'string',
            'certificates'                  => 'nullable|array',
            'certificates.*'                => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240',
        ]);

        $driver = UserDriverDetail::findOrFail($driverId);

        $school = DriverTrainingSchool::create([
            'user_driver_detail_id'         => $driver->id,
            'school_name'                   => $request->school_name,
            'city'                          => $request->city,
            'state'                         => $request->state,
            'date_start'                    => $this->parseDateInput($request->date_start),
            'date_end'                      => $this->parseDateInput($request->date_end),
            'graduated'                     => $request->boolean('graduated'),
            'subject_to_safety_regulations' => $request->boolean('subject_to_safety_regulations'),
            'performed_safety_functions'    => $request->boolean('performed_safety_functions'),
            'training_skills'               => $request->input('training_skills', []),
        ]);

        foreach ($request->file('certificates', []) as $file) {
            $school->addMedia($file)->toMediaCollection('school_certificates');
        }

        return back()->with('success', 'Training school added.');
    }

    // -----------------------------------------------------------------------
    // POST admin/driver-recruitment/{driver}/training-schools/{school}
    // -----------------------------------------------------------------------
    public function updateTrainingSchool(Request $request, int $driverId, int $schoolId)
    {
        $request->validate([
            'school_name'                   => 'required|string|max:255',
            'city'                          => 'nullable|string|max:100',
            'state'                         => 'nullable|string|max:5',
            'date_start'                    => 'nullable|string',
            'date_end'                      => 'nullable|string',
            'graduated'                     => 'boolean',
            'subject_to_safety_regulations' => 'boolean',
            'performed_safety_functions'    => 'boolean',
            'training_skills'               => 'nullable|array',
            'training_skills.*'             => 'string',
            'certificates'                  => 'nullable|array',
            'certificates.*'                => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240',
        ]);

        $driver = UserDriverDetail::findOrFail($driverId);
        $school = DriverTrainingSchool::where('user_driver_detail_id', $driver->id)->findOrFail($schoolId);

        $school->update([
            'school_name'                   => $request->school_name,
            'city'                          => $request->city,
            'state'                         => $request->state,
            'date_start'                    => $this->parseDateInput($request->date_start),
            'date_end'                      => $this->parseDateInput($request->date_end),
            'graduated'                     => $request->boolean('graduated'),
            'subject_to_safety_regulations' => $request->boolean('subject_to_safety_regulations'),
            'performed_safety_functions'    => $request->boolean('performed_safety_functions'),
            'training_skills'               => $request->input('training_skills', []),
        ]);

        foreach ($request->file('certificates', []) as $file) {
            $school->addMedia($file)->toMediaCollection('school_certificates');
        }

        return back()->with('success', 'Training school updated.');
    }

    // -----------------------------------------------------------------------
    // POST admin/driver-recruitment/{driver}/courses
    // -----------------------------------------------------------------------
    public function storeCourse(Request $request, int $driverId)
    {
        $request->validate([
            'organization_name'  => 'required|string|max:255',
            'city'               => 'nullable|string|max:100',
            'state'              => 'nullable|string|max:5',
            'certification_date' => 'nullable|string',
            'expiration_date'    => 'nullable|string',
            'experience'         => 'nullable|string',
            'years_experience'   => 'nullable|numeric|min:0',
            'status'             => 'nullable|string|in:Active,Expired,Pending',
            'certificates'       => 'nullable|array',
            'certificates.*'     => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240',
        ]);

        $driver = UserDriverDetail::findOrFail($driverId);

        $course = DriverCourse::create([
            'user_driver_detail_id' => $driver->id,
            'organization_name'     => $request->organization_name,
            'city'                  => $request->city,
            'state'                 => $request->state,
            'certification_date'    => $this->parseDateInput($request->certification_date),
            'expiration_date'       => $this->parseDateInput($request->expiration_date),
            'experience'            => $request->experience,
            'years_experience'      => $request->years_experience ?? 0,
            'status'                => $request->input('status', 'Active'),
        ]);

        foreach ($request->file('certificates', []) as $file) {
            $course->addMedia($file)->toMediaCollection('course_certificates');
        }

        return back()->with('success', 'Course added.');
    }

    // -----------------------------------------------------------------------
    // POST admin/driver-recruitment/{driver}/courses/{course}
    // -----------------------------------------------------------------------
    public function updateCourse(Request $request, int $driverId, int $courseId)
    {
        $request->validate([
            'organization_name'  => 'required|string|max:255',
            'city'               => 'nullable|string|max:100',
            'state'              => 'nullable|string|max:5',
            'certification_date' => 'nullable|string',
            'expiration_date'    => 'nullable|string',
            'experience'         => 'nullable|string',
            'years_experience'   => 'nullable|numeric|min:0',
            'status'             => 'nullable|string|in:Active,Expired,Pending',
            'certificates'       => 'nullable|array',
            'certificates.*'     => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240',
        ]);

        $driver = UserDriverDetail::findOrFail($driverId);
        $course = DriverCourse::where('user_driver_detail_id', $driver->id)->findOrFail($courseId);

        $course->update([
            'organization_name'  => $request->organization_name,
            'city'               => $request->city,
            'state'              => $request->state,
            'certification_date' => $this->parseDateInput($request->certification_date),
            'expiration_date'    => $this->parseDateInput($request->expiration_date),
            'experience'         => $request->experience,
            'years_experience'   => $request->years_experience ?? 0,
            'status'             => $request->input('status', 'Active'),
        ]);

        foreach ($request->file('certificates', []) as $file) {
            $course->addMedia($file)->toMediaCollection('course_certificates');
        }

        return back()->with('success', 'Course updated.');
    }

    // -----------------------------------------------------------------------
    // POST admin/driver-recruitment/{driver}/traffic-convictions
    // -----------------------------------------------------------------------
    public function storeTrafficConviction(Request $request, int $driverId)
    {
        $request->validate([
            'conviction_date' => 'nullable|string',
            'location'        => 'nullable|string|max:255',
            'charge'          => 'required|string|max:255',
            'penalty'         => 'nullable|string|max:255',
            'conviction_type' => 'nullable|string|max:100',
            'description'     => 'nullable|string',
        ]);

        $driver = UserDriverDetail::findOrFail($driverId);

        DriverTrafficConviction::create([
            'user_driver_detail_id' => $driver->id,
            'conviction_date'       => $this->parseDateInput($request->conviction_date),
            'location'              => $request->location,
            'charge'                => $request->charge,
            'penalty'               => $request->penalty,
            'conviction_type'       => $request->conviction_type,
            'description'           => $request->description,
        ]);

        return back()->with('success', 'Traffic conviction added.');
    }

    // -----------------------------------------------------------------------
    // POST admin/driver-recruitment/{driver}/traffic-convictions/{conviction}
    // -----------------------------------------------------------------------
    public function updateTrafficConviction(Request $request, int $driverId, int $convictionId)
    {
        $request->validate([
            'conviction_date' => 'nullable|string',
            'location'        => 'nullable|string|max:255',
            'charge'          => 'required|string|max:255',
            'penalty'         => 'nullable|string|max:255',
            'conviction_type' => 'nullable|string|max:100',
            'description'     => 'nullable|string',
        ]);

        $driver     = UserDriverDetail::findOrFail($driverId);
        $conviction = DriverTrafficConviction::where('user_driver_detail_id', $driver->id)->findOrFail($convictionId);

        $conviction->update([
            'conviction_date' => $this->parseDateInput($request->conviction_date),
            'location'        => $request->location,
            'charge'          => $request->charge,
            'penalty'         => $request->penalty,
            'conviction_type' => $request->conviction_type,
            'description'     => $request->description,
        ]);

        return back()->with('success', 'Traffic conviction updated.');
    }

    // -----------------------------------------------------------------------
    // DELETE admin/driver-recruitment/{driver}/traffic-convictions/{conviction}
    // -----------------------------------------------------------------------
    public function destroyTrafficConviction(int $driverId, int $convictionId)
    {
        $driver = UserDriverDetail::findOrFail($driverId);
        DriverTrafficConviction::where('user_driver_detail_id', $driver->id)->findOrFail($convictionId)->delete();
        return back()->with('success', 'Traffic conviction deleted.');
    }

    // -----------------------------------------------------------------------
    // DELETE admin/driver-recruitment/{driver}/training-schools/{school}
    // -----------------------------------------------------------------------
    public function destroyTrainingSchool(int $driverId, int $schoolId)
    {
        $driver = UserDriverDetail::findOrFail($driverId);
        DriverTrainingSchool::where('user_driver_detail_id', $driver->id)->findOrFail($schoolId)->delete();
        return back()->with('success', 'Training school deleted.');
    }

    // -----------------------------------------------------------------------
    // DELETE admin/driver-recruitment/{driver}/courses/{course}
    // -----------------------------------------------------------------------
    public function destroyCourse(int $driverId, int $courseId)
    {
        $driver = UserDriverDetail::findOrFail($driverId);
        DriverCourse::where('user_driver_detail_id', $driver->id)->findOrFail($courseId)->delete();
        return back()->with('success', 'Course deleted.');
    }

    // -----------------------------------------------------------------------
    // POST admin/driver-recruitment/{driver}/accidents
    // -----------------------------------------------------------------------
    public function storeAccident(Request $request, int $driverId)
    {
        $request->validate([
            'accident_date'        => 'nullable|string',
            'nature_of_accident'   => 'required|string|max:500',
            'had_fatalities'       => 'boolean',
            'had_injuries'         => 'boolean',
            'number_of_fatalities' => 'nullable|integer|min:0',
            'number_of_injuries'   => 'nullable|integer|min:0',
            'comments'             => 'nullable|string',
        ]);

        $driver = UserDriverDetail::findOrFail($driverId);

        DriverAccident::create([
            'user_driver_detail_id' => $driver->id,
            'accident_date'         => $this->parseDateInput($request->accident_date),
            'nature_of_accident'    => $request->nature_of_accident,
            'had_fatalities'        => $request->boolean('had_fatalities'),
            'had_injuries'          => $request->boolean('had_injuries'),
            'number_of_fatalities'  => $request->number_of_fatalities ?? 0,
            'number_of_injuries'    => $request->number_of_injuries ?? 0,
            'comments'              => $request->comments,
        ]);

        return back()->with('success', 'Accident record added.');
    }

    // -----------------------------------------------------------------------
    // POST admin/driver-recruitment/{driver}/accidents/{accident}
    // -----------------------------------------------------------------------
    public function updateAccident(Request $request, int $driverId, int $accidentId)
    {
        $request->validate([
            'accident_date'        => 'nullable|string',
            'nature_of_accident'   => 'required|string|max:500',
            'had_fatalities'       => 'boolean',
            'had_injuries'         => 'boolean',
            'number_of_fatalities' => 'nullable|integer|min:0',
            'number_of_injuries'   => 'nullable|integer|min:0',
            'comments'             => 'nullable|string',
        ]);

        $driver   = UserDriverDetail::findOrFail($driverId);
        $accident = DriverAccident::where('user_driver_detail_id', $driver->id)->findOrFail($accidentId);

        $accident->update([
            'accident_date'         => $this->parseDateInput($request->accident_date),
            'nature_of_accident'    => $request->nature_of_accident,
            'had_fatalities'        => $request->boolean('had_fatalities'),
            'had_injuries'          => $request->boolean('had_injuries'),
            'number_of_fatalities'  => $request->number_of_fatalities ?? 0,
            'number_of_injuries'    => $request->number_of_injuries ?? 0,
            'comments'              => $request->comments,
        ]);

        return back()->with('success', 'Accident record updated.');
    }

    // -----------------------------------------------------------------------
    // DELETE admin/driver-recruitment/{driver}/accidents/{accident}
    // -----------------------------------------------------------------------
    public function destroyAccident(int $driverId, int $accidentId)
    {
        $driver = UserDriverDetail::findOrFail($driverId);
        DriverAccident::where('user_driver_detail_id', $driver->id)->findOrFail($accidentId)->delete();
        return back()->with('success', 'Accident record deleted.');
    }

    // -----------------------------------------------------------------------
    // POST admin/driver-recruitment/{driver}/upload-document
    // -----------------------------------------------------------------------
    public function uploadDocument(Request $request, int $driverId)
    {
        $request->validate([
            'collection' => 'required|string|in:driving_records,criminal_records,medical_records,clearing_house',
            'file'       => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        $driver = UserDriverDetail::findOrFail($driverId);
        $driver->addMediaFromRequest('file')->toMediaCollection($request->collection);

        return back()->with('success', 'Document uploaded successfully.');
    }

    // -----------------------------------------------------------------------
    // DELETE admin/driver-recruitment/{driver}/documents/{media}
    // -----------------------------------------------------------------------
    public function destroyDocument(int $driverId, int $mediaId)
    {
        $driver = UserDriverDetail::findOrFail($driverId);
        $media  = Media::where('model_id', $driver->id)
                       ->where('model_type', UserDriverDetail::class)
                       ->findOrFail($mediaId);
        $media->delete();
        return back()->with('success', 'Document deleted.');
    }

    /**
     * Safely format a date value that may be a string, Carbon, or null.
     * Output is always m/d/Y for the frontend.
     */
    private function safeDate(mixed $value, string $format = 'm/d/Y'): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            return Carbon::parse($value)->format($format);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Convert a Litepicker MM/DD/YYYY string to Y-m-d for DB storage.
     * Accepts both MM/DD/YYYY and Y-m-d transparently.
     */
    private function parseDateInput(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function buildStepsStatus(UserDriverDetail $driver): array
    {
        return [
            1  => $driver->user && $driver->last_name ? 'completed' : 'missing',
            2  => $driver->application && $driver->application->addresses->isNotEmpty() ? 'completed' : 'missing',
            3  => $driver->application && $driver->application->details ? 'completed' : 'missing',
            4  => $driver->licenses->isNotEmpty() ? 'completed' : 'missing',
            5  => $driver->medicalQualification ? 'completed' : 'missing',
            6  => 'completed', // Optional step — training schools are not required
            7  => 'completed', // Optional step — traffic violations are not required
            8  => 'completed', // Optional step — accidents are not required
            9  => $driver->fmcsrData ? 'completed' : 'missing',
            10 => ($driver->employmentCompanies->isNotEmpty() || $driver->has_completed_employment_history) ? 'completed' : 'missing',
            11 => $driver->certification ? 'completed' : 'missing',
        ];
    }
}
