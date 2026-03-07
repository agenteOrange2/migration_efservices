<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\Admin\Vehicle\VehicleMaintenance;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\EmergencyRepair;
use Carbon\Carbon;

class DriverDashboardController extends Controller
{
    /**
     * Create a new controller instance.
     * Verify user has driver role and driver detail.
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            
            if (!$user || !$user->driverDetail) {
                abort(403, 'Access denied. Driver profile not found.');
            }
            
            return $next($request);
        });
    }

    /**
     * Display the driver dashboard (metrics, quick actions, summary).
     */
    public function index()
    {
        $driver = $this->loadDriverWithRelationships();
        $stats = $this->calculateStatistics($driver);
        $maintenanceAlerts = $this->loadMaintenanceAlerts($driver);
        $alerts = $this->identifyAlerts($driver, $maintenanceAlerts);
        $documentProgress = $this->calculateDocumentProgress($driver);

        return view('driver.dashboard', compact('driver', 'stats', 'alerts', 'documentProgress'));
    }

    /**
     * Display the driver profile page (detailed information with tabs).
     */
    public function profile()
    {
        $driver = $this->loadDriverWithRelationships();
        $stats = $this->calculateStatistics($driver);

        return view('driver.profile.profile', compact('driver', 'stats'));
    }

    /**
     * Load driver with all necessary relationships.
     */
    private function loadDriverWithRelationships()
    {
        return Auth::user()->driverDetail->load([
            'user',
            'carrier',
            'licenses' => function($query) {
                $query->with('endorsements')->orderBy('expiration_date', 'desc');
            },
            'medicalQualification',
            'trainingSchools' => function($query) {
                $query->orderBy('date_end', 'desc');
            },
            'courses' => function($query) {
                $query->orderBy('certification_date', 'desc');
            },
            'testings' => function($query) {
                $query->orderBy('test_date', 'desc');
            },
            'inspections' => function($query) {
                $query->orderBy('inspection_date', 'desc');
            },
            'vehicles',
            'assignedVehicle',
            'activeVehicleAssignment.vehicle',
            'driverTrainings' => function($query) {
                $query->orderBy('completed_date', 'desc');
            },
            'application',
            'accidents',
            'trafficConvictions',
            'employmentCompanies'
        ]);
    }

