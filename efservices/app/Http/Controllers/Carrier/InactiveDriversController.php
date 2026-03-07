<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Controller;
use App\Models\DriverArchive;
use App\Services\Driver\ArchiveDownloadService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Inactive Drivers Controller for Carriers
 * 
 * Handles viewing and downloading of inactive driver archives.
 * Carriers can only access archives that belong to them.
 */
class InactiveDriversController extends Controller
{
    public function __construct(
        protected ArchiveDownloadService $downloadService
    ) {
        // Ensure user is authenticated and is a carrier
        $this->middleware('auth');
    }

    /**
     * Display a listing of inactive drivers for the carrier.
     */
    public function index(Request $request): View
    {
        // Get carrier ID from authenticated user
        $carrierId = $this->getCarrierId();

        if (!$carrierId) {
            \Illuminate\Support\Facades\Log::warning('InactiveDriversController::index - No carrier ID found for user', [
                'user_id' => auth()->id(),
            ]);
            abort(403, 'No se encontró información del carrier asociado a su cuenta.');
        }

        return view('carrier.drivers.inactive.index', [
            'activeTheme' => session('activeTheme', config('app.theme', 'raze')),
            'carrierId' => $carrierId,
        ]);
    }

    /**
     * Display the specified inactive driver archive.
     */
    public function show(DriverArchive $archive): View
    {
        // Verify carrier has access to this archive
        $carrierId = $this->getCarrierId();

        if (!$carrierId) {
            \Illuminate\Support\Facades\Log::warning('InactiveDriversController::show - No carrier ID found for user', [
                'user_id' => auth()->id(),
                'archive_id' => $archive->id,
            ]);
            abort(403, 'No se encontró información del carrier asociado a su cuenta.');
        }

        if (!$archive->canBeAccessedByCarrier($carrierId)) {
            \Illuminate\Support\Facades\Log::warning('InactiveDriversController::show - Carrier access denied', [
                'user_id' => auth()->id(),
                'user_carrier_id' => $carrierId,
                'archive_id' => $archive->id,
                'archive_carrier_id' => $archive->carrier_id,
            ]);
            abort(403, 'No tiene permiso para acceder a este archivo de conductor.');
        }

        // Load relationships
        $archive->load(['carrier', 'user', 'migrationRecord.targetCarrier']);

        // Transform snapshot data to match view expectations
        $archive = $this->transformSnapshotData($archive);

        return view('carrier.drivers.inactive.show', [
            'activeTheme' => session('activeTheme', config('app.theme', 'raze')),
            'archive' => $archive,
            'documentsByCategory' => $archive->getDocumentsByCategory(),
            'documentCount' => $archive->getDocumentCount(),
        ]);
    }

