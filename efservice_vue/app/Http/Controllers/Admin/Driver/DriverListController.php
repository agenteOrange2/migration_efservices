<?php

namespace App\Http\Controllers\Admin\Driver;

use App\Http\Controllers\Controller;
use App\Models\Admin\Driver\DriverApplication;
use App\Models\Carrier;
use App\Models\UserDriverDetail;
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
        ]);

        $driverData = $this->buildDriverShowData($driver);

        return Inertia::render('admin/drivers/Show', $driverData);
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

    public function downloadDocuments(UserDriverDetail $driver): StreamedResponse|\Illuminate\Http\RedirectResponse
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

        $driver->load(['licenses', 'medicalQualification', 'trainingSchools', 'application']);

        if ($driver->licenses) {
            foreach ($driver->licenses as $license) {
                $this->addMediaToZip($zip, $license, 'license_front', 'Licenses/License_' . ($license->license_number ?? '') . '_Front');
                $this->addMediaToZip($zip, $license, 'license_back', 'Licenses/License_' . ($license->license_number ?? '') . '_Back');
            }
        }

        if ($driver->medicalQualification) {
            $this->addMediaToZip($zip, $driver->medicalQualification, 'medical_card', 'Medical/Medical_Card');
            $this->addMediaToZip($zip, $driver->medicalQualification, 'social_security_card', 'Medical/Social_Security_Card');
        }

        if ($driver->application && $driver->application->hasMedia('application_pdf')) {
            $pdf = $driver->application->getFirstMedia('application_pdf');
            if ($pdf && file_exists($pdf->getPath())) {
                $zip->addFile($pdf->getPath(), 'Application/Complete_Application.pdf');
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
            'completion_percentage'         => $driver->completion_percentage ?? 0,
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
            $medStatus = $medExpiry ? ($medExpiry->isFuture() ? ($medExpiry->diffInDays(now()) <= 30 ? 'expiring_soon' : 'valid') : 'expired') : 'unknown';
            $medical = [
                'medical_card_expiration_date' => $medExpiry?->format('Y-m-d'),
                'medical_examiner_name'        => $m->medical_examiner_name,
                'medical_examiner_registry'    => $m->medical_examiner_registry ?? null,
                'medical_status'               => $medStatus,
                'medical_card_url'             => $m->getFirstMediaUrl('medical_card') ?: null,
                'social_security_card_url'     => $m->getFirstMediaUrl('social_security_card') ?: null,
                'medical_certificate_url'      => $m->getFirstMediaUrl('medical_certificate') ?: null,
            ];
        }

        // Employment
        $employment = [];
        if ($driver->employmentCompanies) {
            foreach ($driver->employmentCompanies as $ec) {
                $employment[] = [
                    'company_name'              => $ec->company?->name ?? null,
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
            'period'       => $re->period ?? null,
            'position'     => $re->position ?? null,
            'work_position'=> $re->work_position ?? null,
            'comments'     => $re->comments ?? null,
            'from_date'    => $re->from_date?->format('Y-m-d') ?? null,
            'to_date'      => $re->to_date?->format('Y-m-d') ?? null,
        ])->toArray() ?? [];

        // Unemployment Periods
        $unemploymentPeriods = $driver->unemploymentPeriods?->map(fn ($up) => [
            'from_date'   => $up->from_date?->format('Y-m-d') ?? null,
            'to_date'     => $up->to_date?->format('Y-m-d') ?? null,
            'comments'    => $up->comments ?? null,
            'type'        => $up->type ?? null,
        ])->toArray() ?? [];

        // Training Schools
        $trainingSchools = [];
        if ($driver->trainingSchools) {
            foreach ($driver->trainingSchools as $ts) {
                $trainingSchools[] = [
                    'name'            => $ts->name ?? $ts->school_name ?? null,
                    'location'        => $ts->location ?? null,
                    'from_date'       => $ts->from_date?->format('Y-m-d') ?? null,
                    'to_date'         => $ts->to_date?->format('Y-m-d') ?? null,
                    'graduated'       => $ts->graduated ?? null,
                    'subject_fmcsr'   => $ts->subject_fmcsr ?? null,
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
        $categories = [
            'license' => [],
            'medical' => [],
            'training_schools' => [],
            'courses' => [],
            'accidents' => [],
            'traffic' => [],
            'inspections' => [],
            'testing' => [],
            'records' => [],
            'certification' => [],
        ];

        foreach ($driver->licenses ?? [] as $lic) {
            foreach (['license_front', 'license_back', 'license_documents'] as $col) {
                foreach ($lic->getMedia($col) as $m) {
                    $categories['license'][] = [
                        'name' => $m->file_name,
                        'url' => $m->getUrl(),
                        'size' => $this->formatFileSize($m->size),
                        'date' => $m->created_at->format('Y-m-d'),
                        'related_info' => 'License ' . ($lic->license_number ?? ''),
                    ];
                }
            }
        }

        if ($driver->medicalQualification) {
            foreach (['medical_card', 'social_security_card', 'medical_certificate'] as $col) {
                foreach ($driver->medicalQualification->getMedia($col) as $m) {
                    $categories['medical'][] = [
                        'name' => $m->file_name,
                        'url' => $m->getUrl(),
                        'size' => $this->formatFileSize($m->size),
                        'date' => $m->created_at->format('Y-m-d'),
                        'related_info' => 'Medical',
                    ];
                }
            }
        }

        foreach ($driver->getMedia('driving_records') as $m) {
            $categories['records'][] = ['name' => $m->file_name, 'url' => $m->getUrl(), 'size' => $this->formatFileSize($m->size), 'date' => $m->created_at->format('Y-m-d'), 'related_info' => 'Driving Record'];
        }
        foreach ($driver->getMedia('medical_records') as $m) {
            $categories['records'][] = ['name' => $m->file_name, 'url' => $m->getUrl(), 'size' => $this->formatFileSize($m->size), 'date' => $m->created_at->format('Y-m-d'), 'related_info' => 'Medical Record'];
        }
        foreach ($driver->getMedia('criminal_records') as $m) {
            $categories['records'][] = ['name' => $m->file_name, 'url' => $m->getUrl(), 'size' => $this->formatFileSize($m->size), 'date' => $m->created_at->format('Y-m-d'), 'related_info' => 'Criminal Record'];
        }
        foreach ($driver->getMedia('clearing_house') as $m) {
            $categories['records'][] = ['name' => $m->file_name, 'url' => $m->getUrl(), 'size' => $this->formatFileSize($m->size), 'date' => $m->created_at->format('Y-m-d'), 'related_info' => 'Clearing House'];
        }

        foreach ($driver->trainingSchools ?? [] as $ts) {
            foreach ($ts->getMedia('school_certificates') as $m) {
                $categories['training_schools'][] = ['name' => $m->file_name, 'url' => $m->getUrl(), 'size' => $this->formatFileSize($m->size), 'date' => $m->created_at->format('Y-m-d'), 'related_info' => $ts->name ?? 'Training'];
            }
        }

        foreach ($driver->courses ?? [] as $c) {
            foreach ($c->getMedia('course_certificates') as $m) {
                $categories['courses'][] = ['name' => $m->file_name, 'url' => $m->getUrl(), 'size' => $this->formatFileSize($m->size), 'date' => $m->created_at->format('Y-m-d'), 'related_info' => $c->organization_name ?? 'Course'];
            }
        }

        foreach ($driver->accidents ?? [] as $a) {
            foreach ($a->getMedia('accident-images') as $m) {
                $categories['accidents'][] = ['name' => $m->file_name, 'url' => $m->getUrl(), 'size' => $this->formatFileSize($m->size), 'date' => $m->created_at->format('Y-m-d'), 'related_info' => 'Accident'];
            }
        }

        foreach ($driver->trafficConvictions ?? [] as $tc) {
            foreach ($tc->getMedia('traffic_images') as $m) {
                $categories['traffic'][] = ['name' => $m->file_name, 'url' => $m->getUrl(), 'size' => $this->formatFileSize($m->size), 'date' => $m->created_at->format('Y-m-d'), 'related_info' => 'Traffic'];
            }
        }

        foreach ($driver->testings ?? [] as $t) {
            foreach (['drug_test_pdf', 'test_results', 'test_certificates'] as $col) {
                foreach ($t->getMedia($col) as $m) {
                    $categories['testing'][] = ['name' => $m->file_name, 'url' => $m->getUrl(), 'size' => $this->formatFileSize($m->size), 'date' => $m->created_at->format('Y-m-d'), 'related_info' => 'Testing'];
                }
            }
        }

        foreach ($driver->inspections ?? [] as $i) {
            foreach ($i->getMedia('inspection_documents') as $m) {
                $categories['inspections'][] = ['name' => $m->file_name, 'url' => $m->getUrl(), 'size' => $this->formatFileSize($m->size), 'date' => $m->created_at->format('Y-m-d'), 'related_info' => 'Inspection'];
            }
        }

        if ($driver->application) {
            foreach (['application_pdf', 'application_documents'] as $col) {
                foreach ($driver->application->getMedia($col) as $m) {
                    $categories['certification'][] = ['name' => $m->file_name, 'url' => $m->getUrl(), 'size' => $this->formatFileSize($m->size), 'date' => $m->created_at->format('Y-m-d'), 'related_info' => 'Application'];
                }
            }
        }

        return $categories;
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