    /**
     * Calculate statistics for the driver dashboard.
     *
     * @param \App\Models\UserDriverDetail $driver
     * @return array
     */
    private function calculateStatistics($driver): array
    {
        $totalDocuments = 0;

        // 1. LICENSES - Count all license documents
        foreach ($driver->licenses as $license) {
            $totalDocuments += $license->getMedia('license_front')->count();
            $totalDocuments += $license->getMedia('license_back')->count();
            $totalDocuments += $license->getMedia('license_documents')->count();
        }

        // 2. MEDICAL DOCUMENTS - Count all medical documents
        if ($driver->medicalQualification) {
            $medicalCollections = ['medical_certificate', 'test_results', 'additional_documents', 'medical_documents', 'medical_card'];
            foreach ($medicalCollections as $collection) {
                $totalDocuments += $driver->medicalQualification->getMedia($collection)->count();
            }
        }

        // 3. TRAINING SCHOOLS - Count school certificates
        foreach ($driver->trainingSchools as $school) {
            $totalDocuments += $school->getMedia('school_certificates')->count();
        }

        // 4. COURSES - Count course certificates
        foreach ($driver->courses as $course) {
            $totalDocuments += $course->getMedia('course_certificates')->count();
        }

        // 5. ACCIDENTS - Count accident images
        foreach ($driver->accidents as $accident) {
            $totalDocuments += $accident->getMedia('accident-images')->count();
        }

        // 6. TRAFFIC VIOLATIONS - Count traffic images
        foreach ($driver->trafficConvictions as $conviction) {
            $totalDocuments += $conviction->getMedia('traffic_images')->count();
        }

        // 7. TESTING - Count all testing documents
        if ($driver->testings) {
            foreach ($driver->testings as $testing) {
                $totalDocuments += $testing->getMedia('drug_test_pdf')->count();
                $totalDocuments += $testing->getMedia('test_results')->count();
                $totalDocuments += $testing->getMedia('test_certificates')->count();
            }
        }

        // 8. INSPECTIONS - Count inspection documents
        if ($driver->inspections) {
            foreach ($driver->inspections as $inspection) {
                $totalDocuments += $inspection->getMedia('inspection_documents')->count();
                $totalDocuments += $inspection->getMedia()->count();
            }
        }

        // 9. VEHICLE VERIFICATIONS - Count PDF files from storage
        $vehicleVerificationsPath = "driver/{$driver->id}/vehicle_verifications";
        if (\Storage::disk('public')->exists($vehicleVerificationsPath)) {
            $vehicleFiles = \Storage::disk('public')->files($vehicleVerificationsPath);
            foreach ($vehicleFiles as $filePath) {
                $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
                if (strtolower($fileExtension) === 'pdf') {
                    $totalDocuments++;
                }
            }
        }

        // 10. RECORDS - Count all record types
        $recordCollections = ['driving_records', 'criminal_records', 'medical_records', 'clearing_house', 'records', 'general', 'documents'];
        foreach ($recordCollections as $collection) {
            $totalDocuments += $driver->getMedia($collection)->count();
        }

        // 11. EMPLOYMENT VERIFICATION - Count employment documents
        if ($driver->employmentCompanies && $driver->employmentCompanies->count() > 0) {
            foreach ($driver->employmentCompanies as $empCompany) {
                $totalDocuments += $empCompany->getMedia('employment_verification_documents')->count();

                // Count verification tokens with documents
                $tokens = \App\Models\Admin\Driver\EmploymentVerificationToken::where('employment_company_id', $empCompany->id)
                    ->whereNotNull('verified_at')
                    ->where('document_path', '!=', null)
                    ->get();
                foreach ($tokens as $token) {
                    if (\Storage::disk('public')->exists($token->document_path)) {
                        $totalDocuments++;
                    }
                }
            }
        }

        // 12. APPLICATION FORMS - Count all application documents
        if ($driver->application) {
            $totalDocuments += $driver->application->getMedia('application_pdf')->count();
            $totalDocuments += $driver->application->getMedia('signed_application')->count();
        }

        // Individual application media
        $individualApplicationCollections = ['signed_application', 'application_pdf', 'lease_agreement', 'contract_documents', 'application_forms', 'individual_forms'];
        foreach ($individualApplicationCollections as $collection) {
            $totalDocuments += $driver->getMedia($collection)->count();
        }

        // Application files from storage
        $driverApplicationsPath = "driver/{$driver->id}/driver_applications";
        if (\Storage::disk('public')->exists($driverApplicationsPath)) {
            $individualFiles = \Storage::disk('public')->files($driverApplicationsPath);
            foreach ($individualFiles as $filePath) {
                $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
                if (strtolower($fileExtension) === 'pdf') {
                    $totalDocuments++;
                }
            }
        }

        // OTHER DOCUMENTS - Count other/miscellaneous documents
        $otherCollections = ['other', 'miscellaneous'];
        foreach ($otherCollections as $collection) {
            $totalDocuments += $driver->getMedia($collection)->count();
        }

        // Calculate records uploaded count
        $recordsUploaded = $driver->getMedia('driving_records')->count() +
            $driver->getMedia('medical_records')->count() +
            $driver->getMedia('criminal_records')->count() +
            $driver->getMedia('clearing_house')->count();

        // Calculate medical status - use medical_card_expiration_date as primary, fallback to dot_medical_expiry_date
        $medicalStatus = 'Expired';
        $medicalExpiryDate = null;
        if ($driver->medicalQualification) {
            $dateField = $driver->medicalQualification->medical_card_expiration_date 
                ?? $driver->medicalQualification->dot_medical_expiry_date;
            
            if ($dateField) {
                $expiryDate = Carbon::parse($dateField);
                $medicalExpiryDate = $expiryDate;
                
                if ($expiryDate->isFuture()) {
                    // Check if expiring within 30 days
                    if ($expiryDate->diffInDays(Carbon::now()) <= 30) {
                        $medicalStatus = 'Expiring Soon';
                    } else {
                        $medicalStatus = 'Valid';
                    }
                }
            }
        }

        // Calculate testing count and status
        $testingCount = $driver->testings ? $driver->testings->count() : 0;
        $testingStatus = $testingCount > 0 ? 'Tests Completed' : 'No Tests';

        // Calculate associated vehicles count
        $associatedVehiclesCount = 0;
        if ($driver->vehicles) {
            $associatedVehiclesCount = $driver->vehicles->count();
        } elseif (method_exists($driver, 'assignedVehicle') && $driver->assignedVehicle) {
            $associatedVehiclesCount = 1;
        } elseif (method_exists($driver, 'activeVehicleAssignment') && $driver->activeVehicleAssignment) {
            $associatedVehiclesCount = 1;
        }
        $vehiclesStatus = $associatedVehiclesCount > 0 ? 'Vehicles Assigned' : 'No Vehicles';

        // Calculate trainings count
        $trainingsCount = 0;
        $trainingsCount += $driver->trainingSchools ? $driver->trainingSchools->count() : 0;
        $trainingsCount += $driver->courses ? $driver->courses->count() : 0;
        $trainingsCount += $driver->driverTrainings ? $driver->driverTrainings->count() : 0;

        // Calculate maintenance count for assigned vehicle
        $maintenanceCount = 0;
        $maintenanceStatus = 'No Vehicle';
        $vehicleAssignment = $driver->activeVehicleAssignment;
        if ($vehicleAssignment && $vehicleAssignment->vehicle) {
            $vehicle = $vehicleAssignment->vehicle;
            $maintenanceCount = VehicleMaintenance::where('vehicle_id', $vehicle->id)
                ->where('status', false)
                ->count();
            $overdueCount = VehicleMaintenance::where('vehicle_id', $vehicle->id)
                ->where('status', false)
                ->where('next_service_date', '<', Carbon::now())
                ->count();
            
            if ($overdueCount > 0) {
                $maintenanceStatus = 'Overdue';
            } elseif ($maintenanceCount > 0) {
                $maintenanceStatus = 'Pending';
            } else {
                $maintenanceStatus = 'Up to Date';
            }
        } elseif ($driver->assigned_vehicle_id) {
            $vehicle = Vehicle::find($driver->assigned_vehicle_id);
            if ($vehicle) {
                $maintenanceCount = VehicleMaintenance::where('vehicle_id', $vehicle->id)
                    ->where('status', false)
                    ->count();
                $overdueCount = VehicleMaintenance::where('vehicle_id', $vehicle->id)
                    ->where('status', false)
                    ->where('next_service_date', '<', Carbon::now())
                    ->count();
                
                if ($overdueCount > 0) {
                    $maintenanceStatus = 'Overdue';
                } elseif ($maintenanceCount > 0) {
                    $maintenanceStatus = 'Pending';
                } else {
                    $maintenanceStatus = 'Up to Date';
                }
            }
        }

        // Calculate emergency repairs stats for assigned vehicle
        $emergencyRepairsCount = 0;
        $emergencyRepairsPending = 0;
        $emergencyRepairsInProgress = 0;
        $emergencyRepairsCompleted = 0;
        $emergencyRepairsTotalCost = 0;
        $emergencyRepairsStatus = 'No Vehicle';
        
        if (isset($vehicle) && $vehicle) {
            $emergencyRepairsCount = EmergencyRepair::where('vehicle_id', $vehicle->id)->count();
            $emergencyRepairsPending = EmergencyRepair::where('vehicle_id', $vehicle->id)
                ->where('status', 'pending')
                ->count();
            $emergencyRepairsInProgress = EmergencyRepair::where('vehicle_id', $vehicle->id)
                ->where('status', 'in_progress')
                ->count();
            $emergencyRepairsCompleted = EmergencyRepair::where('vehicle_id', $vehicle->id)
                ->where('status', 'completed')
                ->count();
            $emergencyRepairsTotalCost = EmergencyRepair::where('vehicle_id', $vehicle->id)->sum('cost');
            
            if ($emergencyRepairsPending > 0 || $emergencyRepairsInProgress > 0) {
                $emergencyRepairsStatus = 'Active Repairs';
            } elseif ($emergencyRepairsCompleted > 0) {
                $emergencyRepairsStatus = 'All Completed';
            } else {
                $emergencyRepairsStatus = 'No Repairs';
            }
        }

        return [
            'total_documents' => $totalDocuments,
            'records_uploaded' => $recordsUploaded,
            'medical_status' => $medicalStatus,
            'medical_expiry_date' => $medicalExpiryDate,
            'testing_count' => $testingCount,
            'testing_status' => $testingStatus,
            'vehicles_count' => $associatedVehiclesCount,
            'vehicles_status' => $vehiclesStatus,
            'licenses_count' => $driver->licenses ? $driver->licenses->count() : 0,
            'trainings_count' => $trainingsCount,
            'maintenance_count' => $maintenanceCount,
            'maintenance_status' => $maintenanceStatus,
            'emergency_repairs_count' => $emergencyRepairsCount,
            'emergency_repairs_pending' => $emergencyRepairsPending,
            'emergency_repairs_in_progress' => $emergencyRepairsInProgress,
            'emergency_repairs_completed' => $emergencyRepairsCompleted,
            'emergency_repairs_total_cost' => $emergencyRepairsTotalCost,
            'emergency_repairs_status' => $emergencyRepairsStatus,
        ];
    }

