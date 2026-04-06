<?php

namespace App\Services\Driver;

use App\Models\Carrier;
use App\Models\DriverArchive;
use App\Models\MigrationRecord;
use App\Models\User;
use App\Models\UserDriverDetail;
use Illuminate\Support\Facades\Log;

/**
 * Service for creating and managing driver archives.
 * 
 * Handles the creation of complete driver data snapshots when drivers
 * migrate between carriers or are terminated.
 */
class DriverArchiveService
{
    /**
     * Generate a complete snapshot of all driver data.
     */
    public function generateDriverSnapshot(UserDriverDetail $driver): array
    {
        $driver->load([
            'user',
            'carrier',
            'licenses.endorsements',
            'medicalQualification',
            'certification',
            'employmentCompanies.company',  // Load the company relationship
            'workHistories',
            'unemploymentPeriods',  // Load unemployment periods
            'trainingSchools',
            'driverTrainings.training',  // Load training relationship
            'testings',
            'accidents',
            'trafficConvictions',
            'inspections',
            'vehicleAssignments.vehicle',
            'fmcsrData',
        ]);

        return [
            'personal_info' => $this->getPersonalInfoSnapshot($driver),
            'licenses' => $this->getLicensesSnapshot($driver),
            'medical_qualification' => $this->getMedicalSnapshot($driver),
            'certifications' => $this->getCertificationsSnapshot($driver),
            'employment_history' => $this->getEmploymentHistorySnapshot($driver),
            'training_records' => $this->getTrainingSnapshot($driver),
            'testing_records' => $this->getTestingSnapshot($driver),
            'accidents' => $this->getAccidentsSnapshot($driver),
            'traffic_convictions' => $this->getConvictionsSnapshot($driver),
            'inspections' => $this->getInspectionsSnapshot($driver),
            'hos_summary' => $this->getHosSnapshot($driver),
            'vehicle_assignments' => $this->getVehicleAssignmentsSnapshot($driver),
        ];
    }

    protected function getPersonalInfoSnapshot(UserDriverDetail $driver): array
    {
        $user = $driver->user;
        
        // Get profile photo URL
        $profilePhotoUrl = null;
        $profilePhoto = $driver->getFirstMedia('profile_photo_driver');
        if ($profilePhoto) {
            $profilePhotoUrl = $profilePhoto->getUrl();
        }
        
        return [
            'user_id' => $user->id,
            'user_driver_detail_id' => $driver->id,
            'name' => $user->name,
            'email' => $user->email,
            'middle_name' => $driver->middle_name,
            'last_name' => $driver->last_name,
            'phone' => $driver->phone,
            'date_of_birth' => $driver->date_of_birth?->format('Y-m-d'),
            'hire_date' => $driver->hire_date?->format('Y-m-d'),
            'termination_date' => $driver->termination_date?->format('Y-m-d'),
            'status' => $driver->status,
            'status_name' => $driver->status_name,
            'completion_percentage' => $driver->completion_percentage,
            'emergency_contact_name' => $driver->emergency_contact_name,
            'emergency_contact_phone' => $driver->emergency_contact_phone,
            'emergency_contact_relationship' => $driver->emergency_contact_relationship,
            'notes' => $driver->notes,
            'carrier_id' => $driver->carrier_id,
            'carrier_name' => $driver->carrier?->name,
            'profile_photo_url' => $profilePhotoUrl,
        ];
    }

    protected function getLicensesSnapshot(UserDriverDetail $driver): array
    {
        return $driver->licenses->map(function ($license) {
            return [
                'id' => $license->id,
                'license_number' => $license->license_number,
                'state' => $license->state_of_issue ?? null,
                'class' => $license->license_class ?? null,
                'expiration_date' => $license->expiration_date?->format('Y-m-d'),
                'is_cdl' => $license->is_cdl ?? false,
                'restrictions' => $license->restrictions ?? null,
                'status' => $license->status ?? null,
                'is_primary' => $license->is_primary ?? false,
                'endorsements' => $license->endorsements->pluck('code')->toArray(),
            ];
        })->toArray();
    }

    protected function getMedicalSnapshot(UserDriverDetail $driver): ?array
    {
        $medical = $driver->medicalQualification;
        if (!$medical) return null;

        return [
            'id' => $medical->id,
            'social_security_number' => $medical->social_security_number ?? null,
            'hire_date' => $medical->hire_date?->format('Y-m-d'),
            'location' => $medical->location ?? null,
            'is_suspended' => $medical->is_suspended ?? false,
            'suspension_date' => $medical->suspension_date?->format('Y-m-d'),
            'is_terminated' => $medical->is_terminated ?? false,
            'termination_date' => $medical->termination_date?->format('Y-m-d'),
            'expiration_date' => $medical->medical_card_expiration_date?->format('Y-m-d'),
            'examiner_name' => $medical->medical_examiner_name ?? null,
            'examiner_registry_number' => $medical->medical_examiner_registry_number ?? null,
            'status' => $medical->status,
        ];
    }

