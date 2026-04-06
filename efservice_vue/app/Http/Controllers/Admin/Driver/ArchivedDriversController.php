<?php

namespace App\Http\Controllers\Admin\Driver;

use App\Http\Controllers\Controller;
use App\Models\Carrier;
use App\Models\DriverArchive;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ArchivedDriversController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'carrier_id' => (string) $request->input('carrier_id', ''),
            'date_from' => (string) $request->input('date_from', ''),
            'date_to' => (string) $request->input('date_to', ''),
            'archive_reason' => (string) $request->input('archive_reason', ''),
            'sort_field' => (string) $request->input('sort_field', 'archived_at'),
            'sort_direction' => (string) $request->input('sort_direction', 'desc'),
        ];

        $accessibleQuery = $this->accessibleArchivesQuery()
            ->with(['carrier:id,name', 'migrationRecord.targetCarrier:id,name']);
        $statsQuery = $this->accessibleArchivesQuery(false);

        $query = clone $accessibleQuery;

        if ($filters['search'] !== '') {
            $query->searchByName($filters['search']);
        }

        if ($this->isSuperadmin() && $filters['carrier_id'] !== '') {
            $query->forCarrier((int) $filters['carrier_id']);
        }

        if ($filters['date_from'] !== '') {
            $query->whereDate('archived_at', '>=', $this->toDbDate($filters['date_from']));
        }

        if ($filters['date_to'] !== '') {
            $query->whereDate('archived_at', '<=', $this->toDbDate($filters['date_to']));
        }

        if ($filters['archive_reason'] !== '') {
            $query->byReason($filters['archive_reason']);
        }

        $allowedSortFields = ['archived_at', 'created_at', 'status', 'archive_reason'];
        $sortField = in_array($filters['sort_field'], $allowedSortFields, true) ? $filters['sort_field'] : 'archived_at';
        $sortDirection = $filters['sort_direction'] === 'asc' ? 'asc' : 'desc';

        $archives = $query
            ->orderBy($sortField, $sortDirection)
            ->paginate(15)
            ->withQueryString();

        $archives->through(fn (DriverArchive $archive) => $this->transformArchiveRow($archive));

        return Inertia::render('admin/drivers/archived/Index', [
            'archives' => $archives,
            'filters' => $filters,
            'carriers' => $this->carrierOptions(),
            'archiveReasons' => [
                ['value' => DriverArchive::REASON_MIGRATION, 'label' => 'Migration'],
                ['value' => DriverArchive::REASON_TERMINATION, 'label' => 'Termination'],
                ['value' => DriverArchive::REASON_MANUAL, 'label' => 'Manual Archive'],
            ],
            'stats' => [
                'total' => (clone $accessibleQuery)->count(),
                'migration' => (clone $accessibleQuery)->where('archive_reason', DriverArchive::REASON_MIGRATION)->count(),
                'termination' => (clone $accessibleQuery)->where('archive_reason', DriverArchive::REASON_TERMINATION)->count(),
                'restored' => (clone $statsQuery)->where('status', DriverArchive::STATUS_RESTORED)->count(),
            ],
            'isSuperadmin' => $this->isSuperadmin(),
        ]);
    }

    public function show(DriverArchive $archive): Response
    {
        $archive->load([
            'carrier:id,name,dot_number,mc_number',
            'user:id,name,email',
            'originalDriverDetail.medicalQualification',
            'migrationRecord.targetCarrier:id,name',
            'migrationRecord.sourceCarrier:id,name',
            'migrationRecord.migratedByUser:id,name',
        ]);

        abort_unless($this->canViewArchive($archive), 403);

        $documentsByCategory = collect($archive->getDocumentsByCategory())
            ->values()
            ->map(function (array $category) {
                return [
                    'category' => $category['category'],
                    'count' => (int) ($category['count'] ?? 0),
                    'documents' => collect($category['documents'] ?? [])->map(function (array $document) {
                        return [
                            'name' => $document['name'] ?? 'Document',
                            'url' => $document['url'] ?? null,
                            'size' => (int) ($document['size'] ?? 0),
                            'mime_type' => $document['mime_type'] ?? null,
                            'created_at' => $document['created_at'] ?? null,
                        ];
                    })->values()->all(),
                ];
            })
            ->all();

        $medicalDocuments = collect($documentsByCategory)
            ->firstWhere('category', 'Medical');

        return Inertia::render('admin/drivers/archived/Detail', [
            'archive' => [
                'id' => $archive->id,
                'full_name' => $archive->full_name,
                'email' => $archive->email,
                'phone' => $archive->phone,
                'carrier_name' => $archive->carrier?->name,
                'carrier_dot' => $archive->carrier?->dot_number,
                'carrier_mc' => $archive->carrier?->mc_number,
                'archived_at' => $archive->archived_at?->toIso8601String(),
                'archive_reason' => $archive->archive_reason,
                'status' => $archive->status,
                'document_count' => $archive->getDocumentCount(),
                'profile_photo_url' => $archive->getFirstMediaUrl('archived_profile_photo') ?: ($archive->driver_data_snapshot['profile_photo_url'] ?? null),
            ],
            'sections' => [
                'personal' => $this->personalSnapshot($archive),
                'licenses' => $this->licensesSnapshot($archive),
                'medical' => $this->medicalSnapshot($archive),
                'medical_documents' => $medicalDocuments['documents'] ?? [],
                'employment' => $this->employmentSnapshot($archive),
                'training' => $this->trainingSnapshot($archive),
                'testing' => $this->testingSnapshot($archive),
                'safety' => [
                    'accidents' => $this->accidentsSnapshot($archive),
                    'convictions' => $this->convictionsSnapshot($archive),
                    'inspections' => $this->inspectionsSnapshot($archive),
                ],
                'hos' => $this->hosSnapshot($archive),
                'vehicles' => $this->vehiclesSnapshot($archive),
                'documents' => $documentsByCategory,
                'migration' => $this->migrationSnapshot($archive),
            ],
            'stats' => [
                'licenses' => count($this->licensesSnapshot($archive)),
                'medical' => count($this->medicalSnapshot($archive)),
                'employment' => count($this->employmentSnapshot($archive)),
                'training' => count($this->trainingSnapshot($archive)),
                'testing' => count($this->testingSnapshot($archive)),
                'safety' => count($this->accidentsSnapshot($archive)) + count($this->convictionsSnapshot($archive)) + count($this->inspectionsSnapshot($archive)),
            ],
        ]);
    }

    protected function accessibleArchivesQuery(bool $onlyArchived = true): Builder
    {
        $query = DriverArchive::query();

        if ($onlyArchived) {
            $query->archived();
        }

        if (! $this->isSuperadmin()) {
            $carrierId = auth()->user()?->carrierDetails?->carrier_id;
            $query->where('carrier_id', $carrierId ?: 0);
        }

        return $query;
    }

    protected function canViewArchive(DriverArchive $archive): bool
    {
        if ($this->isSuperadmin()) {
            return true;
        }

        $carrierId = auth()->user()?->carrierDetails?->carrier_id;

        return $carrierId && (int) $archive->carrier_id === (int) $carrierId;
    }

    protected function isSuperadmin(): bool
    {
        return (bool) auth()->user()?->hasRole('superadmin');
    }

    protected function transformArchiveRow(DriverArchive $archive): array
    {
        $name = $archive->full_name ?: 'Unknown Driver';

        return [
            'id' => $archive->id,
            'full_name' => $name,
            'email' => $archive->email,
            'carrier_name' => $archive->carrier?->name ?? 'N/A',
            'archived_at_display' => $archive->archived_at?->format('n/j/Y'),
            'archived_time_display' => $archive->archived_at?->format('g:i A'),
            'archive_reason' => $archive->archive_reason,
            'status' => $archive->status,
            'migration_target_name' => $archive->migrationRecord?->targetCarrier?->name,
            'document_count' => $archive->getDocumentCount(),
            'initials' => collect(explode(' ', $name))
                ->filter()
                ->take(2)
                ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
                ->implode(''),
        ];
    }

    protected function personalSnapshot(DriverArchive $archive): array
    {
        $data = $archive->driver_data_snapshot ?? [];
        $personal = [
            'full_name' => trim(implode(' ', array_filter([
                $data['name'] ?? null,
                $data['middle_name'] ?? null,
                $data['last_name'] ?? null,
            ]))) ?: null,
            'email_address' => $data['email'] ?? null,
            'phone_number' => $data['phone'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'ssn_last_four' => $data['ssn_last_four'] ?? null,
            'driver_license_number' => $data['driver_license_number'] ?? null,
            'driver_license_state' => $data['driver_license_state'] ?? null,
            'driver_license_expiration' => $data['driver_license_expiration'] ?? null,
            'street_address' => $data['address'] ?? $data['address_line1'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'zip_code' => $data['zip'] ?? ($data['zip_code'] ?? null),
            'contact_name' => $data['emergency_contact_name'] ?? null,
            'contact_phone' => $data['emergency_contact_phone'] ?? null,
            'relationship' => $data['emergency_contact_relationship'] ?? null,
        ];

        return array_filter($personal, fn ($value) => ! is_null($value) && $value !== '');
    }

    protected function licensesSnapshot(DriverArchive $archive): array
    {
        return collect($archive->licenses_snapshot ?? [])
            ->map(fn (array $license) => [
                'license_number' => $license['license_number'] ?? null,
                'license_type' => $license['license_type'] ?? 'CDL',
                'state' => $license['state'] ?? ($license['state_of_issue'] ?? null),
                'class' => $license['class'] ?? ($license['license_class'] ?? null),
                'expiration_date' => $license['expiration_date'] ?? null,
                'issue_date' => $license['issue_date'] ?? null,
                'status' => $license['status'] ?? null,
                'endorsements' => $license['endorsements'] ?? [],
                'restrictions' => $license['restrictions'] ?? [],
            ])
            ->values()
            ->all();
    }

    protected function medicalSnapshot(DriverArchive $archive): array
    {
        $medical = $archive->medical_snapshot;
        $fallbackMedical = $archive->originalDriverDetail?->medicalQualification;
        $fallbackSsn = $fallbackMedical?->social_security_number;

        if (empty($medical) && $fallbackMedical) {
            $medical = [
                'social_security_number' => $fallbackMedical->social_security_number,
                'hire_date' => $fallbackMedical->hire_date?->format('Y-m-d'),
                'location' => $fallbackMedical->location,
                'is_suspended' => $fallbackMedical->is_suspended,
                'suspension_date' => $fallbackMedical->suspension_date?->format('Y-m-d'),
                'is_terminated' => $fallbackMedical->is_terminated,
                'termination_date' => $fallbackMedical->termination_date?->format('Y-m-d'),
                'expiration_date' => $fallbackMedical->medical_card_expiration_date?->format('Y-m-d'),
                'medical_card_expiration_date' => $fallbackMedical->medical_card_expiration_date?->format('Y-m-d'),
                'medical_examiner_name' => $fallbackMedical->medical_examiner_name,
                'medical_examiner_registry_number' => $fallbackMedical->medical_examiner_registry_number,
                'status' => $fallbackMedical->status,
            ];
        }

        if (empty($medical)) {
            return [];
        }

        if (isset($medical['id']) || isset($medical['exam_date']) || isset($medical['expiration_date'])) {
            return [[
                'social_security_number' => $medical['social_security_number'] ?? $fallbackSsn,
                'hire_date' => $medical['hire_date'] ?? null,
                'location' => $medical['location'] ?? null,
                'is_suspended' => (bool) ($medical['is_suspended'] ?? false),
                'suspension_date' => $medical['suspension_date'] ?? null,
                'is_terminated' => (bool) ($medical['is_terminated'] ?? false),
                'termination_date' => $medical['termination_date'] ?? null,
                'exam_date' => $medical['exam_date'] ?? null,
                'expiration_date' => $medical['expiration_date'] ?? ($medical['medical_card_expiration_date'] ?? null),
                'exam_type' => $medical['exam_type'] ?? 'DOT Medical Examination',
                'examiner_name' => $medical['examiner_name'] ?? ($medical['medical_examiner_name'] ?? null),
                'examiner_registry' => $medical['examiner_registry_number'] ?? ($medical['medical_examiner_registry'] ?? $medical['medical_examiner_registry_number'] ?? null),
                'certificate_number' => $medical['certificate_number'] ?? null,
                'examiner_license' => $medical['examiner_license'] ?? null,
                'certification_type' => $medical['certification_type'] ?? null,
                'has_variance' => (bool) ($medical['has_variance'] ?? false),
                'restrictions' => $medical['restrictions'] ?? null,
                'status' => $medical['status'] ?? null,
                'notes' => $medical['notes'] ?? null,
            ]];
        }

        return collect($medical)->map(fn (array $record) => [
            'social_security_number' => $record['social_security_number'] ?? $fallbackSsn,
            'hire_date' => $record['hire_date'] ?? null,
            'location' => $record['location'] ?? null,
            'is_suspended' => (bool) ($record['is_suspended'] ?? false),
            'suspension_date' => $record['suspension_date'] ?? null,
            'is_terminated' => (bool) ($record['is_terminated'] ?? false),
            'termination_date' => $record['termination_date'] ?? null,
            'exam_date' => $record['exam_date'] ?? null,
            'expiration_date' => $record['expiration_date'] ?? ($record['medical_card_expiration_date'] ?? null),
            'exam_type' => $record['exam_type'] ?? 'DOT Medical Examination',
            'examiner_name' => $record['examiner_name'] ?? ($record['medical_examiner_name'] ?? null),
            'examiner_registry' => $record['examiner_registry_number'] ?? ($record['medical_examiner_registry'] ?? $record['medical_examiner_registry_number'] ?? null),
            'certificate_number' => $record['certificate_number'] ?? null,
            'examiner_license' => $record['examiner_license'] ?? null,
            'certification_type' => $record['certification_type'] ?? null,
            'has_variance' => (bool) ($record['has_variance'] ?? false),
            'restrictions' => $record['restrictions'] ?? null,
            'status' => $record['status'] ?? null,
            'notes' => $record['notes'] ?? null,
        ])->values()->all();
    }

    protected function employmentSnapshot(DriverArchive $archive): array
    {
        $snapshot = $archive->employment_history_snapshot ?? [];
        $employmentData = [];

        foreach ($snapshot['employment_companies'] ?? [] as $company) {
            $employmentData[] = [
                'employer_name' => $company['company_name'] ?? 'Unknown Employer',
                'position' => $company['position'] ?? ($company['positions_held'] ?? null),
                'start_date' => $company['start_date'] ?? ($company['employed_from'] ?? null),
                'end_date' => $company['end_date'] ?? ($company['employed_to'] ?? null),
                'address' => $company['address'] ?? null,
                'city' => $company['city'] ?? null,
                'state' => $company['state'] ?? null,
                'zip' => $company['zip'] ?? null,
                'phone' => $company['phone'] ?? null,
                'email' => $company['email'] ?? null,
                'fax' => $company['fax'] ?? null,
                'contact_name' => $company['contact'] ?? null,
                'reason_for_leaving' => $company['reason_for_leaving'] ?? null,
                'was_subject_to_fmcsr' => (bool) ($company['was_subject_to_fmcsr'] ?? false),
                'was_subject_to_drug_testing' => (bool) ($company['was_subject_to_drug_testing'] ?? false),
                'verification_status' => $company['verification_status'] ?? null,
                'is_verified' => ($company['verification_status'] ?? null) === 'verified',
                'verified_at' => $company['verification_date'] ?? null,
                'notes' => $company['verification_notes'] ?? null,
            ];
        }

        foreach ($snapshot['work_histories'] ?? [] as $history) {
            $employmentData[] = [
                'employer_name' => $history['employer_name'] ?? 'Work History',
                'position' => $history['position'] ?? null,
                'start_date' => $history['start_date'] ?? null,
                'end_date' => $history['end_date'] ?? null,
                'reason_for_leaving' => $history['comments'] ?? null,
            ];
        }

        foreach ($snapshot['unemployment_periods'] ?? [] as $period) {
            $employmentData[] = [
                'employer_name' => 'Unemployment Period',
                'position' => 'Unemployed',
                'start_date' => $period['start_date'] ?? null,
                'end_date' => $period['end_date'] ?? null,
                'reason_for_leaving' => $period['reason'] ?? ($period['comments'] ?? null),
            ];
        }

        return $employmentData;
    }

    protected function trainingSnapshot(DriverArchive $archive): array
    {
        $snapshot = $archive->training_snapshot ?? [];
        $trainingData = [];

        foreach ($snapshot['training_schools'] ?? [] as $school) {
            $trainingData[] = [
                'type' => 'school',
                'name' => $school['school_name'] ?? ($school['name'] ?? 'Training School'),
                'provider' => $school['school_name'] ?? null,
                'city' => $school['city'] ?? null,
                'state' => $school['state'] ?? null,
                'start_date' => $school['date_attended_from'] ?? ($school['date_start'] ?? null),
                'completion_date' => $school['date_attended_to'] ?? ($school['date_end'] ?? null),
                'status' => ! empty($school['graduated']) ? 'completed' : 'in_progress',
            ];
        }

        foreach ($snapshot['driver_trainings'] ?? [] as $training) {
            $trainingData[] = [
                'type' => 'training',
                'name' => $training['training_name'] ?? ($training['title'] ?? 'Training Course'),
                'provider' => $training['provider'] ?? null,
                'assigned_date' => $training['assigned_date'] ?? null,
                'due_date' => $training['due_date'] ?? null,
                'completion_date' => $training['completed_at'] ?? null,
                'status' => $training['status'] ?? null,
            ];
        }

        foreach ($snapshot['courses'] ?? [] as $course) {
            $trainingData[] = [
                'type' => 'course',
                'name' => $course['organization_name'] ?? 'Course',
                'provider' => $course['organization_name'] ?? null,
                'assigned_date' => $course['certification_date'] ?? null,
                'completion_date' => $course['certification_date'] ?? null,
                'due_date' => $course['expiration_date'] ?? null,
                'status' => $course['status'] ?? null,
            ];
        }

        return $trainingData;
    }

    protected function testingSnapshot(DriverArchive $archive): array
    {
        return collect($archive->testing_snapshot ?? [])
            ->map(fn (array $testing) => [
                'test_date' => $testing['test_date'] ?? null,
                'test_type' => $testing['test_type'] ?? null,
                'result' => $testing['result'] ?? ($testing['test_result'] ?? null),
                'status' => $testing['status'] ?? null,
                'test_reason' => $testing['reason'] ?? ($testing['test_reason'] ?? null),
                'collection_site' => $testing['location'] ?? ($testing['collection_site'] ?? null),
                'specimen_id' => $testing['specimen_id'] ?? null,
                'laboratory' => $testing['laboratory'] ?? null,
                'mro_name' => $testing['mro'] ?? ($testing['mro_name'] ?? null),
                'result_date' => $testing['result_date'] ?? null,
                'substances_tested' => $testing['substances_tested'] ?? null,
                'follow_up_required' => (bool) ($testing['follow_up_required'] ?? false),
                'follow_up_notes' => $testing['follow_up_notes'] ?? null,
                'administered_by' => $testing['administered_by'] ?? null,
                'location' => $testing['location'] ?? null,
                'next_test_due' => $testing['next_test_due'] ?? null,
                'bill_to' => $testing['bill_to'] ?? null,
                'notes' => $testing['notes'] ?? null,
            ])
            ->values()
            ->all();
    }

    protected function accidentsSnapshot(DriverArchive $archive): array
    {
        return collect($archive->accidents_snapshot ?? [])
            ->map(fn (array $accident) => [
                'accident_date' => $accident['accident_date'] ?? null,
                'location' => $accident['location'] ?? null,
                'nature_of_accident' => $accident['nature_of_accident'] ?? null,
                'description' => $accident['description'] ?? ($accident['comments'] ?? null),
                'fatalities' => (bool) ($accident['had_fatalities'] ?? false),
                'fatality_count' => (int) ($accident['number_of_fatalities'] ?? 0),
                'injuries' => (bool) ($accident['had_injuries'] ?? false),
                'injury_count' => (int) ($accident['number_of_injuries'] ?? 0),
                'had_fatalities' => (bool) ($accident['had_fatalities'] ?? false),
                'had_injuries' => (bool) ($accident['had_injuries'] ?? false),
                'number_of_fatalities' => (int) ($accident['number_of_fatalities'] ?? 0),
                'number_of_injuries' => (int) ($accident['number_of_injuries'] ?? 0),
                'comments' => $accident['comments'] ?? null,
            ])
            ->values()
            ->all();
    }

    protected function convictionsSnapshot(DriverArchive $archive): array
    {
        return collect($archive->convictions_snapshot ?? [])
            ->map(fn (array $conviction) => [
                'violation_type' => $conviction['charge'] ?? ($conviction['violation_type'] ?? 'Traffic Violation'),
                'conviction_date' => $conviction['conviction_date'] ?? ($conviction['violation_date'] ?? null),
                'location' => $conviction['location'] ?? null,
                'state' => $conviction['state'] ?? null,
                'penalty' => $conviction['penalty'] ?? null,
                'description' => $conviction['description'] ?? null,
            ])
            ->values()
            ->all();
    }

    protected function inspectionsSnapshot(DriverArchive $archive): array
    {
        return collect($archive->inspections_snapshot ?? [])
            ->map(fn (array $inspection) => [
                'inspection_date' => $inspection['inspection_date'] ?? null,
                'inspection_type' => $inspection['inspection_type'] ?? ($inspection['type'] ?? null),
                'inspection_level' => $inspection['inspection_level'] ?? ($inspection['level'] ?? null),
                'level' => $inspection['level'] ?? ($inspection['inspection_level'] ?? null),
                'location' => $inspection['location'] ?? null,
                'status' => $inspection['status'] ?? null,
                'result' => $inspection['result'] ?? ($inspection['status'] ?? null),
                'inspector_name' => $inspection['inspector_name'] ?? null,
                'violations' => $inspection['violations'] ?? ($inspection['defects_found'] ?? null),
                'notes' => $inspection['notes'] ?? ($inspection['description'] ?? null),
            ])
            ->values()
            ->all();
    }

    protected function hosSnapshot(DriverArchive $archive): array
    {
        $snapshot = $archive->hos_snapshot ?? [];

        return [
            'entries_count' => (int) ($snapshot['total_entries'] ?? 0),
            'violations_count' => (int) ($snapshot['violations_count'] ?? 0),
            'last_entry_date' => $snapshot['last_entry_date'] ?? null,
            'total_drive_hours' => $snapshot['total_drive_hours'] ?? null,
        ];
    }

    protected function vehiclesSnapshot(DriverArchive $archive): array
    {
        return collect($archive->vehicle_assignments_snapshot ?? [])
            ->map(fn (array $assignment) => [
                'vehicle_make' => $assignment['vehicle_make'] ?? null,
                'vehicle_model' => $assignment['vehicle_model'] ?? null,
                'vehicle_year' => $assignment['vehicle_year'] ?? null,
                'unit_number' => $assignment['vehicle_unit_number'] ?? null,
                'vin' => $assignment['vin'] ?? null,
                'license_plate' => $assignment['license_plate'] ?? null,
                'plate_state' => $assignment['plate_state'] ?? null,
                'vehicle_type' => $assignment['vehicle_type'] ?? null,
                'odometer_start' => $assignment['odometer_start'] ?? null,
                'odometer_end' => $assignment['odometer_end'] ?? null,
                'is_primary' => (bool) ($assignment['is_primary'] ?? false),
                'end_reason' => $assignment['end_reason'] ?? null,
                'notes' => $assignment['notes'] ?? null,
                'driver_type' => $assignment['driver_type'] ?? null,
                'start_date' => $assignment['start_date'] ?? null,
                'end_date' => $assignment['end_date'] ?? null,
                'status' => $assignment['status'] ?? null,
            ])
            ->values()
            ->all();
    }

    protected function migrationSnapshot(DriverArchive $archive): ?array
    {
        if (! $archive->migrationRecord) {
            return null;
        }

        $record = $archive->migrationRecord;

        return [
            'migrated_at' => $record->migrated_at?->toIso8601String(),
            'source_carrier' => $record->sourceCarrier?->name,
            'target_carrier' => $record->targetCarrier?->name,
            'reason' => $record->reason,
            'notes' => $record->notes,
            'migrated_by' => $record->migratedByUser?->name,
            'status' => $record->status,
            'rolled_back' => $record->isRolledBack(),
            'rolled_back_at' => $record->rolled_back_at?->toIso8601String(),
            'rollback_reason' => $record->rollback_reason,
        ];
    }

    protected function carrierOptions()
    {
        if (! $this->isSuperadmin()) {
            return [];
        }

        return Carrier::query()
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Carrier $carrier) => [
                'id' => $carrier->id,
                'name' => $carrier->name,
            ]);
    }

    protected function toDbDate(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            return $value;
        }

        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $value, $matches) === 1) {
            return sprintf('%04d-%02d-%02d', (int) $matches[3], (int) $matches[1], (int) $matches[2]);
        }

        return $value;
    }
}