    /**
     * Load maintenance alerts for the driver's assigned vehicle.
     *
     * @param \App\Models\UserDriverDetail $driver
     * @return array
     */
    private function loadMaintenanceAlerts($driver): array
    {
        $maintenanceAlerts = [
            'overdue' => 0,
            'expiring_soon' => 0,
            'items' => []
        ];

        // Get driver's assigned vehicle
        $vehicleAssignment = $driver->activeVehicleAssignment;
        $vehicle = null;
        
        if ($vehicleAssignment && $vehicleAssignment->vehicle) {
            $vehicle = $vehicleAssignment->vehicle;
        } elseif ($driver->assigned_vehicle_id) {
            $vehicle = Vehicle::find($driver->assigned_vehicle_id);
        }

        if (!$vehicle) {
            return $maintenanceAlerts;
        }

        $now = Carbon::now();
        $expiringThreshold = $now->copy()->addDays(30);

        // Get overdue maintenance
        $overdueMaintenance = VehicleMaintenance::where('vehicle_id', $vehicle->id)
            ->where('status', false)
            ->where('next_service_date', '<', $now)
            ->get();

        $maintenanceAlerts['overdue'] = $overdueMaintenance->count();
        
        // Get maintenance expiring soon
        $expiringSoonMaintenance = VehicleMaintenance::where('vehicle_id', $vehicle->id)
            ->where('status', false)
            ->where('next_service_date', '>=', $now)
            ->where('next_service_date', '<=', $expiringThreshold)
            ->get();

        $maintenanceAlerts['expiring_soon'] = $expiringSoonMaintenance->count();
        $maintenanceAlerts['items'] = $overdueMaintenance->merge($expiringSoonMaintenance);

        return $maintenanceAlerts;
    }