    protected function getCertificationsSnapshot(UserDriverDetail $driver): ?array
    {
        $certification = $driver->certification;
        if (!$certification) return null;

        return [
            'id' => $certification->id,
            'certification_date' => $certification->signed_at?->format('Y-m-d H:i:s'),
            'signature_data' => $certification->signature ?? null,
            'is_accepted' => $certification->is_accepted ?? false,
        ];
    }

    protected function getEmploymentHistorySnapshot(UserDriverDetail $driver): array
    {
        $companies = $driver->employmentCompanies->map(function ($company) {
            return [
                'id' => $company->id,
                'company_name' => $company->company?->company_name ?? null,
                'address' => $company->company?->address ?? null,
                'city' => $company->company?->city ?? null,
                'state' => $company->company?->state ?? null,
                'zip' => $company->company?->zip ?? null,
                'phone' => $company->company?->phone ?? null,
                'contact' => $company->company?->contact ?? null,
                'email' => $company->company?->email ?? null,
                'fax' => $company->company?->fax ?? null,
                'position' => $company->positions_held ?? null,
                'start_date' => $company->employed_from?->format('Y-m-d'),
                'end_date' => $company->employed_to?->format('Y-m-d'),
                'reason_for_leaving' => $company->reason_for_leaving ?? null,
                'was_subject_to_fmcsr' => $company->subject_to_fmcsr ?? null,
                'was_subject_to_drug_testing' => $company->safety_sensitive_function ?? null,
                'verification_status' => $company->verification_status ?? null,
                'verification_date' => $company->verification_date?->format('Y-m-d H:i:s'),
                'verification_notes' => $company->verification_notes ?? null,
            ];
        })->toArray();

        $workHistories = $driver->workHistories->map(function ($history) {
            return [
                'id' => $history->id,
                'employer_name' => $history->employer_name ?? null,
                'start_date' => $history->start_date?->format('Y-m-d'),
                'end_date' => $history->end_date?->format('Y-m-d'),
                'position' => $history->position ?? null,
            ];
        })->toArray();

        // Add unemployment periods
        $unemploymentPeriods = [];
        if (method_exists($driver, 'unemploymentPeriods')) {
            $unemploymentPeriods = $driver->unemploymentPeriods->map(function ($period) {
                return [
                    'id' => $period->id,
                    'employer_name' => 'Unemployment Period',
                    'start_date' => $period->start_date?->format('Y-m-d'),
                    'end_date' => $period->end_date?->format('Y-m-d'),
                    'reason' => $period->comments ?? 'Unemployed',
                ];
            })->toArray();
        }

        return [
            'employment_companies' => $companies,
            'work_histories' => $workHistories,
            'unemployment_periods' => $unemploymentPeriods,
        ];
    }

    protected function getTrainingSnapshot(UserDriverDetail $driver): array
    {
        $schools = $driver->trainingSchools->map(function ($school) {
            return [
                'id' => $school->id,
                'school_name' => $school->school_name ?? null,
                'city' => $school->city ?? null,
                'state' => $school->state ?? null,
                'date_attended_from' => $school->date_start?->format('Y-m-d'),
                'date_attended_to' => $school->date_end?->format('Y-m-d'),
                'graduated' => $school->graduated ?? null,
                'subject_to_safety_regulations' => $school->subject_to_safety_regulations ?? false,
                'performed_safety_functions' => $school->performed_safety_functions ?? false,
                'training_skills' => $school->training_skills ?? null,
            ];
        })->toArray();

        $trainings = $driver->driverTrainings->map(function ($training) {
            return [
                'id' => $training->id,
                'training_id' => $training->training_id,
                'training_name' => $training->training?->title ?? null,
                'training_description' => $training->training?->description ?? null,
                'assigned_date' => $training->assigned_date?->format('Y-m-d H:i:s'),
                'due_date' => $training->due_date?->format('Y-m-d H:i:s'),
                'completed_at' => $training->completed_date?->format('Y-m-d H:i:s'),
                'status' => $training->status ?? null,
                'viewed' => $training->viewed ?? false,
                'viewed_at' => $training->viewed_at?->format('Y-m-d H:i:s'),
                'consent_accepted' => $training->consent_accepted ?? false,
                'consent_accepted_at' => $training->consent_accepted_at?->format('Y-m-d H:i:s'),
            ];
        })->toArray();

        return ['training_schools' => $schools, 'driver_trainings' => $trainings];
    }

