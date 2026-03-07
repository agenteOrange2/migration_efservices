<?php

namespace App\Livewire\Admin\Driver;

use App\Models\DriverArchive;
use App\Models\UserDriverDetail;
use App\Services\Driver\DriverArchiveService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\Attributes\Layout;

/**
 * Livewire component for viewing archived driver details.
 * Displays all historical data in read-only mode.
 */
#[Layout('layouts.admin')]
class ArchivedDriverDetail extends Component
{
    public ?DriverArchive $archive = null;
    public string $activeTab = 'personal';

    public function mount(int $archiveId): void
    {
        $this->archive = DriverArchive::with(['carrier', 'user', 'migrationRecord.targetCarrier', 'migrationRecord.sourceCarrier'])
            ->findOrFail($archiveId);

        // Check access permissions
        if (!$this->canViewArchive()) {
            abort(403, 'You do not have permission to view this archive.');
        }
    }

    protected function canViewArchive(): bool
    {
        $user = auth()->user();

        // Superadmin can view all
        if ($user->hasRole('superadmin')) {
            return true;
        }

        // Carrier admin can only view their carrier's archives
        $carrierDetail = $user->carrierDetails;
        if ($carrierDetail && $this->archive->carrier_id === $carrierDetail->carrier_id) {
            return true;
        }

        return false;
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function getPersonalInfoProperty(): array
    {
        return $this->archive->driver_data_snapshot ?? [];
    }

    public function getLicensesProperty(): array
    {
        $licenses = $this->archive->licenses_snapshot ?? [];
        
        // Ensure each license has all expected fields
        return collect($licenses)->map(function ($license) {
            return [
                'license_number' => $license['license_number'] ?? null,
                'license_type' => $license['license_type'] ?? 'CDL',
                'state' => $license['state'] ?? null,
                'class' => $license['class'] ?? $license['license_class'] ?? null,
                'expiration_date' => $license['expiration_date'] ?? null,
                'issue_date' => $license['issue_date'] ?? null,
                'status' => $license['status'] ?? 'unknown',
                'endorsements' => $license['endorsements'] ?? [],
                'restrictions' => $license['restrictions'] ?? [],
            ];
        })->toArray();
    }

    public function getMedicalProperty(): array
    {
        $medical = $this->archive->medical_snapshot;
        
        // If medical is null or empty, return empty array
        if (empty($medical)) {
            return [];
        }
        
        // If it's a single object, wrap it in an array for the view
        // The view expects to iterate over medical records
        if (isset($medical['id']) || isset($medical['exam_date']) || isset($medical['expiration_date'])) {
            return [$medical];
        }
        
        // If it's already an array of records, return as is
        return $medical;
    }

    public function getCertificationsProperty(): ?array
    {
        return $this->archive->certifications_snapshot;
    }

    public function getEmploymentHistoryProperty(): array
    {
        $snapshot = $this->archive->employment_history_snapshot ?? [];
        $employmentData = [];
        
        // Transform employment companies
        if (isset($snapshot['employment_companies'])) {
            foreach ($snapshot['employment_companies'] as $company) {
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
                    'contact_name' => $company['contact'] ?? null,
                    'reason_for_leaving' => $company['reason_for_leaving'] ?? null,
                    'was_subject_to_fmcsr' => $company['was_subject_to_fmcsr'] ?? false,
                    'was_subject_to_drug_testing' => $company['was_subject_to_drug_testing'] ?? false,
                    'is_verified' => ($company['verification_status'] ?? null) === 'verified',
                    'verified_at' => $company['verification_date'] ?? null,
                ];
            }
        }
        
        // Transform work histories
        if (isset($snapshot['work_histories'])) {
            foreach ($snapshot['work_histories'] as $history) {
                $employmentData[] = [
                    'employer_name' => $history['employer_name'] ?? 'Unknown Employer',
                    'position' => $history['position'] ?? null,
                    'start_date' => $history['start_date'] ?? null,
                    'end_date' => $history['end_date'] ?? null,
                ];
            }
        }
        
        // Transform unemployment periods
        if (isset($snapshot['unemployment_periods'])) {
            foreach ($snapshot['unemployment_periods'] as $period) {
                $employmentData[] = [
                    'employer_name' => 'Unemployment Period',
                    'is_unemployment' => true,
                    'start_date' => $period['start_date'] ?? null,
                    'end_date' => $period['end_date'] ?? null,
                    'reason_for_leaving' => $period['reason'] ?? 'Unemployed',
                ];
            }
        }
        
        return $employmentData;
    }

