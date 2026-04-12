<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\Admin\Driver\DriverLicense;
use App\Models\Admin\Driver\DriverMedicalQualification;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Admin\Vehicle\VehicleMaintenance;
use App\Models\EmergencyRepair;
use App\Models\Hos\HosViolation;
use App\Models\Trip;
use App\Models\UserDriverDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use ZipArchive;

class DriverDashboardController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();
        $driver = $user->driverDetails;

        if (!$driver) {
            abort(403, 'No driver profile associated with this account.');
        }

        $driverId = $driver->id;
        $now = Carbon::now();
        $expiringThreshold = $now->copy()->addDays(30);

        // --- Trip stats ---
        $tripQuery = Trip::where('user_driver_detail_id', $driverId);

        $totalTrips     = (clone $tripQuery)->count();
        $activeTrips    = (clone $tripQuery)->whereIn('status', [Trip::STATUS_IN_PROGRESS, Trip::STATUS_ACCEPTED, Trip::STATUS_PENDING])->count();
        $completedTrips = (clone $tripQuery)->where('status', Trip::STATUS_COMPLETED)->count();
        $cancelledTrips = (clone $tripQuery)->where('status', Trip::STATUS_CANCELLED)->count();

        $recentTrips = (clone $tripQuery)
            ->with('vehicle:id,company_unit_number,vin')
            ->orderByDesc('scheduled_start_date')
            ->take(5)
            ->get()
            ->map(fn($t) => [
                'id'             => $t->id,
                'trip_number'    => $t->trip_number,
                'origin'         => $t->origin_address,
                'destination'    => $t->destination_address,
                'status'         => $t->status,
                'status_name'    => $t->status_name ?? ucfirst(str_replace('_', ' ', $t->status)),
                'date'           => $t->scheduled_start_date?->format('M d, Y'),
                'vehicle'        => $t->vehicle?->company_unit_number ?? $t->vehicle?->vin ?? null,
            ]);

        // --- HOS violations ---
        $violationQuery = HosViolation::where('user_driver_detail_id', $driverId);

        $totalViolations       = (clone $violationQuery)->count();
        $unacknowledgedViolations = (clone $violationQuery)->where('acknowledged', false)->count();

        $recentViolations = (clone $violationQuery)
            ->orderByDesc('violation_date')
            ->take(5)
            ->get()
            ->map(fn($v) => [
                'id'             => $v->id,
                'type'           => $v->violation_type,
                'severity'       => $v->violation_severity,
                'date'           => $v->violation_date?->format('M d, Y'),
                'acknowledged'   => (bool) $v->acknowledged,
                'penalty_type'   => $v->penalty_type,
            ]);

        // --- License status ---
        $licenseStats = DriverLicense::where('user_driver_detail_id', $driverId)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN expiration_date < ? THEN 1 ELSE 0 END) as expired,
                SUM(CASE WHEN expiration_date >= ? AND expiration_date <= ? THEN 1 ELSE 0 END) as expiring_soon
            ', [$now, $now, $expiringThreshold])
            ->first();

        $licTotal    = $licenseStats->total ?? 0;
        $licExpired  = $licenseStats->expired ?? 0;
        $licExpiring = $licenseStats->expiring_soon ?? 0;

        $licenseData = [
            'total'         => $licTotal,
            'valid'         => max(0, $licTotal - $licExpired - $licExpiring),
            'expiring_soon' => $licExpiring,
            'expired'       => $licExpired,
        ];

        // --- Medical status ---
        $medStats = DriverMedicalQualification::where('user_driver_detail_id', $driverId)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN medical_card_expiration_date < ? THEN 1 ELSE 0 END) as expired,
                SUM(CASE WHEN medical_card_expiration_date >= ? AND medical_card_expiration_date <= ? THEN 1 ELSE 0 END) as expiring_soon,
                SUM(CASE WHEN medical_card_expiration_date > ? THEN 1 ELSE 0 END) as active
            ', [$now, $now, $expiringThreshold, $expiringThreshold])
            ->first();

        $medicalData = [
            'total'         => $medStats->total ?? 0,
            'active'        => $medStats->active ?? 0,
            'expiring_soon' => $medStats->expiring_soon ?? 0,
            'expired'       => $medStats->expired ?? 0,
        ];

        // --- Assigned vehicle ---
        $assignedVehicle = null;
        if ($driver->assigned_vehicle_id) {
            $driver->loadMissing('assignedVehicle');
            $v = $driver->assignedVehicle;
            if ($v) {
                $assignedVehicle = [
                    'id'          => $v->id,
                    'unit_number' => $v->company_unit_number ?? $v->vin,
                    'make'        => $v->make ?? null,
                    'model'       => $v->model ?? null,
                    'year'        => $v->year ?? null,
                    'license_plate' => $v->registration_number ?? null,
                ];
            }
        }

        // --- Carrier info ---
        $driver->loadMissing('carrier');
        $carrier = $driver->carrier;

        // --- Alerts ---
        $alerts = [];

        if ($unacknowledgedViolations > 0) {
            $alerts[] = [
                'type'    => 'danger',
                'icon'    => 'AlertOctagon',
                'title'   => 'HOS Violations Pending',
                'message' => "You have {$unacknowledgedViolations} unacknowledged HOS violation(s). Please review them.",
            ];
        }
        if ($licenseData['expired'] > 0) {
            $alerts[] = [
                'type'    => 'danger',
                'icon'    => 'XCircle',
                'title'   => 'Expired License',
                'message' => "You have {$licenseData['expired']} expired license(s). Update immediately.",
            ];
        }
        if ($licenseData['expiring_soon'] > 0) {
            $alerts[] = [
                'type'    => 'warning',
                'icon'    => 'Clock',
                'title'   => 'License Expiring Soon',
                'message' => "{$licenseData['expiring_soon']} license(s) expire within 30 days.",
            ];
        }
        if ($medicalData['expired'] > 0) {
            $alerts[] = [
                'type'    => 'danger',
                'icon'    => 'AlertCircle',
                'title'   => 'Expired Medical Card',
                'message' => "Your medical certification has expired. Update immediately.",
            ];
        }
        if ($medicalData['expiring_soon'] > 0) {
            $alerts[] = [
                'type'    => 'warning',
                'icon'    => 'Clock',
                'title'   => 'Medical Card Expiring Soon',
                'message' => "{$medicalData['expiring_soon']} medical certification(s) expire within 30 days.",
            ];
        }

        return Inertia::render('driver/Dashboard', [
            'driver' => [
                'id'          => $driver->id,
                'full_name'   => $driver->full_name,
                'status'      => $driver->status,
                'status_name' => $driver->status_name,
                'hire_date'   => $driver->hire_date?->format('M d, Y'),
                'hos_cycle'   => $driver->getEffectiveHosCycleType(),
                'photo_url'   => $driver->profile_photo_url,
            ],
            'carrier' => $carrier ? [
                'id'         => $carrier->id,
                'name'       => $carrier->name,
                'dot_number' => $carrier->dot_number,
            ] : null,
            'tripStats' => [
                'total'     => $totalTrips,
                'active'    => $activeTrips,
                'completed' => $completedTrips,
                'cancelled' => $cancelledTrips,
            ],
            'violationStats' => [
                'total'          => $totalViolations,
                'unacknowledged' => $unacknowledgedViolations,
            ],
            'licenseStats'   => $licenseData,
            'medicalStats'   => $medicalData,
            'assignedVehicle' => $assignedVehicle,
            'recentTrips'    => $recentTrips,
            'recentViolations' => $recentViolations,
            'alerts'         => $alerts,
        ]);
    }

    public function profile(): Response
    {
        $driver = $this->loadDriverWithRelationships();
        $vehicles = $this->resolveVehicles($driver);
        $documentCategories = $this->buildDocumentCategories($driver);
        $stats = $this->calculateProfileStats($driver, $vehicles, $documentCategories);

        return Inertia::render('driver/profile/Show', [
            'driver' => [
                'id' => $driver->id,
                'first_name' => $driver->user?->name,
                'middle_name' => $driver->middle_name,
                'last_name' => $driver->last_name,
                'full_name' => $driver->full_name,
                'email' => $driver->user?->email,
                'phone' => $driver->phone,
                'date_of_birth' => $driver->date_of_birth?->format('M d, Y'),
                'created_at' => optional($driver->created_at)->format('M d, Y'),
                'hire_date' => $driver->hire_date?->format('M d, Y'),
                'status_name' => $driver->status_name,
                'effective_status' => $driver->getEffectiveStatus(),
                'photo_url' => $driver->profile_photo_url,
                'carrier' => $driver->carrier ? [
                    'id' => $driver->carrier->id,
                    'name' => $driver->carrier->name,
                    'dot_number' => $driver->carrier->dot_number,
                    'mc_number' => $driver->carrier->mc_number,
                    'address' => $driver->carrier->address,
                ] : null,
                'application' => $driver->application ? [
                    'status' => $driver->application->status,
                    'status_name' => ucfirst($driver->application->status),
                    'submitted_date' => optional($driver->application->created_at)->format('M d, Y'),
                ] : null,
            ],
            'stats' => $stats,
            'licenses' => $driver->licenses->map(function ($license) {
                $isExpired = $license->expiration_date?->isPast() ?? false;
                $isExpiringSoon = $license->expiration_date && !$isExpired && $license->expiration_date->diffInDays(now()) <= 30;

                return [
                    'id' => $license->id,
                    'number' => $license->license_number,
                    'state' => $license->state_of_issue,
                    'class' => $license->license_class,
                    'expiration_date' => $license->expiration_date?->format('M d, Y'),
                    'is_cdl' => (bool) $license->is_cdl,
                    'is_primary' => (bool) $license->is_primary,
                    'is_expired' => $isExpired,
                    'is_expiring_soon' => $isExpiringSoon,
                    'endorsements' => $license->endorsements->map(fn ($endorsement) => $endorsement->code ?? $endorsement->name)->values(),
                    'front_url' => $license->getFirstMediaUrl('license_front') ?: null,
                    'back_url' => $license->getFirstMediaUrl('license_back') ?: null,
                ];
            })->values(),
            'medical' => $this->serializeMedical($driver),
            'vehicles' => $vehicles->map(function ($vehicle) {
                return [
                    'id' => $vehicle->id,
                    'year' => $vehicle->year,
                    'make' => $vehicle->make,
                    'model' => $vehicle->model,
                    'vin' => $vehicle->vin,
                    'type' => $vehicle->type,
                    'status' => $vehicle->status,
                    'status_name' => ucfirst(str_replace('_', ' ', $vehicle->status)),
                    'unit_number' => $vehicle->company_unit_number,
                ];
            })->values(),
            'trainings' => [
                'schools' => $driver->trainingSchools->map(fn ($school) => [
                    'id' => $school->id,
                    'name' => $school->school_name,
                    'city' => $school->city,
                    'state' => $school->state,
                    'graduated' => (bool) $school->graduated,
                    'date_start' => $school->date_start?->format('M d, Y'),
                    'date_end' => $school->date_end?->format('M d, Y'),
                ])->values(),
                'courses' => $driver->courses->map(fn ($course) => [
                    'id' => $course->id,
                    'organization_name' => $course->organization_name,
                    'city' => $course->city,
                    'state' => $course->state,
                    'certification_date' => $course->certification_date?->format('M d, Y'),
                    'years_experience' => $course->years_experience,
                ])->values(),
                'assigned' => $driver->driverTrainings->map(fn ($training) => [
                    'id' => $training->id,
                    'name' => $training->training?->title ?? 'Training',
                    'status' => $training->status,
                    'assigned_date' => $training->assigned_date?->format('M d, Y'),
                    'due_date' => $training->due_date?->format('M d, Y'),
                    'completed_date' => $training->completed_date?->format('M d, Y'),
                ])->values(),
            ],
            'testings' => $driver->testings->map(fn ($testing) => [
                'id' => $testing->id,
                'test_type' => $testing->test_type,
                'test_result' => $testing->test_result,
                'status' => $testing->status,
                'test_date' => $testing->test_date?->format('M d, Y'),
                'location' => $testing->location,
                'administered_by' => $testing->administered_by,
                'next_test_due' => $testing->next_test_due?->format('M d, Y'),
                'categories' => collect($testing->testCategories)->pluck('label')->values(),
                'pdf_url' => $testing->getFirstMediaUrl('drug_test_pdf') ?: null,
            ])->values(),
            'inspections' => $driver->inspections->map(fn ($inspection) => [
                'id' => $inspection->id,
                'inspection_type' => $inspection->inspection_type,
                'status' => $inspection->status,
                'inspection_date' => $inspection->inspection_date?->format('M d, Y'),
                'inspector_name' => $inspection->inspector_name,
                'location' => $inspection->location,
                'inspection_level' => $inspection->inspection_level,
                'defects_found' => $inspection->defects_found,
                'corrective_actions' => $inspection->corrective_actions,
                'is_safe_to_operate' => (bool) $inspection->is_vehicle_safe_to_operate,
                'documents' => $inspection->getMedia('inspection_documents')->map(fn (Media $media) => $this->mapMedia($media))->values(),
            ])->values(),
            'documents' => $documentCategories->values(),
        ]);
    }

    public function downloadDocuments(Request $request)
    {
        $user = auth()->user();
        $key = 'driver-profile-download:' . $user->id;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return back()->with('error', 'Too many download attempts. Please try again in ' . ceil($seconds / 60) . ' minutes.');
        }

        RateLimiter::hit($key, 3600);

        $driver = $this->loadDriverWithRelationships();
        $zipFileName = 'driver_documents_' . $driver->id . '_' . now()->format('Y-m-d_His') . '.zip';
        $zipDirectory = storage_path('app/temp');
        $zipPath = $zipDirectory . DIRECTORY_SEPARATOR . $zipFileName;

        if (!is_dir($zipDirectory)) {
            mkdir($zipDirectory, 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Could not create ZIP file.');
        }

        $filesAdded = 0;

        foreach ($driver->licenses as $index => $license) {
            $licenseFolder = 'Licenses/License_' . ($index + 1);
            foreach (['license_front', 'license_back', 'license_documents'] as $collection) {
                foreach ($license->getMedia($collection) as $media) {
                    if (file_exists($media->getPath())) {
                        $zip->addFile($media->getPath(), "{$licenseFolder}/{$collection}/" . $this->sanitizeFileName($media->file_name));
                        $filesAdded++;
                    }
                }
            }
        }

        if ($driver->medicalQualification) {
            foreach (['medical_certificate', 'test_results', 'additional_documents', 'medical_documents', 'medical_card', 'social_security_card'] as $collection) {
                foreach ($driver->medicalQualification->getMedia($collection) as $media) {
                    if (file_exists($media->getPath())) {
                        $zip->addFile($media->getPath(), "Medical/{$collection}/" . $this->sanitizeFileName($media->file_name));
                        $filesAdded++;
                    }
                }
            }
        }

        foreach ($driver->trainingSchools as $school) {
            foreach ($school->getMedia('school_certificates') as $media) {
                if (file_exists($media->getPath())) {
                    $zip->addFile($media->getPath(), 'Training/Schools/' . $this->sanitizeFileName($media->file_name));
                    $filesAdded++;
                }
            }
        }

        foreach ($driver->courses as $course) {
            foreach ($course->getMedia('course_certificates') as $media) {
                if (file_exists($media->getPath())) {
                    $zip->addFile($media->getPath(), 'Training/Courses/' . $this->sanitizeFileName($media->file_name));
                    $filesAdded++;
                }
            }
        }

        foreach ($driver->testings as $index => $testing) {
            $testingFolder = 'Testing/Test_' . ($index + 1);
            foreach (['drug_test_pdf', 'test_results', 'test_certificates', 'document_attachments'] as $collection) {
                foreach ($testing->getMedia($collection) as $media) {
                    if (file_exists($media->getPath())) {
                        $zip->addFile($media->getPath(), "{$testingFolder}/{$collection}/" . $this->sanitizeFileName($media->file_name));
                        $filesAdded++;
                    }
                }
            }
        }

        foreach ($driver->inspections as $inspection) {
            foreach ($inspection->getMedia('inspection_documents') as $media) {
                if (file_exists($media->getPath())) {
                    $zip->addFile($media->getPath(), 'Inspections/' . $this->sanitizeFileName($media->file_name));
                    $filesAdded++;
                }
            }
        }

        foreach ($driver->accidents as $accident) {
            foreach ($accident->getMedia('accident-images') as $media) {
                if (file_exists($media->getPath())) {
                    $zip->addFile($media->getPath(), 'Accidents/' . $this->sanitizeFileName($media->file_name));
                    $filesAdded++;
                }
            }
        }

        foreach ($driver->trafficConvictions as $conviction) {
            foreach ($conviction->getMedia('traffic_images') as $media) {
                if (file_exists($media->getPath())) {
                    $zip->addFile($media->getPath(), 'Traffic/' . $this->sanitizeFileName($media->file_name));
                    $filesAdded++;
                }
            }
        }

        foreach (['driving_records', 'criminal_records', 'medical_records', 'clearing_house', 'other', 'miscellaneous', 'w9_documents', 'dot_policy_documents'] as $collection) {
            foreach ($driver->getMedia($collection) as $media) {
                if (file_exists($media->getPath())) {
                    $zip->addFile($media->getPath(), 'Driver/' . $collection . '/' . $this->sanitizeFileName($media->file_name));
                    $filesAdded++;
                }
            }
        }

        if ($driver->application) {
            foreach (['application_pdf', 'signed_application'] as $collection) {
                foreach ($driver->application->getMedia($collection) as $media) {
                    if (file_exists($media->getPath())) {
                        $zip->addFile($media->getPath(), 'Application/' . $collection . '/' . $this->sanitizeFileName($media->file_name));
                        $filesAdded++;
                    }
                }
            }
        }

        foreach (['signed_application', 'application_pdf', 'lease_agreement', 'contract_documents', 'application_forms', 'individual_forms'] as $collection) {
            foreach ($driver->getMedia($collection) as $media) {
                if (file_exists($media->getPath())) {
                    $zip->addFile($media->getPath(), 'Application/' . $collection . '/' . $this->sanitizeFileName($media->file_name));
                    $filesAdded++;
                }
            }
        }

        $vehicleVerificationsPath = "driver/{$driver->id}/vehicle_verifications";
        if (Storage::disk('public')->exists($vehicleVerificationsPath)) {
            foreach (Storage::disk('public')->files($vehicleVerificationsPath) as $filePath) {
                $fullPath = Storage::disk('public')->path($filePath);
                if (file_exists($fullPath)) {
                    $zip->addFile($fullPath, 'Vehicle_Verifications/' . $this->sanitizeFileName(basename($filePath)));
                    $filesAdded++;
                }
            }
        }

        $driverApplicationsPath = "driver/{$driver->id}/driver_applications";
        if (Storage::disk('public')->exists($driverApplicationsPath)) {
            foreach (Storage::disk('public')->files($driverApplicationsPath) as $filePath) {
                $fullPath = Storage::disk('public')->path($filePath);
                if (file_exists($fullPath)) {
                    $zip->addFile($fullPath, 'Application/storage/' . $this->sanitizeFileName(basename($filePath)));
                    $filesAdded++;
                }
            }
        }

        $zip->close();

        if ($filesAdded === 0) {
            @unlink($zipPath);

            return back()->with('error', 'No documents available to download.');
        }

        return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
    }

    private function loadDriverWithRelationships(): UserDriverDetail
    {
        $driver = auth()->user()?->driverDetails;

        abort_unless($driver, 403, 'No driver profile associated with this account.');

        return $driver->load([
            'user:id,name,email',
            'carrier:id,name,dot_number,mc_number,address',
            'licenses' => fn ($query) => $query->with('endorsements')->latest('expiration_date'),
            'medicalQualification',
            'trainingSchools' => fn ($query) => $query->latest('date_end'),
            'courses' => fn ($query) => $query->latest('certification_date'),
            'testings' => fn ($query) => $query->latest('test_date'),
            'inspections' => fn ($query) => $query->latest('inspection_date'),
            'vehicles',
            'assignedVehicle',
            'activeVehicleAssignment.vehicle',
            'driverTrainings.training',
            'application',
            'accidents',
            'trafficConvictions',
            'employmentCompanies',
        ]);
    }

    private function resolveVehicles(UserDriverDetail $driver): Collection
    {
        if ($driver->vehicles && $driver->vehicles->count() > 0) {
            return $driver->vehicles;
        }

        if ($driver->assignedVehicle) {
            return collect([$driver->assignedVehicle]);
        }

        if ($driver->activeVehicleAssignment?->vehicle) {
            return collect([$driver->activeVehicleAssignment->vehicle]);
        }

        return collect();
    }

    private function calculateProfileStats(UserDriverDetail $driver, Collection $vehicles, Collection $documentCategories): array
    {
        $medical = $driver->medicalQualification;
        $medicalStatus = 'Not Set';

        if ($medical?->medical_card_expiration_date) {
            if ($medical->medical_card_expiration_date->isPast()) {
                $medicalStatus = 'Expired';
            } elseif ($medical->medical_card_expiration_date->diffInDays(now()) <= 30) {
                $medicalStatus = 'Expiring Soon';
            } else {
                $medicalStatus = 'Valid';
            }
        }

        return [
            'total_documents' => $documentCategories->sum(fn (array $category) => count($category['documents'])),
            'licenses_count' => $driver->licenses->count(),
            'vehicles_count' => $vehicles->count(),
            'trainings_count' => $driver->trainingSchools->count() + $driver->courses->count() + $driver->driverTrainings->count(),
            'testing_count' => $driver->testings->count(),
            'medical_status' => $medicalStatus,
        ];
    }

    private function serializeMedical(UserDriverDetail $driver): ?array
    {
        if (!$driver->medicalQualification) {
            return null;
        }

        $medical = $driver->medicalQualification;
        $expirationDate = $medical->medical_card_expiration_date;
        $isExpired = $expirationDate?->isPast() ?? false;
        $isExpiringSoon = $expirationDate && !$isExpired && $expirationDate->diffInDays(now()) <= 30;

        $documents = collect([
            ...$medical->getMedia('medical_certificate'),
            ...$medical->getMedia('test_results'),
            ...$medical->getMedia('additional_documents'),
            ...$medical->getMedia('medical_documents'),
            ...$medical->getMedia('medical_card'),
            ...$medical->getMedia('social_security_card'),
        ])->map(fn (Media $media) => $this->mapMedia($media))->values();

        return [
            'expiration_date' => $expirationDate?->format('M d, Y'),
            'examiner_name' => $medical->medical_examiner_name,
            'registry_number' => $medical->medical_examiner_registry_number,
            'status' => $isExpired ? 'expired' : ($isExpiringSoon ? 'expiring_soon' : ($expirationDate ? 'valid' : 'not_set')),
            'documents' => $documents,
        ];
    }

    private function buildDocumentCategories(UserDriverDetail $driver): Collection
    {
        $categories = collect();

        $licenseDocuments = collect();
        foreach ($driver->licenses as $license) {
            $licenseDocuments = $licenseDocuments
                ->merge($license->getMedia('license_front'))
                ->merge($license->getMedia('license_back'))
                ->merge($license->getMedia('license_documents'));
        }
        $this->pushCategory($categories, 'Licenses', $licenseDocuments);

        if ($driver->medicalQualification) {
            $medicalDocuments = collect();
            foreach (['medical_certificate', 'test_results', 'additional_documents', 'medical_documents', 'medical_card', 'social_security_card'] as $collection) {
                $medicalDocuments = $medicalDocuments->merge($driver->medicalQualification->getMedia($collection));
            }
            $this->pushCategory($categories, 'Medical', $medicalDocuments);
        }

        $trainingDocuments = collect();
        foreach ($driver->trainingSchools as $school) {
            $trainingDocuments = $trainingDocuments->merge($school->getMedia('school_certificates'));
        }
        foreach ($driver->courses as $course) {
            $trainingDocuments = $trainingDocuments->merge($course->getMedia('course_certificates'));
        }
        $this->pushCategory($categories, 'Training', $trainingDocuments);

        $testingDocuments = collect();
        foreach ($driver->testings as $testing) {
            foreach (['drug_test_pdf', 'test_results', 'test_certificates', 'document_attachments'] as $collection) {
                $testingDocuments = $testingDocuments->merge($testing->getMedia($collection));
            }
        }
        $this->pushCategory($categories, 'Testing', $testingDocuments);

        $inspectionDocuments = collect();
        foreach ($driver->inspections as $inspection) {
            $inspectionDocuments = $inspectionDocuments->merge($inspection->getMedia('inspection_documents'));
        }
        $this->pushCategory($categories, 'Inspections', $inspectionDocuments);

        $accidentDocuments = collect();
        foreach ($driver->accidents as $accident) {
            $accidentDocuments = $accidentDocuments->merge($accident->getMedia('accident-images'));
        }
        $this->pushCategory($categories, 'Accidents', $accidentDocuments);

        $trafficDocuments = collect();
        foreach ($driver->trafficConvictions as $conviction) {
            $trafficDocuments = $trafficDocuments->merge($conviction->getMedia('traffic_images'));
        }
        $this->pushCategory($categories, 'Traffic', $trafficDocuments);

        $recordsDocuments = collect();
        foreach (['driving_records', 'criminal_records', 'medical_records', 'clearing_house', 'dot_policy_documents'] as $collection) {
            $recordsDocuments = $recordsDocuments->merge($driver->getMedia($collection));
        }
        $this->pushCategory($categories, 'Records', $recordsDocuments);

        $applicationDocuments = collect();
        if ($driver->application) {
            $applicationDocuments = $applicationDocuments
                ->merge($driver->application->getMedia('application_pdf'))
                ->merge($driver->application->getMedia('signed_application'));
        }
        foreach (['signed_application', 'application_pdf', 'lease_agreement', 'contract_documents', 'application_forms', 'individual_forms'] as $collection) {
            $applicationDocuments = $applicationDocuments->merge($driver->getMedia($collection));
        }
        $this->pushCategory($categories, 'Application', $applicationDocuments);

        $employmentDocuments = collect();
        foreach ($driver->employmentCompanies as $company) {
            $employmentDocuments = $employmentDocuments->merge($company->getMedia('employment_verification_documents'));
        }
        $this->pushCategory($categories, 'Employment', $employmentDocuments);

        $otherDocuments = collect();
        foreach (['other', 'miscellaneous', 'w9_documents'] as $collection) {
            $otherDocuments = $otherDocuments->merge($driver->getMedia($collection));
        }
        $this->pushCategory($categories, 'Other', $otherDocuments);

        return $categories;
    }

    private function pushCategory(Collection $categories, string $label, Collection $media): void
    {
        if ($media->isEmpty()) {
            return;
        }

        $categories->push([
            'label' => $label,
            'count' => $media->count(),
            'documents' => $media->map(fn (Media $item) => $this->mapMedia($item))->values()->all(),
        ]);
    }

    private function mapMedia(Media $media): array
    {
        return [
            'id' => $media->id,
            'name' => $media->file_name,
            'url' => $media->getUrl(),
            'size' => $media->human_readable_size,
            'mime_type' => $media->mime_type,
            'created_at' => optional($media->created_at)->format('M d, Y'),
        ];
    }

    private function sanitizeFileName(string $fileName): string
    {
        $fileName = basename($fileName);
        $fileName = preg_replace('/[^\w\-\.\s]/', '_', $fileName);

        return $fileName ?: 'document_' . uniqid();
    }
}