    protected function getTestingSnapshot(UserDriverDetail $driver): array
    {
        return $driver->testings->map(function ($test) {
            return [
                'id' => $test->id,
                'test_type' => $test->test_type ?? null,
                'test_date' => $test->test_date?->format('Y-m-d'),
                'result' => $test->test_result ?? null,
                'status' => $test->status ?? null,
                'reason' => $this->getTestReason($test),
                'administered_by' => $test->administered_by ?? null,
                'mro' => $test->mro ?? null,
                'requester_name' => $test->requester_name ?? null,
                'location' => $test->location ?? null,
                'scheduled_time' => $test->scheduled_time?->format('Y-m-d H:i:s'),
                'notes' => $test->notes ?? null,
                'next_test_due' => $test->next_test_due?->format('Y-m-d'),
                'bill_to' => $test->bill_to ?? null,
            ];
        })->toArray();
    }

    /**
     * Get the test reason from boolean flags
     */
    protected function getTestReason($test): ?string
    {
        if ($test->is_random_test) return 'random';
        if ($test->is_post_accident_test) return 'post_accident';
        if ($test->is_reasonable_suspicion_test) return 'reasonable_suspicion';
        if ($test->is_pre_employment_test) return 'pre_employment';
        if ($test->is_follow_up_test) return 'follow_up';
        if ($test->is_return_to_duty_test) return 'return_to_duty';
        if ($test->is_other_reason_test) return $test->other_reason_description;
        return null;
    }

    protected function getAccidentsSnapshot(UserDriverDetail $driver): array
    {
        return $driver->accidents->map(function ($accident) {
            return [
                'id' => $accident->id,
                'accident_date' => $accident->accident_date?->format('Y-m-d'),
                'nature_of_accident' => $accident->nature_of_accident ?? null,
                'description' => $accident->comments ?? null,
                'fatalities' => $accident->number_of_fatalities ?? 0,
                'injuries' => $accident->number_of_injuries ?? 0,
                'had_fatalities' => $accident->had_fatalities ?? false,
                'had_injuries' => $accident->had_injuries ?? false,
            ];
        })->toArray();
    }

    protected function getConvictionsSnapshot(UserDriverDetail $driver): array
    {
        return $driver->trafficConvictions->map(function ($conviction) {
            return [
                'id' => $conviction->id,
                'conviction_date' => $conviction->conviction_date?->format('Y-m-d'),
                'location' => $conviction->location ?? null,
                'charge' => $conviction->charge ?? null,
                'penalty' => $conviction->penalty ?? null,
            ];
        })->toArray();
    }

    protected function getInspectionsSnapshot(UserDriverDetail $driver): array
    {
        return $driver->inspections->map(function ($inspection) {
            return [
                'id' => $inspection->id,
                'inspection_date' => $inspection->inspection_date?->format('Y-m-d'),
                'location' => $inspection->location ?? null,
                'level' => $inspection->inspection_level ?? null,
                'type' => $inspection->inspection_type ?? null,
                'inspector_name' => $inspection->inspector_name ?? null,
                'inspector_number' => $inspection->inspector_number ?? null,
                'status' => $inspection->status ?? null,
                'result' => $inspection->status ?? null,
                'violations' => $inspection->defects_found ?? null,
                'defects_corrected' => $inspection->is_defects_corrected ?? false,
                'defects_corrected_date' => $inspection->defects_corrected_date?->format('Y-m-d'),
                'corrected_by' => $inspection->corrected_by ?? null,
                'is_vehicle_safe' => $inspection->is_vehicle_safe_to_operate ?? false,
                'notes' => $inspection->notes ?? null,
            ];
        })->toArray();
    }

    protected function getHosSnapshot(UserDriverDetail $driver): array
    {
        $hosEntriesCount = \App\Models\Hos\HosEntry::where('user_driver_detail_id', $driver->id)->count();
        $violationsCount = \App\Models\Hos\HosViolation::where('user_driver_detail_id', $driver->id)->count();
        $lastEntry = \App\Models\Hos\HosEntry::where('user_driver_detail_id', $driver->id)
            ->orderBy('created_at', 'desc')
            ->first();

        return [
            'total_entries' => $hosEntriesCount,
            'violations_count' => $violationsCount,
            'last_entry_date' => $lastEntry?->created_at?->format('Y-m-d'),
        ];
    }