    public function getTrainingProperty(): array
    {
        $snapshot = $this->archive->training_snapshot ?? [];
        $trainingData = [];
        
        // Transform training schools
        if (isset($snapshot['training_schools'])) {
            foreach ($snapshot['training_schools'] as $school) {
                $trainingData[] = [
                    'type' => 'school',
                    'name' => $school['school_name'] ?? 'Training School',
                    'city' => $school['city'] ?? null,
                    'state' => $school['state'] ?? null,
                    'start_date' => $school['date_attended_from'] ?? null,
                    'completion_date' => $school['date_attended_to'] ?? null,
                    'status' => isset($school['graduated']) && $school['graduated'] ? 'completed' : 'in_progress',
                ];
            }
        }
        
        // Transform driver trainings
        if (isset($snapshot['driver_trainings'])) {
            foreach ($snapshot['driver_trainings'] as $training) {
                $trainingData[] = [
                    'type' => 'training',
                    'name' => $training['training_name'] ?? 'Training Course',
                    'assigned_date' => $training['assigned_date'] ?? null,
                    'due_date' => $training['due_date'] ?? null,
                    'completion_date' => $training['completed_at'] ?? null,
                    'status' => $training['status'] ?? null,
                ];
            }
        }
        
        return $trainingData;
    }

    public function getTestingProperty(): array
    {
        return $this->archive->testing_snapshot ?? [];
    }

    public function getAccidentsProperty(): array
    {
        return $this->archive->accidents_snapshot ?? [];
    }

    public function getConvictionsProperty(): array
    {
        $convictions = $this->archive->convictions_snapshot ?? [];
        
        // Transform convictions to match view expectations
        return collect($convictions)->map(function ($conviction) {
            return [
                'violation_type' => $conviction['charge'] ?? $conviction['violation_type'] ?? 'Traffic Violation',
                'violation_date' => $conviction['conviction_date'] ?? $conviction['violation_date'] ?? null,
                'conviction_date' => $conviction['conviction_date'] ?? null,
                'location' => $conviction['location'] ?? null,
                'state' => $conviction['state'] ?? null,
                'penalty' => $conviction['penalty'] ?? null,
                'fine_amount' => $conviction['fine_amount'] ?? null,
                'points' => $conviction['points'] ?? null,
                'description' => $conviction['description'] ?? $conviction['penalty'] ?? null,
                'notes' => $conviction['notes'] ?? null,
            ];
        })->toArray();
    }

    public function getInspectionsProperty(): array
    {
        return $this->archive->inspections_snapshot ?? [];
    }

    public function getHosProperty(): array
    {
        $snapshot = $this->archive->hos_snapshot ?? [];
        
        return [
            'entries_count' => $snapshot['total_entries'] ?? 0,
            'violations_count' => $snapshot['violations_count'] ?? 0,
            'last_entry_date' => $snapshot['last_entry_date'] ?? null,
            'total_drive_hours' => $snapshot['total_drive_hours'] ?? null,
        ];
    }

    public function getVehicleAssignmentsProperty(): array
    {
        $snapshot = $this->archive->vehicle_assignments_snapshot ?? [];
        
        return collect($snapshot)->map(function ($assignment) {
            return [
                'vehicle_make' => $assignment['vehicle_make'] ?? null,
                'vehicle_model' => $assignment['vehicle_model'] ?? null,
                'vehicle_year' => $assignment['vehicle_year'] ?? null,
                'unit_number' => $assignment['vehicle_unit_number'] ?? null,
                'driver_type' => $assignment['driver_type'] ?? null,
                'start_date' => $assignment['start_date'] ?? null,
                'end_date' => $assignment['end_date'] ?? null,
                'status' => $assignment['status'] ?? 'unknown',
            ];
        })->toArray();
    }
    