    /**
     * Transform snapshot data to match view expectations.
     */
    protected function transformSnapshotData(DriverArchive $archive): DriverArchive
    {
        // Transform employment history
        if ($archive->employment_history_snapshot) {
            $employmentData = [];
            
            // Add employment companies
            if (isset($archive->employment_history_snapshot['employment_companies'])) {
                foreach ($archive->employment_history_snapshot['employment_companies'] as $company) {
                    $employmentData[] = [
                        'employer_name' => $company['company_name'] ?? 'Unknown Employer',
                        'position' => $company['position'] ?? null,
                        'start_date' => $company['start_date'] ?? null,
                        'end_date' => $company['end_date'] ?? null,
                        'address' => $company['address'] ?? null,
                        'city' => $company['city'] ?? null,
                        'state' => $company['state'] ?? null,
                        'zip' => $company['zip'] ?? null,
                        'phone' => $company['phone'] ?? null,
                        'email' => $company['email'] ?? null,
                        'fax' => $company['fax'] ?? null,
                        'contact_name' => $company['contact'] ?? null,
                        'reason_for_leaving' => $company['reason_for_leaving'] ?? null,
                        'was_subject_to_fmcsr' => $company['was_subject_to_fmcsr'] ?? false,
                        'was_subject_to_drug_testing' => $company['was_subject_to_drug_testing'] ?? false,
                        'is_verified' => ($company['verification_status'] ?? null) === 'verified',
                        'verified_at' => $company['verification_date'] ?? null,
                        'notes' => $company['verification_notes'] ?? null,
                    ];
                }
            }
            
            // Add work histories
            if (isset($archive->employment_history_snapshot['work_histories'])) {
                foreach ($archive->employment_history_snapshot['work_histories'] as $history) {
                    $employmentData[] = [
                        'employer_name' => $history['employer_name'] ?? 'Unknown Employer',
                        'position' => $history['position'] ?? null,
                        'start_date' => $history['start_date'] ?? null,
                        'end_date' => $history['end_date'] ?? null,
                    ];
                }
            }
            
            // Add unemployment periods
            if (isset($archive->employment_history_snapshot['unemployment_periods'])) {
                foreach ($archive->employment_history_snapshot['unemployment_periods'] as $period) {
                    $employmentData[] = [
                        'employer_name' => 'Unemployment Period',
                        'position' => null,
                        'start_date' => $period['start_date'] ?? null,
                        'end_date' => $period['end_date'] ?? null,
                        'reason_for_leaving' => $period['reason'] ?? 'Unemployed',
                        'is_unemployment' => true,
                    ];
                }
            }
            
            $archive->employment_history_snapshot = $employmentData;
        }

        // Transform medical snapshot to array
        if ($archive->medical_snapshot && !isset($archive->medical_snapshot[0])) {
            $archive->medical_snapshot = [$archive->medical_snapshot];
        }

        // Transform certifications snapshot to array
        if ($archive->certifications_snapshot && !isset($archive->certifications_snapshot[0])) {
            $archive->certifications_snapshot = [$archive->certifications_snapshot];
        }

        // Transform training snapshot
        if ($archive->training_snapshot) {
            $trainingData = [];
            
            // Add training schools
            if (isset($archive->training_snapshot['training_schools'])) {
                foreach ($archive->training_snapshot['training_schools'] as $school) {
                    $trainingData[] = [
                        'type' => 'school',
                        'course_name' => $school['school_name'] ?? 'Training School',
                        'provider' => $school['school_name'] ?? null,
                        'city' => $school['city'] ?? null,
                        'state' => $school['state'] ?? null,
                        'start_date' => $school['date_attended_from'] ?? null,
                        'completion_date' => $school['date_attended_to'] ?? null,
                        'status' => isset($school['graduated']) && $school['graduated'] ? 'completed' : 'in_progress',
                        'subject_to_safety_regulations' => $school['subject_to_safety_regulations'] ?? false,
                        'performed_safety_functions' => $school['performed_safety_functions'] ?? false,
                        'training_skills' => $school['training_skills'] ?? null,
                    ];
                }
            }
            
            // Add driver trainings
            if (isset($archive->training_snapshot['driver_trainings'])) {
                foreach ($archive->training_snapshot['driver_trainings'] as $training) {
                    $trainingData[] = [
                        'type' => 'training',
                        'course_name' => $training['training_name'] ?? 'Training Course',
                        'description' => $training['training_description'] ?? null,
                        'assigned_date' => $training['assigned_date'] ?? null,
                        'due_date' => $training['due_date'] ?? null,
                        'completion_date' => $training['completed_at'] ?? null,
                        'status' => $training['status'] ?? null,
                        'viewed' => $training['viewed'] ?? false,
                        'consent_accepted' => $training['consent_accepted'] ?? false,
                    ];
                }
            }
            
            $archive->training_snapshot = $trainingData;
        }

        return $archive;
    }

    /**
     * Download the complete archive as a ZIP file.
     */
    public function download(DriverArchive $archive): StreamedResponse
    {
        // Verify carrier has access to this archive
        $carrierId = $this->getCarrierId();

        if (!$carrierId) {
            \Illuminate\Support\Facades\Log::warning('InactiveDriversController::download - No carrier ID found for user', [
                'user_id' => auth()->id(),
                'archive_id' => $archive->id,
            ]);
            abort(403, 'No se encontró información del carrier asociado a su cuenta.');
        }

        if (!$archive->canBeAccessedByCarrier($carrierId)) {
            \Illuminate\Support\Facades\Log::warning('InactiveDriversController::download - Carrier access denied', [
                'user_id' => auth()->id(),
                'user_carrier_id' => $carrierId,
                'archive_id' => $archive->id,
                'archive_carrier_id' => $archive->carrier_id,
            ]);
            abort(403, 'No tiene permiso para descargar este archivo de conductor.');
        }

        // Stream the ZIP file
        return $this->downloadService->streamArchiveZip($archive);
    }

    /**
     * Get the carrier ID from the authenticated user.
     */
    protected function getCarrierId(): ?int
    {
        $user = auth()->user();

        if (!$user) {
            \Illuminate\Support\Facades\Log::warning('InactiveDriversController: No authenticated user');
            return null;
        }

        // Load carrierDetails relationship if not loaded
        if (!$user->relationLoaded('carrierDetails')) {
            $user->load('carrierDetails');
        }

        // Try to get carrier ID from user's carrier details (for carrier employees)
        if ($user->carrierDetails && $user->carrierDetails->carrier_id) {
            return $user->carrierDetails->carrier_id;
        }

        // Load carriers relationship if not loaded
        if (!$user->relationLoaded('carriers')) {
            $user->load('carriers');
        }

        // Try to get carrier ID from carriers relationship (for carrier owners/managers)
        if ($user->carriers && $user->carriers->isNotEmpty()) {
            return $user->carriers->first()->id;
        }

        \Illuminate\Support\Facades\Log::warning('InactiveDriversController: Could not determine carrier ID', [
            'user_id' => $user->id,
            'has_carrier_details' => $user->carrierDetails ? true : false,
            'carrier_details_carrier_id' => $user->carrierDetails?->carrier_id,
            'carriers_count' => $user->carriers?->count() ?? 0,
        ]);

        return null;
    }
}