    protected function getVehicleAssignmentsSnapshot(UserDriverDetail $driver): array
    {
        return $driver->vehicleAssignments->map(function ($assignment) {
            return [
                'id' => $assignment->id,
                'vehicle_id' => $assignment->vehicle_id,
                'vehicle_unit_number' => $assignment->vehicle?->unit_number ?? null,
                'vehicle_make' => $assignment->vehicle?->make ?? null,
                'vehicle_model' => $assignment->vehicle?->model ?? null,
                'driver_type' => $assignment->driver_type,
                'start_date' => $assignment->start_date?->format('Y-m-d'),
                'end_date' => $assignment->end_date?->format('Y-m-d'),
                'status' => $assignment->status,
            ];
        })->toArray();
    }

    /**
     * Create a complete archive of the driver.
     */
    public function createArchive(
        UserDriverDetail $driver,
        ?MigrationRecord $migrationRecord = null,
        string $reason = DriverArchive::REASON_MIGRATION
    ): DriverArchive {
        $snapshot = $this->generateDriverSnapshot($driver);

        $archive = DriverArchive::create([
            'original_user_driver_detail_id' => $driver->id,
            'user_id' => $driver->user_id,
            'carrier_id' => $driver->carrier_id,
            'migration_record_id' => $migrationRecord?->id,
            'archived_at' => now(),
            'archive_reason' => $reason,
            'driver_data_snapshot' => $snapshot['personal_info'],
            'licenses_snapshot' => $snapshot['licenses'],
            'medical_snapshot' => $snapshot['medical_qualification'],
            'certifications_snapshot' => $snapshot['certifications'],
            'employment_history_snapshot' => $snapshot['employment_history'],
            'training_snapshot' => $snapshot['training_records'],
            'testing_snapshot' => $snapshot['testing_records'],
            'accidents_snapshot' => $snapshot['accidents'],
            'convictions_snapshot' => $snapshot['traffic_convictions'],
            'inspections_snapshot' => $snapshot['inspections'],
            'hos_snapshot' => $snapshot['hos_summary'],
            'vehicle_assignments_snapshot' => $snapshot['vehicle_assignments'],
            'status' => DriverArchive::STATUS_ARCHIVED,
        ]);

        $this->archiveMediaFiles($driver, $archive);

        Log::info('Driver archive created', [
            'archive_id' => $archive->id,
            'driver_id' => $driver->id,
            'carrier_id' => $driver->carrier_id,
            'reason' => $reason,
        ]);

        return $archive;
    }

