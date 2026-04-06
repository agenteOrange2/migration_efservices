<?php

namespace App\Http\Controllers\Admin\Driver;

use App\Http\Controllers\Controller;
use App\Models\Admin\Driver\DriverApplication;
use App\Models\Carrier;
use App\Models\MigrationRecord;
use App\Models\UserDriverDetail;
use App\Services\Driver\StepCompletionCalculator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class DriverListController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search', '');
        $carrierFilter = $request->input('carrier', '');
        $perPage = (int) $request->input('per_page', 15);
        $tab = $request->input('tab', 'all');

        $query = UserDriverDetail::with(['user:id,name,email', 'carrier:id,name'])
            ->whereHas('application', fn ($q) => $q->where('status', DriverApplication::STATUS_APPROVED));

        switch ($tab) {
            case 'active':
                $query->where('status', UserDriverDetail::STATUS_ACTIVE);
                break;
            case 'inactive':
                $query->where('status', UserDriverDetail::STATUS_INACTIVE);
                break;
            case 'new':
                $query->whereDate('created_at', '>=', now()->subDays(30));
                break;
        }

        $query->orderBy('created_at', 'desc');

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"))
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (! empty($carrierFilter)) {
            $query->where('carrier_id', $carrierFilter);
        }

        $drivers = $query->paginate($perPage);
        $drivers->getCollection()->transform(fn ($driver) => $this->transformDriver($driver));

        $carriers = Carrier::orderBy('name')->get(['id', 'name']);

        $totalDriversCount = UserDriverDetail::whereHas('application', fn ($q) => $q->where('status', DriverApplication::STATUS_APPROVED))->count();
        $activeDriversCount = UserDriverDetail::whereHas('application', fn ($q) => $q->where('status', DriverApplication::STATUS_APPROVED))
            ->where('status', UserDriverDetail::STATUS_ACTIVE)->count();
        $inactiveDriversCount = UserDriverDetail::whereHas('application', fn ($q) => $q->where('status', DriverApplication::STATUS_APPROVED))
            ->where('status', UserDriverDetail::STATUS_INACTIVE)->count();
        $newDriversCount = UserDriverDetail::whereHas('application', fn ($q) => $q->where('status', DriverApplication::STATUS_APPROVED))
            ->whereDate('created_at', '>=', now()->subDays(30))->count();

        return Inertia::render('admin/drivers/Index', [
            'drivers' => $drivers,
            'carriers' => $carriers,
            'filters' => [
                'search' => $search,
                'carrier' => $carrierFilter,
                'per_page' => $perPage,
                'tab' => $tab,
            ],
            'stats' => [
                'total' => $totalDriversCount,
                'active' => $activeDriversCount,
                'inactive' => $inactiveDriversCount,
                'new' => $newDriversCount,
            ],
        ]);
    }

    public function show(UserDriverDetail $driver): Response
    {
        $driver->load([
            'user:id,name,email',
            'carrier:id,name,address,dot_number,mc_number,state',
            'application.addresses',
            'application.details',
            'licenses.endorsements',
            'medicalQualification',
            'experiences',
            'employmentCompanies.company',
            'relatedEmployments',
            'unemploymentPeriods',
            'trainingSchools',
            'courses',
            'accidents',
            'trafficConvictions',
            'testings',
            'inspections.vehicle',
            'vehicleAssignments.vehicle',
            'fmcsrData',
            'criminalHistory',
            'certification',
            'companyPolicy',
            'w9Form',
            'media',
        ]);

        $driverData = $this->buildDriverShowData($driver);

        return Inertia::render('admin/drivers/Show', $driverData);
    }

    public function destroy(UserDriverDetail $driver): \Illuminate\Http\RedirectResponse
    {
        try {
            $user = $driver->user;

            $driver->clearMediaCollection('profile_photo_driver');
            $driver->delete();

            if ($user) {
                $user->delete();
            }

            return redirect()->route('admin.drivers.index')
                ->with('success', 'Driver deleted successfully.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error deleting driver: ' . $e->getMessage());
            return back()->withErrors('Error deleting driver.');
        }
    }

    public function activate(UserDriverDetail $driver)
    {
        $driver->status = UserDriverDetail::STATUS_ACTIVE;
        $driver->save();

        return redirect()->route('admin.drivers.show', $driver)
            ->with('success', 'Driver has been activated.');
    }

    public function deactivate(UserDriverDetail $driver)
    {
        $driver->status = UserDriverDetail::STATUS_INACTIVE;
        $driver->save();

        return redirect()->route('admin.drivers.show', $driver)
            ->with('success', 'Driver has been deactivated.');
    }

    public function downloadDocuments(UserDriverDetail $driver): \Symfony\Component\HttpFoundation\BinaryFileResponse|StreamedResponse|\Illuminate\Http\RedirectResponse
    {
        $zipFileName = 'driver_' . $driver->id . '_documents_' . date('Y-m-d') . '.zip';
        $zipPath = storage_path('app/temp/' . $zipFileName);

        if (! file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return redirect()->route('admin.drivers.show', $driver)->with('error', 'Could not create ZIP file');
        }

        $driver->load([
            'licenses',
            'medicalQualification',
            'trainingSchools',
            'courses',
            'accidents',
            'trafficConvictions',
            'testings',
            'employmentCompanies',
            'application',
            'w9Form',
            'media',
        ]);

        // Licenses
        if ($driver->licenses) {
            foreach ($driver->licenses as $license) {
                $slug = $license->license_number ?? $license->id;
                $this->addMediaToZip($zip, $license, 'license_front', "Licenses/License_{$slug}_Front");
                $this->addMediaToZip($zip, $license, 'license_back', "Licenses/License_{$slug}_Back");
                $this->addMediaToZip($zip, $license, 'license_documents', "Licenses/License_{$slug}_Documents");
            }
        }

        // Medical
        if ($driver->medicalQualification) {
            $med = $driver->medicalQualification;
            $this->addMediaToZip($zip, $med, 'medical_card', 'Medical/Medical_Card');
            $this->addMediaToZip($zip, $med, 'social_security_card', 'Medical/Social_Security_Card');
            $this->addMediaToZip($zip, $med, 'medical_certificate', 'Medical/Certificate');
            $this->addMediaToZip($zip, $med, 'test_results', 'Medical/Test_Results');
            $this->addMediaToZip($zip, $med, 'additional_documents', 'Medical/Additional_Documents');
            $this->addMediaToZip($zip, $med, 'medical_documents', 'Medical/Medical_Documents');
        }

        // Training Schools
        if ($driver->trainingSchools) {
            foreach ($driver->trainingSchools as $i => $school) {
                $slug = $school->school_name ? preg_replace('/[^a-zA-Z0-9_-]/', '_', $school->school_name) : "School_{$i}";
                $this->addMediaToZip($zip, $school, 'school_certificates', "Training/{$slug}_Certificates");
            }
        }

        // Courses
        if ($driver->courses) {
            foreach ($driver->courses as $i => $course) {
                $slug = $course->organization_name ? preg_replace('/[^a-zA-Z0-9_-]/', '_', $course->organization_name) : "Course_{$i}";
                $this->addMediaToZip($zip, $course, 'course_certificates', "Courses/{$slug}_Certificates");
            }
        }

        // Accidents
        if ($driver->accidents) {
            foreach ($driver->accidents as $i => $accident) {
                $this->addMediaToZip($zip, $accident, 'accident-images', "Accidents/Accident_{$i}_Images");
            }
        }

        // Testing documents
        if ($driver->testings) {
            foreach ($driver->testings as $i => $test) {
                $this->addMediaToZip($zip, $test, 'drug_test_pdf', "Testing/Test_{$i}_Drug_Test");
                $this->addMediaToZip($zip, $test, 'test_results', "Testing/Test_{$i}_Results");
                $this->addMediaToZip($zip, $test, 'test_certificates', "Testing/Test_{$i}_Certificates");
                $this->addMediaToZip($zip, $test, 'test_authorization', "Testing/Test_{$i}_Authorization");
                $this->addMediaToZip($zip, $test, 'document_attachments', "Testing/Test_{$i}_Attachments");
            }
        }

        // Employment verification documents
        if ($driver->employmentCompanies) {
            foreach ($driver->employmentCompanies as $i => $emp) {
                $this->addMediaToZip($zip, $emp, 'employment_verification_documents', "Employment/Company_{$i}_Verification");
            }
        }

        // Traffic violations
        if ($driver->trafficConvictions) {
            foreach ($driver->trafficConvictions as $i => $conviction) {
                $this->addMediaToZip($zip, $conviction, 'traffic_images', "Traffic_Violations/Conviction_{$i}_Images");
            }
        }

        // Driver-level documents
        $this->addMediaToZip($zip, $driver, 'driving_records', 'Driving_Records/Record');
        $this->addMediaToZip($zip, $driver, 'criminal_records', 'Criminal_Records/Record');
        $this->addMediaToZip($zip, $driver, 'medical_records', 'Medical_Records/Record');
        $this->addMediaToZip($zip, $driver, 'clearing_house', 'Clearing_House/Document');
        $this->addMediaToZip($zip, $driver, 'w9_documents', 'W9_Documents/W9');
        $this->addMediaToZip($zip, $driver, 'dot_policy_documents', 'Dot_Policy_Documents/Policy');

        // W9 form PDF (filesystem path)
        if ($driver->w9Form && ! empty($driver->w9Form->pdf_path)) {
            $w9Path = storage_path('app/public/' . ltrim($driver->w9Form->pdf_path, '/'));
            if (! file_exists($w9Path)) {
                $w9Path = public_path($driver->w9Form->pdf_path);
            }
            if (file_exists($w9Path)) {
                $zip->addFile($w9Path, 'W9_Documents/W9_Form.pdf');
            }
        }

        // Application PDF
        if ($driver->application) {
            // Compiled full PDF (media library)
            if ($driver->application->hasMedia('application_pdf')) {
                $pdf = $driver->application->getFirstMedia('application_pdf');
                if ($pdf && file_exists($pdf->getPath())) {
                    $zip->addFile($pdf->getPath(), 'Complete_Application/Application.pdf');
                }
            }
            // Individual application forms (filesystem pdf_path)
            if (! empty($driver->application->pdf_path)) {
                $appPdfPath = storage_path('app/public/' . ltrim($driver->application->pdf_path, '/'));
                if (file_exists($appPdfPath)) {
                    $zip->addFile($appPdfPath, 'Individual_Application_Forms/Application_Form.pdf');
                }
            }
        }

        // Lease agreements (filesystem)
        $leaseDir = storage_path("app/public/driver/{$driver->id}/vehicle_verifications");
        if (is_dir($leaseDir)) {
            $leaseFiles = glob($leaseDir . '/*');
            foreach ($leaseFiles as $file) {
                if (is_file($file)) {
                    $zip->addFile($file, 'Lease_Agreements/' . basename($file));
                }
            }
        }

        $zip->close();

        return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
    }

    protected function addMediaToZip(ZipArchive $zip, $model, string $collection, string $zipPath): void
    {
        if (! method_exists($model, 'getMedia')) {
            return;
        }
        $media = $model->getMedia($collection);
        foreach ($media as $i => $item) {
            if (file_exists($item->getPath())) {
                $ext = $item->extension ?: pathinfo($item->file_name, PATHINFO_EXTENSION);
                $zip->addFile($item->getPath(), $zipPath . '_' . ($i + 1) . '.' . $ext);
            }
        }
    }

    protected function buildDriverShowData(UserDriverDetail $driver): array
    {
        $effectiveStatus = $driver->getEffectiveStatus();
        $profilePhotoUrl = $driver->getFirstMediaUrl('profile_photo_driver') ?: null;

        $base = [
            'id'                            => $driver->id,
            'full_name'                     => trim(($driver->user?->name ?? 'Unknown') . ' ' . ($driver->middle_name ?? '') . ' ' . ($driver->last_name ?? '')),
            'name'                          => $driver->user?->name ?? 'Unknown',
            'middle_name'                   => $driver->middle_name,
            'last_name'                     => $driver->last_name,
            'email'                         => $driver->user?->email ?? 'No email',
            'phone'                         => $driver->phone,
            'date_of_birth'                 => $driver->date_of_birth?->format('Y-m-d'),
            'effective_status'              => $effectiveStatus,
            'status'                        => $driver->status,
            'profile_photo_url'             => $profilePhotoUrl,
            'created_at'                    => $driver->created_at?->toIso8601String(),
            'hire_date'                     => $driver->hire_date?->format('Y-m-d'),
            'termination_date'              => $driver->termination_date?->format('Y-m-d'),
            'notes'                         => $driver->notes,
            'completion_percentage'         => (int) round(app(StepCompletionCalculator::class)->calculateTotalCompletion($driver->id)),
            // Emergency contact
            'emergency_contact_name'        => $driver->emergency_contact_name,
            'emergency_contact_phone'       => $driver->emergency_contact_phone,
            'emergency_contact_relationship'=> $driver->emergency_contact_relationship,
            // Carrier
            'carrier' => $driver->carrier ? [
                'id'         => $driver->carrier->id,
                'name'       => $driver->carrier->name,
                'address'    => $driver->carrier->address,
                'state'      => $driver->carrier->state,
                'dot_number' => $driver->carrier->dot_number,
                'mc_number'  => $driver->carrier->mc_number,
            ] : null,
            'application_status' => $driver->application?->status ?? null,
        ];

        // Addresses
        $addresses = [];
        if ($driver->application?->addresses) {
            foreach ($driver->application->addresses as $addr) {
                $addresses[] = [
                    'address_line1' => $addr->address_line1,
                    'address_line2' => $addr->address_line2,
                    'city'          => $addr->city,
                    'state'         => $addr->state,
                    'zip_code'      => $addr->zip_code,
                    'primary'       => $addr->primary ?? false,
                    'from_date'     => $addr->from_date?->format('Y-m-d'),
                    'to_date'       => $addr->to_date?->format('Y-m-d'),
                ];
            }
        }

        // Licenses
        $licenses = [];
        if ($driver->licenses) {
            foreach ($driver->licenses as $lic) {
                $licenses[] = [
                    'id'               => $lic->id,
                    'license_number'   => $lic->license_number,
                    'state_of_issue'   => $lic->state_of_issue ?? $lic->state ?? null,
                    'license_class'    => $lic->license_class,
                    'expiration_date'  => $lic->expiration_date?->format('Y-m-d'),
                    'is_cdl'           => $lic->is_cdl ?? false,
                    'status'           => $lic->status ?? null,
                    'license_front_url'=> $lic->getFirstMediaUrl('license_front') ?: null,
                    'license_back_url' => $lic->getFirstMediaUrl('license_back') ?: null,
                    'endorsements'     => $lic->endorsements?->map(fn ($e) => $e->name ?? $e->type ?? null)->filter()->values()->toArray() ?? [],
                ];
            }
        }

        // Driving Experience
        $experiences = [];
        if ($driver->experiences) {
            foreach ($driver->experiences as $e) {
                $experiences[] = [
                    'equipment_type'  => $e->equipment_type,
                    'years_experience'=> $e->years_experience,
                    'miles_driven'    => $e->miles_driven,
                    'requires_cdl'    => $e->requires_cdl ?? false,
                ];
            }
        }

        // Medical
        $medical = null;
        if ($driver->medicalQualification) {
            $m = $driver->medicalQualification;
            $medExpiry = $m->medical_card_expiration_date;
            $medStatus = $medExpiry ? ($medExpiry->isFuture() ? ($medExpiry->diffInDays(now()) <= 90 ? 'expiring_soon' : 'valid') : 'expired') : 'unknown';
            $ssn = $m->social_security_number;
            $ssnMasked = $ssn ? 'XXX-XX-' . substr($ssn, -4) : null;
            $medicalRecords = $driver->getMedia('medical_records')->map(fn ($doc) => [
                'name' => $doc->file_name,
                'url'  => $doc->getUrl(),
                'size' => $doc->human_readable_size,
            ])->values()->all();
            $medical = [
                'medical_card_expiration_date' => $medExpiry?->format('Y-m-d'),
                'medical_examiner_name'        => $m->medical_examiner_name,
                'medical_examiner_registry'    => $m->medical_examiner_registry_number ?? null,
                'medical_status'               => $medStatus,
                'ssn_masked'                   => $ssnMasked,
                'medical_card_url'             => $m->getFirstMediaUrl('medical_card') ?: null,
                'social_security_card_url'     => $m->getFirstMediaUrl('social_security_card') ?: null,
                'medical_certificate_url'      => $m->getFirstMediaUrl('medical_certificate') ?: null,
                'medical_records'              => $medicalRecords,
            ];
        }

        // Employment
        $employment = [];
        if ($driver->employmentCompanies) {
            foreach ($driver->employmentCompanies as $ec) {
                $employment[] = [
                    'company_name'              => $ec->company?->company_name ?? null,
                    'company_address'           => $ec->company?->address ?? null,
                    'company_phone'             => $ec->company?->phone ?? null,
                    'positions_held'            => $ec->positions_held ?? null,
                    'from_date'                 => $ec->employed_from?->format('Y-m-d'),
                    'to_date'                   => $ec->employed_to?->format('Y-m-d'),
                    'subject_to_fmcsr'          => $ec->subject_to_fmcsr ?? false,
                    'safety_sensitive_function' => $ec->safety_sensitive_function ?? false,
                    'reason_for_leaving'        => $ec->reason_for_leaving ?? null,
                    'other_reason_description'  => $ec->other_reason_description ?? null,
                    'email'                     => $ec->email ?? null,
                    'email_sent'                => $ec->email_sent ?? false,
                    'verification_status'       => $ec->verification_status ?? null,
                    'verification_date'         => $ec->verification_date?->format('Y-m-d'),
                    'explanation'               => $ec->explanation ?? null,
                ];
            }
        }

        // Related Employments
        $relatedEmployments = $driver->relatedEmployments?->map(fn ($re) => [
            'position'     => $re->position ?? null,
            'comments'     => $re->comments ?? null,
            'from_date'    => $re->start_date?->format('Y-m-d') ?? null,
            'to_date'      => $re->end_date?->format('Y-m-d') ?? null,
        ])->toArray() ?? [];

        // Unemployment Periods
        $unemploymentPeriods = $driver->unemploymentPeriods?->map(fn ($up) => [
            'from_date' => $up->start_date?->format('Y-m-d') ?? null,
            'to_date'   => $up->end_date?->format('Y-m-d') ?? null,
            'comments'  => $up->comments ?? null,
        ])->toArray() ?? [];

        // Training Schools
        $trainingSchools = [];
        if ($driver->trainingSchools) {
            foreach ($driver->trainingSchools as $ts) {
                $trainingSchools[] = [
                    'name'            => $ts->school_name ?? null,
                    'city'            => $ts->city ?? null,
                    'state'           => $ts->state ?? null,
                    'from_date'       => $ts->date_start?->format('Y-m-d') ?? null,
                    'to_date'         => $ts->date_end?->format('Y-m-d') ?? null,
                    'graduated'       => $ts->graduated ?? false,
                    'subject_fmcsr'   => $ts->subject_to_safety_regulations ?? false,
                    'certificate_url' => $ts->getFirstMediaUrl('school_certificates') ?: null,
                ];
            }
        }

        // Courses
        $courses = [];
        if ($driver->courses) {
            foreach ($driver->courses as $c) {
                $courses[] = [
                    'organization_name' => $c->organization_name ?? null,
                    'location'          => $c->location ?? null,
                    'status'            => $c->status ?? null,
                    'certification_date'=> $c->certification_date?->format('Y-m-d') ?? null,
                    'expiration_date'   => $c->expiration_date?->format('Y-m-d') ?? null,
                    'certificate_url'   => $c->getFirstMediaUrl('course_certificates') ?: null,
                ];
            }
        }

        // Testings
        $testings = [];
        if ($driver->testings) {
            foreach ($driver->testings as $t) {
                $testings[] = [
                    'id'                   => $t->id,
                    'test_date'            => $t->test_date?->format('Y-m-d'),
                    'test_type'            => $t->test_type ?? null,
                    'test_result'          => $t->test_result ?? null,
                    'status'               => $t->status ?? null,
                    'administered_by'      => $t->administered_by ?? null,
                    'location'             => $t->location ?? null,
                    'next_test_due'        => $t->next_test_due?->format('Y-m-d'),
                    'notes'                => $t->notes ?? null,
                    'is_random_test'       => $t->is_random_test ?? false,
                    'is_post_accident_test'=> $t->is_post_accident_test ?? false,
                    'is_pre_employment_test'=> $t->is_pre_employment_test ?? false,
                    'drug_test_pdf_url'    => $t->getFirstMediaUrl('drug_test_pdf') ?: null,
                    'test_results_url'     => $t->getFirstMediaUrl('test_results') ?: null,
                ];
            }
        }

        // Inspections
        $inspections = [];
        if ($driver->inspections) {
            foreach ($driver->inspections as $i) {
                $inspections[] = [
                    'inspection_date'      => $i->inspection_date?->format('Y-m-d'),
                    'inspection_type'      => $i->inspection_type ?? null,
                    'inspection_level'     => $i->inspection_level ?? null,
                    'inspector_name'       => $i->inspector_name ?? null,
                    'location'             => $i->location ?? null,
                    'status'               => $i->status ?? null,
                    'defects_found'        => $i->defects_found ?? null,
                    'is_defects_corrected' => $i->is_defects_corrected ?? false,
                    'corrective_actions'   => $i->corrective_actions ?? null,
                    'notes'                => $i->notes ?? null,
                    'vehicle'              => $i->vehicle ? $i->vehicle->make . ' ' . $i->vehicle->model . ($i->vehicle->year ? ' (' . $i->vehicle->year . ')' : '') : null,
                ];
            }
        }

        // Vehicle Assignments
        $vehicleAssignments = [];
        if ($driver->vehicleAssignments) {
            foreach ($driver->vehicleAssignments as $va) {
                $vehicleAssignments[] = [
                    'driver_type' => $va->driver_type ?? null,
                    'start_date'  => $va->start_date?->format('Y-m-d'),
                    'end_date'    => $va->end_date?->format('Y-m-d'),
                    'status'      => $va->status ?? null,
                    'notes'       => $va->notes ?? null,
                    'vehicle'     => $va->vehicle ? [
                        'make'  => $va->vehicle->make ?? null,
                        'model' => $va->vehicle->model ?? null,
                        'year'  => $va->vehicle->year ?? null,
                        'vin'   => $va->vehicle->vin ?? null,
                    ] : null,
                ];
            }
        }

        // FMCSR Data
        $fmcsrData = $driver->fmcsrData ? [
            'is_disqualified'        => $driver->fmcsrData->is_disqualified ?? false,
            'disqualified_details'   => $driver->fmcsrData->disqualified_details ?? null,
            'is_license_suspended'   => $driver->fmcsrData->is_license_suspended ?? false,
            'suspension_details'     => $driver->fmcsrData->suspension_details ?? null,
            'is_license_denied'      => $driver->fmcsrData->is_license_denied ?? false,
            'denial_details'         => $driver->fmcsrData->denial_details ?? null,
            'has_positive_drug_test' => $driver->fmcsrData->has_positive_drug_test ?? false,
            'has_duty_offenses'      => $driver->fmcsrData->has_duty_offenses ?? false,
            'offense_details'        => $driver->fmcsrData->offense_details ?? null,
            'consent_driving_record' => $driver->fmcsrData->consent_driving_record ?? false,
        ] : null;

        // Criminal History
        $criminalHistory = $driver->criminalHistory ? [
            'has_criminal_charges'  => $driver->criminalHistory->has_criminal_charges ?? false,
            'has_felony_conviction' => $driver->criminalHistory->has_felony_conviction ?? false,
            'has_minister_permit'   => $driver->criminalHistory->has_minister_permit ?? false,
            'fcra_consent'          => $driver->criminalHistory->fcra_consent ?? false,
        ] : null;

        // HOS (Hours of Service) Data
        $hosData = [
            'cycle_type'               => $driver->hos_cycle_type ?? '70_8',
            'change_requested'         => $driver->hos_cycle_change_requested ?? false,
            'change_requested_to'      => $driver->hos_cycle_change_requested_to ?? null,
            'change_requested_at'      => $driver->hos_cycle_change_requested_at?->format('Y-m-d H:i'),
            'change_approved_at'       => $driver->hos_cycle_change_approved_at?->format('Y-m-d H:i'),
        ];

        // Wizard Steps (using StepCompletionCalculator)
        $wizardSummary = app(StepCompletionCalculator::class)->getCompletionSummary($driver->id);
        $stepLabels = [
            1  => 'General Information',
            2  => 'Address',
            3  => 'Application Details',
            4  => 'Licenses',
            5  => 'Medical',
            6  => 'Training Schools',
            7  => 'Traffic Violations',
            8  => 'Accidents',
            9  => 'FMCSR',
            10 => 'Work History',
            11 => 'Company Policy',
            12 => 'Criminal History',
            13 => 'W-9 Form',
            14 => 'Certification',
            15 => 'Confirmation',
        ];
        $wizardSteps = collect($wizardSummary['steps'] ?? [])->map(fn ($s, $step) => [
            'step'       => $step,
            'label'      => $stepLabels[$step] ?? "Step {$step}",
            'status'     => $s['status'],
            'percentage' => $s['percentage'],
        ])->values()->all();

        // Migration History
        $migrationHistory = MigrationRecord::where('driver_user_id', $driver->user_id)
            ->with(['sourceCarrier:id,name', 'targetCarrier:id,name', 'migratedByUser:id,name'])
            ->latest('migrated_at')
            ->get()
            ->map(fn ($m) => [
                'id'              => $m->id,
                'migrated_at'     => $m->migrated_at->format('m/d/Y H:i'),
                'migrated_at_raw' => $m->migrated_at->toIso8601String(),
                'source_carrier'  => $m->sourceCarrier?->name ?? 'Unknown',
                'target_carrier'  => $m->targetCarrier?->name ?? 'Unknown',
                'performed_by'    => $m->migratedByUser?->name ?? 'System',
                'reason'          => $m->reason,
                'notes'           => $m->notes,
                'status'          => $m->status,
                'can_rollback'    => $m->canRollback(),
                'rolled_back_at'  => $m->rolled_back_at?->format('m/d/Y H:i'),
                'rollback_reason' => $m->rollback_reason,
            ])->all();

        $documentsByCategory = $this->buildDocumentsByCategory($driver);
        $stats = $this->buildDriverStats($driver, $documentsByCategory);

        return [
            'driver' => array_merge($base, [
                'addresses'           => $addresses,
                'licenses'            => $licenses,
                'experiences'         => $experiences,
                'medical'             => $medical,
                'employment'          => $employment,
                'related_employments' => $relatedEmployments,
                'unemployment_periods'=> $unemploymentPeriods,
                'training_schools'    => $trainingSchools,
                'courses'             => $courses,
                'testings'            => $testings,
                'inspections'         => $inspections,
                'vehicle_assignments' => $vehicleAssignments,
                'fmcsr_data'          => $fmcsrData,
                'criminal_history'    => $criminalHistory,
                'hos_data'            => $hosData,
                'wizard_steps'        => $wizardSteps,
                'wizard_total_pct'    => (int) round($wizardSummary['total_percentage'] ?? 0),
                'migration_history'   => $migrationHistory,
                'accidents'           => $driver->accidents?->map(fn ($a) => [
                    'accident_date'       => $a->accident_date?->format('Y-m-d'),
                    'nature_of_accident'  => $a->nature_of_accident ?? null,
                    'had_fatalities'      => $a->had_fatalities ?? false,
                    'had_injuries'        => $a->had_injuries ?? false,
                    'number_of_fatalities'=> $a->number_of_fatalities ?? 0,
                    'number_of_injuries'  => $a->number_of_injuries ?? 0,
                    'comments'            => $a->comments ?? null,
                ])->toArray() ?? [],
                'traffic_convictions' => $driver->trafficConvictions?->map(fn ($t) => [
                    'conviction_date' => $t->conviction_date?->format('Y-m-d'),
                    'location'        => $t->location ?? null,
                    'charge'          => $t->charge ?? null,
                    'penalty'         => $t->penalty ?? null,
                    'conviction_type' => $t->conviction_type ?? null,
                    'description'     => $t->description ?? null,
                ])->toArray() ?? [],
            ]),
            'documentsByCategory' => $documentsByCategory,
            'stats'               => $stats,
        ];
    }

    protected function buildDocumentsByCategory(UserDriverDetail $driver): array
    {
        $h = fn ($m) => [
            'name'         => $m->file_name,
            'url'          => $m->getUrl(),
            'size'         => $this->formatFileSize($m->size),
            'date'         => $m->created_at->format('Y-m-d'),
            'related_info' => '',
        ];

        $categories = [
            'license'                          => [],
            'medical'                          => [],
            'training_schools'                 => [],
            'courses'                          => [],
            'accidents'                        => [],
            'traffic'                          => [],
            'inspections'                      => [],
            'testing'                          => [],
            'records'                          => [],
            'application_forms'                => [],
            'employment_verification'          => [],
            'employment_verification_attempts' => [],
            'w9_documents'                     => [],
            'dot_policy_documents'             => [],
            'certification'                    => [],
        ];

        // ── Licenses ──────────────────────────────────────────────────────────
        foreach ($driver->licenses ?? [] as $lic) {
            $label = 'License ' . ($lic->license_number ?? '');
            foreach (['license_front', 'license_back', 'license_documents'] as $col) {
                foreach ($lic->getMedia($col) as $m) {
                    $row = $h($m);
                    $row['related_info'] = $label;
                    $categories['license'][] = $row;
                }
            }
        }

        // ── Medical ───────────────────────────────────────────────────────────
        if ($driver->medicalQualification) {
            foreach (['medical_card', 'social_security_card', 'medical_certificate', 'test_results', 'additional_documents', 'medical_documents'] as $col) {
                foreach ($driver->medicalQualification->getMedia($col) as $m) {
                    $row = $h($m);
                    $row['related_info'] = 'Medical';
                    $categories['medical'][] = $row;
                }
            }
        }

        // ── Training Schools ──────────────────────────────────────────────────
        foreach ($driver->trainingSchools ?? [] as $ts) {
            foreach ($ts->getMedia('school_certificates') as $m) {
                $row = $h($m);
                $row['related_info'] = $ts->school_name ?? 'Training';
                $categories['training_schools'][] = $row;
            }
        }

        // ── Courses ───────────────────────────────────────────────────────────
        foreach ($driver->courses ?? [] as $c) {
            foreach ($c->getMedia('course_certificates') as $m) {
                $row = $h($m);
                $row['related_info'] = $c->organization_name ?? 'Course';
                $categories['courses'][] = $row;
            }
        }

        // ── Accidents ─────────────────────────────────────────────────────────
        foreach ($driver->accidents ?? [] as $a) {
            foreach ($a->getMedia('accident-images') as $m) {
                $row = $h($m);
                $row['related_info'] = 'Accident ' . ($a->accident_date?->format('m/d/Y') ?? '');
                $categories['accidents'][] = $row;
            }
        }

        // ── Traffic Convictions ───────────────────────────────────────────────
        foreach ($driver->trafficConvictions ?? [] as $tc) {
            foreach ($tc->getMedia('traffic_images') as $m) {
                $row = $h($m);
                $row['related_info'] = 'Traffic Conviction';
                $categories['traffic'][] = $row;
            }
        }

        // ── Inspections ───────────────────────────────────────────────────────
        foreach ($driver->inspections ?? [] as $i) {
            foreach ($i->getMedia('inspection_documents') as $m) {
                $row = $h($m);
                $row['related_info'] = 'Inspection ' . ($i->inspection_date?->format('m/d/Y') ?? '');
                $categories['inspections'][] = $row;
            }
        }

        // ── Testing ───────────────────────────────────────────────────────────
        foreach ($driver->testings ?? [] as $t) {
            $typeLabel = $t->test_type ? str_replace('_', ' ', ucfirst($t->test_type)) : 'Test';
            $dateLabel = $t->test_date?->format('m/d/Y') ?? '';
            foreach (['drug_test_pdf', 'test_results', 'test_certificates', 'test_authorization', 'document_attachments'] as $col) {
                foreach ($t->getMedia($col) as $m) {
                    $row = $h($m);
                    $row['related_info'] = trim("{$typeLabel} - {$dateLabel}");
                    $row['type'] = match($col) {
                        'drug_test_pdf'       => 'Drug Test',
                        'test_results'        => 'Test Attachment',
                        'test_certificates'   => 'Certificate',
                        'test_authorization'  => 'Authorization',
                        'document_attachments'=> 'Attachment',
                        default               => 'Testing',
                    };
                    $categories['testing'][] = $row;
                }
            }
        }

        // ── Records (MVR, criminal, medical, clearing house) ──────────────────
        foreach ($driver->getMedia('driving_records') as $m) {
            $row = $h($m); $row['related_info'] = 'Driving Record';
            $categories['records'][] = $row;
        }
        foreach ($driver->getMedia('medical_records') as $m) {
            $row = $h($m); $row['related_info'] = 'Medical Record';
            $categories['records'][] = $row;
        }
        foreach ($driver->getMedia('criminal_records') as $m) {
            $row = $h($m); $row['related_info'] = 'Criminal Record';
            $categories['records'][] = $row;
        }
        foreach ($driver->getMedia('clearing_house') as $m) {
            $row = $h($m); $row['related_info'] = 'Clearing House';
            $categories['records'][] = $row;
        }

        // ── Application Forms ─────────────────────────────────────────────────
        if ($driver->application) {
            foreach ($driver->application->getMedia('application_pdf') as $m) {
                $row = $h($m); $row['related_info'] = 'Complete Application';
                $categories['application_forms'][] = $row;
            }
        }
        foreach ($driver->getMedia('application_forms') as $m) {
            $row = $h($m); $row['related_info'] = 'Application Form';
            $categories['application_forms'][] = $row;
        }
        foreach ($driver->getMedia('individual_forms') as $m) {
            $row = $h($m); $row['related_info'] = 'Individual Form';
            $categories['application_forms'][] = $row;
        }
        foreach ($driver->getMedia('signed_application') as $m) {
            $row = $h($m); $row['related_info'] = 'Signed Application';
            $categories['application_forms'][] = $row;
        }
        foreach ($driver->getMedia('contract_documents') as $m) {
            $row = $h($m); $row['related_info'] = 'Contract';
            $categories['application_forms'][] = $row;
        }
        foreach ($driver->getMedia('lease_agreement') as $m) {
            $row = $h($m); $row['related_info'] = 'Lease Agreement';
            $categories['application_forms'][] = $row;
        }

        // ── Certification (driver app certification + Spatie certification model) ──
        if ($driver->certification) {
            foreach ($driver->certification->getMedia('certification_documents') as $m) {
                $row = $h($m); $row['related_info'] = 'Certification';
                $categories['certification'][] = $row;
            }
        }

        // ── Employment Verification ───────────────────────────────────────────
        foreach ($driver->employmentCompanies ?? [] as $ec) {
            $companyName = $ec->company?->company_name ?? 'Company';
            foreach ($ec->getMedia('employment_verification_documents') as $m) {
                $row = $h($m);
                $row['related_info'] = 'Manual Upload';
                $row['type']         = 'Employment verification';
                $row['company']      = $companyName;
                $categories['employment_verification'][] = $row;
            }
            foreach ($ec->getMedia('signature') as $m) {
                $row = $h($m);
                $row['related_info'] = 'Email Verified';
                $row['type']         = 'Employment verification email';
                $row['company']      = $companyName;
                $categories['employment_verification'][] = $row;
            }
        }

        // ── Employment Verification Attempts (on driver itself) ───────────────
        foreach ($driver->getMedia('employment_verification_attempts') as $i => $m) {
            $row = $h($m);
            $row['related_info'] = 'Manual Upload';
            $row['type']         = 'Employment verification attempt';
            $row['company']      = 'Attempt #' . ($i + 1);
            $categories['employment_verification_attempts'][] = $row;
        }

        // ── W-9 ───────────────────────────────────────────────────────────────
        foreach ($driver->getMedia('w9_documents') as $m) {
            $row = $h($m); $row['related_info'] = 'W-9 Tax Form';
            $row['type'] = 'W9';
            $categories['w9_documents'][] = $row;
        }

        // ── DOT Drug & Alcohol Policy ─────────────────────────────────────────
        foreach ($driver->getMedia('dot_policy_documents') as $m) {
            $row = $h($m); $row['related_info'] = 'DOT Drug & Alcohol Policy';
            $row['type'] = 'Dot policy';
            $categories['dot_policy_documents'][] = $row;
        }

        // Remove empty categories so the frontend only shows what exists
        return array_filter($categories, fn ($docs) => count($docs) > 0);
    }

    protected function buildDriverStats(UserDriverDetail $driver, array $documentsByCategory): array
    {
        $totalDocuments = 0;
        foreach ($documentsByCategory as $docs) {
            $totalDocuments += count($docs);
        }

        $recordsUploaded = count($documentsByCategory['records'] ?? []);

        $medicalStatus = 'Expired';
        if ($driver->medicalQualification?->medical_card_expiration_date) {
            $expiry = $driver->medicalQualification->medical_card_expiration_date;
            $medicalStatus = $expiry->isFuture() ? 'Valid' : 'Expired';
        }

        $testingCount = $driver->testings?->count() ?? 0;
        $vehiclesCount = $driver->vehicleAssignments?->count() ?? 0;

        return [
            'total_documents' => $totalDocuments,
            'licenses_count' => $driver->licenses?->count() ?? 0,
            'medical_status' => $medicalStatus,
            'medical_expiration' => $driver->medicalQualification?->medical_card_expiration_date?->format('Y-m-d'),
            'records_uploaded' => $recordsUploaded,
            'testing_count' => $testingCount,
            'testing_status' => $testingCount > 0 ? 'Tests Completed' : 'No Tests',
            'vehicles_count' => $vehiclesCount,
            'vehicles_status' => $vehiclesCount > 0 ? 'Vehicles Assigned' : 'No Vehicles',
        ];
    }

    protected function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    protected function transformDriver(UserDriverDetail $driver): array
    {
        $completionPercentage = $this->calculateProfileCompleteness($driver);
        $effectiveStatus = $driver->getEffectiveStatus();

        return [
            'id' => $driver->id,
            'name' => $driver->user?->name ?? 'Unknown',
            'last_name' => $driver->last_name,
            'full_name' => trim(($driver->user?->name ?? 'Unknown') . ' ' . ($driver->last_name ?? '')),
            'email' => $driver->user?->email ?? 'No email',
            'carrier_name' => $driver->carrier?->name ?? 'No carrier',
            'carrier_id' => $driver->carrier_id,
            'status' => $driver->status,
            'effective_status' => $effectiveStatus,
            'completion_percentage' => $completionPercentage,
            'created_at' => $driver->created_at,
            'profile_photo_url' => $driver->getFirstMediaUrl('profile_photo_driver') ?: null,
        ];
    }

    protected function calculateProfileCompleteness(UserDriverDetail $driver): int
    {
        $completedSteps = 0;
        $totalSteps = 6;

        if ($driver->user && $driver->user->email && $driver->phone) {
            $completedSteps++;
        }
        if ($driver->licenses()->exists()) {
            $completedSteps++;
        }
        if ($driver->medicalQualification()->exists()) {
            $completedSteps++;
        }
        if ($driver->experiences()->exists() || $driver->trainingSchools()->exists()) {
            $completedSteps++;
        }
        if ($driver->employmentCompanies()->exists()) {
            $completedSteps++;
        }
        if ($driver->hasRequiredDocuments()) {
            $completedSteps++;
        }

        return (int) round(($completedSteps / $totalSteps) * 100);
    }
}