    public function getDocumentsProperty(): array
    {
        return $this->archive->getDocumentsByCategory();
    }
    
    public function getDocumentCountProperty(): int
    {
        return $this->archive->getDocumentCount();
    }

    public function getMigrationInfoProperty(): ?array
    {
        if (!$this->archive->migrationRecord) {
            return null;
        }

        $record = $this->archive->migrationRecord;
        return [
            'migrated_at' => $record->migrated_at->format('F j, Y g:i A'),
            'source_carrier' => $record->sourceCarrier->name ?? 'Unknown',
            'target_carrier' => $record->targetCarrier->name ?? 'Unknown',
            'reason' => $record->reason,
            'notes' => $record->notes,
            'migrated_by' => $record->migratedByUser->name ?? 'Unknown',
            'status' => $record->status,
            'rolled_back' => $record->isRolledBack(),
            'rolled_back_at' => $record->rolled_back_at?->format('F j, Y g:i A'),
            'rollback_reason' => $record->rollback_reason,
        ];
    }

    public function getTabsProperty(): array
    {
        return [
            'personal' => 'Personal Info',
            'licenses' => 'Licenses',
            'medical' => 'Medical',
            'employment' => 'Employment History',
            'training' => 'Training',
            'testing' => 'Testing',
            'safety' => 'Safety Records',
            'hos' => 'HOS Summary',
            'vehicles' => 'Vehicle Assignments',
            'documents' => 'Documents',
            'migration' => 'Migration Details',
        ];
    }

    public function refreshSnapshots(): void
    {
        try {
            $driver = UserDriverDetail::with([
                'user',
                'carrier',
                'licenses.endorsements',
                'medicalQualification',
                'certification',
                'employmentCompanies.company',
                'workHistories',
                'unemploymentPeriods',
                'trainingSchools',
                'driverTrainings.training',
                'testings',
                'accidents',
                'trafficConvictions',
                'inspections',
                'vehicleAssignments.vehicle',
            ])->find($this->archive->original_user_driver_detail_id);

            if (!$driver) {
                session()->flash('error', 'Original driver record not found. Cannot refresh snapshots.');
                return;
            }

            $archiveService = new DriverArchiveService();
            $snapshot = $archiveService->generateDriverSnapshot($driver);

            // Use query builder to bypass immutability protection
            DB::table('driver_archives')
                ->where('id', $this->archive->id)
                ->update([
                    'driver_data_snapshot' => json_encode($snapshot['personal_info']),
                    'licenses_snapshot' => json_encode($snapshot['licenses']),
                    'medical_snapshot' => json_encode($snapshot['medical_qualification']),
                    'certifications_snapshot' => json_encode($snapshot['certifications']),
                    'employment_history_snapshot' => json_encode($snapshot['employment_history']),
                    'training_snapshot' => json_encode($snapshot['training_records']),
                    'testing_snapshot' => json_encode($snapshot['testing_records']),
                    'accidents_snapshot' => json_encode($snapshot['accidents']),
                    'convictions_snapshot' => json_encode($snapshot['traffic_convictions']),
                    'inspections_snapshot' => json_encode($snapshot['inspections']),
                    'hos_snapshot' => json_encode($snapshot['hos_summary']),
                    'vehicle_assignments_snapshot' => json_encode($snapshot['vehicle_assignments']),
                    'updated_at' => now(),
                ]);

            // Also refresh media files
            $archiveService->archiveMediaFiles($driver, $this->archive);

            // Reload the archive
            $this->archive = DriverArchive::with(['carrier', 'user', 'migrationRecord.targetCarrier', 'migrationRecord.sourceCarrier'])
                ->findOrFail($this->archive->id);

            Log::info('Archive snapshots refreshed via UI', [
                'archive_id' => $this->archive->id,
                'driver_id' => $driver->id,
            ]);

            session()->flash('success', 'Archive snapshots refreshed successfully from driver record.');
        } catch (\Exception $e) {
            Log::error('Failed to refresh archive snapshots', [
                'archive_id' => $this->archive->id,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Failed to refresh snapshots: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.admin.driver.archived-driver-detail');
    }
}