    /**
     * Archive media files from the driver to the archive.
     * Copies all documents that existed up to the archive date.
     */
    public function archiveMediaFiles(UserDriverDetail $driver, DriverArchive $archive): void
    {
        $cutoffDate = $archive->archived_at;

        try {
            // Archive profile photo
            $profilePhoto = $driver->getFirstMedia('profile_photo_driver');
            if ($profilePhoto && $profilePhoto->created_at->lte($cutoffDate)) {
                $this->copyMediaToArchive($profilePhoto, $archive, 'archived_profile_photo', 'Profile');
            }

            // Archive license documents
            foreach (['license_front', 'license_back'] as $collection) {
                $media = $driver->getFirstMedia($collection);
                if ($media && $media->created_at->lte($cutoffDate)) {
                    $this->copyMediaToArchive($media, $archive, 'archived_licenses', 'Licenses');
                }
            }

            // Archive trip reports, daily logs, monthly summaries, signatures
            foreach (['trip_reports', 'daily_logs', 'monthly_summaries', 'signatures'] as $collection) {
                foreach ($driver->getMedia($collection) as $media) {
                    if ($media->created_at->lte($cutoffDate)) {
                        $this->copyMediaToArchive($media, $archive, 'archived_documents', ucwords(str_replace('_', ' ', $collection)));
                    }
                }
            }

            // Archive documents from related models
            $this->archiveRelatedDocuments($driver, $archive, $cutoffDate);

            Log::info('Driver media files archived', [
                'archive_id' => $archive->id,
                'driver_id' => $driver->id,
                'total_documents' => $archive->getMedia('archived_documents')->count() 
                    + $archive->getMedia('archived_licenses')->count()
                    + ($archive->getFirstMedia('archived_profile_photo') ? 1 : 0),
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to archive some media files', [
                'archive_id' => $archive->id,
                'driver_id' => $driver->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Copy a single media file to the archive.
     */
    protected function copyMediaToArchive($media, DriverArchive $archive, string $collection, string $category): void
    {
        try {
            // Get the file path
            $filePath = $media->getPath();
            
            if (file_exists($filePath)) {
                $archive->addMedia($filePath)
                    ->preservingOriginal()
                    ->withCustomProperties(['category' => $category])
                    ->toMediaCollection($collection);
            } else {
                // Try URL if path doesn't exist
                $archive->addMediaFromUrl($media->getUrl())
                    ->withCustomProperties(['category' => $category])
                    ->toMediaCollection($collection);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to copy media to archive', [
                'media_id' => $media->id,
                'collection' => $collection,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Archive documents from related models (licenses, medical, testings, etc.)
     */
    protected function archiveRelatedDocuments(UserDriverDetail $driver, DriverArchive $archive, $cutoffDate): void
    {
        // Archive license documents from licenses
        foreach ($driver->licenses as $license) {
            if (method_exists($license, 'getMedia')) {
                foreach ($license->getMedia() as $media) {
                    if ($media->created_at->lte($cutoffDate)) {
                        $this->copyMediaToArchive($media, $archive, 'archived_documents', 'Licenses');
                    }
                }
            }
        }

        // Archive medical qualification documents
        if ($driver->medicalQualification && method_exists($driver->medicalQualification, 'getMedia')) {
            foreach ($driver->medicalQualification->getMedia() as $media) {
                if ($media->created_at->lte($cutoffDate)) {
                    $this->copyMediaToArchive($media, $archive, 'archived_documents', 'Medical');
                }
            }
        }

        // Archive testing documents
        foreach ($driver->testings as $testing) {
            if (method_exists($testing, 'getMedia')) {
                foreach ($testing->getMedia() as $media) {
                    if ($media->created_at->lte($cutoffDate)) {
                        $this->copyMediaToArchive($media, $archive, 'archived_documents', 'Testing');
                    }
                }
            }
        }

        // Archive accident documents
        foreach ($driver->accidents as $accident) {
            if (method_exists($accident, 'getMedia')) {
                foreach ($accident->getMedia() as $media) {
                    if ($media->created_at->lte($cutoffDate)) {
                        $this->copyMediaToArchive($media, $archive, 'archived_documents', 'Accidents');
                    }
                }
            }
        }

        // Archive inspection documents
        foreach ($driver->inspections as $inspection) {
            if (method_exists($inspection, 'getMedia')) {
                foreach ($inspection->getMedia() as $media) {
                    if ($media->created_at->lte($cutoffDate)) {
                        $this->copyMediaToArchive($media, $archive, 'archived_documents', 'Inspections');
                    }
                }
            }
        }

        // Archive training school documents
        foreach ($driver->trainingSchools as $school) {
            if (method_exists($school, 'getMedia')) {
                foreach ($school->getMedia() as $media) {
                    if ($media->created_at->lte($cutoffDate)) {
                        $this->copyMediaToArchive($media, $archive, 'archived_documents', 'Training');
                    }
                }
            }
        }

        // Archive traffic conviction documents
        foreach ($driver->trafficConvictions as $conviction) {
            if (method_exists($conviction, 'getMedia')) {
                foreach ($conviction->getMedia() as $media) {
                    if ($media->created_at->lte($cutoffDate)) {
                        $this->copyMediaToArchive($media, $archive, 'archived_documents', 'Traffic Violations');
                    }
                }
            }
        }
    }

    /**
     * Restore a driver from an archive (for rollback).
     */
    public function restoreFromArchive(DriverArchive $archive): UserDriverDetail
    {
        $driver = UserDriverDetail::find($archive->original_user_driver_detail_id);
        
        if (!$driver) {
            throw new \Exception("Original driver record not found for archive ID: {$archive->id}");
        }

        $originalCarrierId = $archive->carrier_id;
        $personalInfo = $archive->driver_data_snapshot;

        $driver->update([
            'carrier_id' => $originalCarrierId,
            'status' => $personalInfo['status'] ?? UserDriverDetail::STATUS_ACTIVE,
            'hire_date' => $personalInfo['hire_date'] ? \Carbon\Carbon::parse($personalInfo['hire_date']) : null,
            'termination_date' => null,
        ]);

        $archive->update(['status' => DriverArchive::STATUS_RESTORED]);

        Log::info('Driver restored from archive', [
            'archive_id' => $archive->id,
            'driver_id' => $driver->id,
            'carrier_id' => $originalCarrierId,
        ]);

        return $driver;
    }

    /**
     * Delete an archive (only for rollback within grace period).
     */
    public function deleteArchive(DriverArchive $archive): void
    {
        $archive->clearMediaCollection('archived_documents');
        $archive->clearMediaCollection('archived_profile_photo');
        $archive->clearMediaCollection('archived_licenses');

        $archiveId = $archive->id;
        $archive->delete();

        Log::info('Driver archive deleted', ['archive_id' => $archiveId]);
    }
}