    /**
     * Identify alerts for expiring/expired documents and certifications.
     *
     * @param \App\Models\UserDriverDetail $driver
     * @param array $maintenanceAlerts
     * @return array
     */
    private function identifyAlerts($driver, $maintenanceAlerts = []): array
    {
        $alerts = [];
        $now = Carbon::now();

        // Check medical certificate expiration - use medical_card_expiration_date as primary
        $medicalDateField = null;
        if ($driver->medicalQualification) {
            $medicalDateField = $driver->medicalQualification->medical_card_expiration_date 
                ?? $driver->medicalQualification->dot_medical_expiry_date;
        }
        
        if ($medicalDateField) {
            $expiryDate = Carbon::parse($medicalDateField);
            $daysUntilExpiry = $now->diffInDays($expiryDate, false);

            if ($daysUntilExpiry < 0) {
                // Expired
                $alerts[] = [
                    'type' => 'danger',
                    'icon' => 'AlertCircle',
                    'title' => 'Medical Certificate Expired',
                    'message' => 'Your medical certificate has expired. Please renew immediately.',
                    'link' => route('driver.profile') . '#medical',
                    'link_text' => 'View Medical Records'
                ];
            } elseif ($daysUntilExpiry <= 30) {
                // Expiring soon
                $alerts[] = [
                    'type' => 'warning',
                    'icon' => 'AlertTriangle',
                    'title' => 'Medical Certificate Expiring Soon',
                    'message' => "Your medical certificate expires in {$daysUntilExpiry} days.",
                    'link' => route('driver.profile') . '#medical',
                    'link_text' => 'View Medical Records'
                ];
            }
        } else {
            // No medical certificate date set
            $alerts[] = [
                'type' => 'danger',
                'icon' => 'AlertCircle',
                'title' => 'No Medical Certificate',
                'message' => 'You do not have a medical certificate on file. Please upload one.',
                'link' => route('driver.profile') . '#medical',
                'link_text' => 'Upload Medical Certificate'
            ];
        }

        // Check license expirations
        $expiringLicenses = 0;
        $expiredLicenses = 0;
        
        foreach ($driver->licenses as $license) {
            if ($license->expiration_date) {
                $expiryDate = Carbon::parse($license->expiration_date);
                $daysUntilExpiry = $now->diffInDays($expiryDate, false);

                if ($daysUntilExpiry < 0) {
                    $expiredLicenses++;
                } elseif ($daysUntilExpiry <= 30) {
                    $expiringLicenses++;
                }
            }
        }

        if ($expiredLicenses > 0) {
            $alerts[] = [
                'type' => 'danger',
                'icon' => 'AlertCircle',
                'title' => 'Expired Licenses',
                'message' => "You have {$expiredLicenses} expired " . ($expiredLicenses === 1 ? 'license' : 'licenses') . '.',
                'link' => route('driver.profile') . '#licenses',
                'link_text' => 'View Licenses'
            ];
        }

        if ($expiringLicenses > 0) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'AlertTriangle',
                'title' => 'Licenses Expiring Soon',
                'message' => "You have {$expiringLicenses} " . ($expiringLicenses === 1 ? 'license' : 'licenses') . ' expiring within 30 days.',
                'link' => route('driver.profile') . '#licenses',
                'link_text' => 'View Licenses'
            ];
        }

        // Check for overdue maintenance
        if (!empty($maintenanceAlerts) && $maintenanceAlerts['overdue'] > 0) {
            $alerts[] = [
                'type' => 'danger',
                'icon' => 'AlertCircle',
                'title' => 'Overdue Vehicle Maintenance',
                'message' => "Your vehicle has {$maintenanceAlerts['overdue']} overdue maintenance " . ($maintenanceAlerts['overdue'] === 1 ? 'task' : 'tasks') . ' that require immediate attention.',
                'link' => route('driver.maintenance.index'),
                'link_text' => 'View Maintenance'
            ];
        }

        // Check for maintenance expiring soon
        if (!empty($maintenanceAlerts) && $maintenanceAlerts['expiring_soon'] > 0) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'Clock',
                'title' => 'Vehicle Maintenance Due Soon',
                'message' => "Your vehicle has {$maintenanceAlerts['expiring_soon']} maintenance " . ($maintenanceAlerts['expiring_soon'] === 1 ? 'task' : 'tasks') . ' due within 30 days.',
                'link' => route('driver.maintenance.index'),
                'link_text' => 'View Maintenance'
            ];
        }

        // Check for emergency repairs - Get vehicle and check for pending/in_progress repairs
        $vehicleAssignment = $driver->activeVehicleAssignment;
        $vehicle = null;
        
        if ($vehicleAssignment && $vehicleAssignment->vehicle) {
            $vehicle = $vehicleAssignment->vehicle;
        } elseif ($driver->assigned_vehicle_id) {
            $vehicle = Vehicle::find($driver->assigned_vehicle_id);
        }

        if ($vehicle) {
            $pendingRepairs = EmergencyRepair::where('vehicle_id', $vehicle->id)
                ->where('status', 'pending')
                ->count();
            $inProgressRepairs = EmergencyRepair::where('vehicle_id', $vehicle->id)
                ->where('status', 'in_progress')
                ->count();

            if ($pendingRepairs > 0) {
                $alerts[] = [
                    'type' => 'danger',
                    'icon' => 'AlertTriangle',
                    'title' => 'Pending Repairs',
                    'message' => "Your vehicle has {$pendingRepairs} pending " . ($pendingRepairs === 1 ? 'repair' : 'repairs') . ' that require attention.',
                    'link' => route('driver.emergency-repairs.index', ['status' => 'pending']),
                    'link_text' => 'View Repairs'
                ];
            }

            if ($inProgressRepairs > 0) {
                $alerts[] = [
                    'type' => 'warning',
                    'icon' => 'Settings',
                    'title' => 'Repairs In Progress',
                    'message' => "Your vehicle has {$inProgressRepairs} " . ($inProgressRepairs === 1 ? 'repair' : 'repairs') . ' currently in progress.',
                    'link' => route('driver.emergency-repairs.index', ['status' => 'in_progress']),
                    'link_text' => 'View Repairs'
                ];
            }
        }

        return $alerts;
    }

    /**
     * Download all driver documents as a ZIP file.
     * Rate limited to 5 downloads per hour per user.
     */
    public function downloadDocuments()
    {
        $user = Auth::user();
        $key = 'download-documents:' . $user->id;
        
        // Rate limit: 5 downloads per hour
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->with('error', "Too many download attempts. Please try again in " . ceil($seconds / 60) . " minutes.");
        }
        
        RateLimiter::hit($key, 3600); // 1 hour decay
        
        $driver = $user->driverDetail->load([
            'licenses',
            'medicalQualification',
            'trainingSchools',
            'courses',
            'testings',
            'inspections',
            'accidents',
            'trafficConvictions',
            'employmentCompanies',
            'application'
        ]);

        // Create temporary ZIP file
        $zipFileName = 'driver_documents_' . $driver->id . '_' . now()->format('Y-m-d_His') . '.zip';
        $zipPath = storage_path('app/temp/' . $zipFileName);

        // Ensure temp directory exists
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Could not create ZIP file.');
        }

        $filesAdded = 0;

        // 1. LICENSE DOCUMENTS
        foreach ($driver->licenses as $index => $license) {
            $licenseFolder = 'Licenses/License_' . ($index + 1);
            foreach (['license_front', 'license_back', 'license_documents'] as $collection) {
                foreach ($license->getMedia($collection) as $media) {
                    $safeFileName = $this->sanitizeFileName($media->file_name);
                    $zip->addFile($media->getPath(), "{$licenseFolder}/{$collection}/{$safeFileName}");
                    $filesAdded++;
                }
            }
        }

        // 2. MEDICAL DOCUMENTS
        if ($driver->medicalQualification) {
            $medicalCollections = ['medical_certificate', 'test_results', 'additional_documents', 'medical_documents', 'medical_card'];
            foreach ($medicalCollections as $collection) {
                foreach ($driver->medicalQualification->getMedia($collection) as $media) {
                    $safeFileName = $this->sanitizeFileName($media->file_name);
                    $zip->addFile($media->getPath(), "Medical/{$collection}/{$safeFileName}");
                    $filesAdded++;
                }
            }
        }

        // 3. TRAINING SCHOOL DOCUMENTS
        foreach ($driver->trainingSchools as $index => $school) {
            foreach ($school->getMedia('school_certificates') as $media) {
                $safeFileName = $this->sanitizeFileName($media->file_name);
                $zip->addFile($media->getPath(), "Training/Schools/{$safeFileName}");
                $filesAdded++;
            }
        }

        // 4. COURSE DOCUMENTS
        foreach ($driver->courses as $course) {
            foreach ($course->getMedia('course_certificates') as $media) {
                $safeFileName = $this->sanitizeFileName($media->file_name);
                $zip->addFile($media->getPath(), "Training/Courses/{$safeFileName}");
                $filesAdded++;
            }
        }

        // 5. TESTING DOCUMENTS
        if ($driver->testings) {
            foreach ($driver->testings as $index => $testing) {
                $testFolder = 'Testing/Test_' . ($index + 1);
                foreach (['drug_test_pdf', 'test_results', 'test_certificates'] as $collection) {
                    foreach ($testing->getMedia($collection) as $media) {
                        $safeFileName = $this->sanitizeFileName($media->file_name);
                        $zip->addFile($media->getPath(), "{$testFolder}/{$safeFileName}");
                        $filesAdded++;
                    }
                }
            }
        }

        // 6. INSPECTION DOCUMENTS
        if ($driver->inspections) {
            foreach ($driver->inspections as $index => $inspection) {
                foreach ($inspection->getMedia('inspection_documents') as $media) {
                    $safeFileName = $this->sanitizeFileName($media->file_name);
                    $zip->addFile($media->getPath(), "Inspections/{$safeFileName}");
                    $filesAdded++;
                }
            }
        }

        // 7. ACCIDENT DOCUMENTS
        foreach ($driver->accidents as $index => $accident) {
            foreach ($accident->getMedia('accident-images') as $media) {
                $safeFileName = $this->sanitizeFileName($media->file_name);
                $zip->addFile($media->getPath(), "Accidents/{$safeFileName}");
                $filesAdded++;
            }
        }

        // 8. TRAFFIC CONVICTION DOCUMENTS
        foreach ($driver->trafficConvictions as $conviction) {
            foreach ($conviction->getMedia('traffic_images') as $media) {
                $safeFileName = $this->sanitizeFileName($media->file_name);
                $zip->addFile($media->getPath(), "Traffic_Convictions/{$safeFileName}");
                $filesAdded++;
            }
        }

        // 9. RECORDS
        $recordCollections = ['driving_records', 'criminal_records', 'medical_records', 'clearing_house'];
        foreach ($recordCollections as $collection) {
            foreach ($driver->getMedia($collection) as $media) {
                $safeFileName = $this->sanitizeFileName($media->file_name);
                $zip->addFile($media->getPath(), "Records/{$collection}/{$safeFileName}");
                $filesAdded++;
            }
        }

        // 10. APPLICATION DOCUMENTS
        if ($driver->application) {
            foreach (['application_pdf', 'signed_application'] as $collection) {
                foreach ($driver->application->getMedia($collection) as $media) {
                    $safeFileName = $this->sanitizeFileName($media->file_name);
                    $zip->addFile($media->getPath(), "Application/{$safeFileName}");
                    $filesAdded++;
                }
            }
        }

        // Individual application media on driver
        foreach (['signed_application', 'application_pdf', 'lease_agreement', 'contract_documents'] as $collection) {
            foreach ($driver->getMedia($collection) as $media) {
                $safeFileName = $this->sanitizeFileName($media->file_name);
                $zip->addFile($media->getPath(), "Application/{$collection}/{$safeFileName}");
                $filesAdded++;
            }
        }

        // 11. OTHER DOCUMENTS
        foreach (['other', 'miscellaneous'] as $collection) {
            foreach ($driver->getMedia($collection) as $media) {
                $safeFileName = $this->sanitizeFileName($media->file_name);
                $zip->addFile($media->getPath(), "Other/{$safeFileName}");
                $filesAdded++;
            }
        }

        // 12. W-9 DOCUMENTS
        foreach ($driver->getMedia('w9_documents') as $media) {
            $safeFileName = $this->sanitizeFileName($media->file_name);
            $zip->addFile($media->getPath(), "W9/{$safeFileName}");
            $filesAdded++;
        }

        $zip->close();

        // Check if any files were added
        if ($filesAdded === 0) {
            @unlink($zipPath);
            return back()->with('error', 'No documents available to download.');
        }

        // Return ZIP file as download and delete after sending
        return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
    }

    /**
     * Sanitize file name for ZIP archive.
     *
     * @param string $fileName
     * @return string
     */
    private function sanitizeFileName(string $fileName): string
    {
        // Remove any path traversal attempts
        $fileName = basename($fileName);
        
        // Replace potentially dangerous characters
        $fileName = preg_replace('/[^\w\-\.\s]/', '_', $fileName);
        
        // Ensure the file name is not empty
        if (empty($fileName)) {
            $fileName = 'document_' . uniqid();
        }
        
        return $fileName;
    }

    /**
     * Calculate document completion progress.
     *
     * @param \App\Models\UserDriverDetail $driver
     * @return array
     */
    private function calculateDocumentProgress($driver): array
    {
        $requiredDocuments = [
            [
                'name' => 'Driver License',
                'icon' => 'CreditCard',
                'completed' => $driver->licenses && $driver->licenses->count() > 0,
                'link' => route('driver.licenses.index'),
            ],
            [
                'name' => 'Medical Certificate',
                'icon' => 'Heart',
                'completed' => $driver->medicalQualification && 
                    ($driver->medicalQualification->medical_card_expiration_date || 
                     $driver->medicalQualification->dot_medical_expiry_date),
                'link' => route('driver.medical.index'),
            ],
            [
                'name' => 'Profile Photo',
                'icon' => 'User',
                'completed' => $driver->getFirstMediaUrl('profile_photo_driver') ? true : false,
                'link' => route('driver.profile'),
            ],
            [
                'name' => 'Driving Record',
                'icon' => 'FileText',
                'completed' => $driver->getMedia('driving_records')->count() > 0,
                'link' => route('driver.documents.create') . '?category=driving_records',
            ],
            [
                'name' => 'Criminal Record',
                'icon' => 'FileSearch',
                'completed' => $driver->getMedia('criminal_records')->count() > 0,
                'link' => route('driver.documents.create') . '?category=criminal_records',
            ],
            [
                'name' => 'Medical Record',
                'icon' => 'FilePlus',
                'completed' => $driver->getMedia('medical_records')->count() > 0,
                'link' => route('driver.documents.create') . '?category=medical_records',
            ],
            [
                'name' => 'Clearing House',
                'icon' => 'Shield',
                'completed' => $driver->getMedia('clearing_house')->count() > 0,
                'link' => route('driver.documents.create') . '?category=clearing_house',
            ],
        ];

        $completedCount = collect($requiredDocuments)->filter(fn($doc) => $doc['completed'])->count();
        $totalCount = count($requiredDocuments);
        $percentage = $totalCount > 0 ? round(($completedCount / $totalCount) * 100) : 0;

        return [
            'percentage' => $percentage,
            'completed' => $completedCount,
            'total' => $totalCount,
            'documents' => $requiredDocuments,
            'missing' => collect($requiredDocuments)->filter(fn($doc) => !$doc['completed'])->values()->all(),
        ];
    }
}
