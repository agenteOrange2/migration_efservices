<?php

namespace App\Http\Controllers\Admin\Driver;

use App\Http\Controllers\Controller;
use App\Models\Admin\Driver\DriverApplication;
use App\Models\Admin\Driver\DriverMedicalQualification;
use App\Models\Carrier;
use App\Models\UserDriverDetail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MedicalRecordsController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'search_term' => trim((string) $request->input('search_term', '')),
            'carrier_filter' => $request->input('carrier_filter', ''),
            'driver_filter' => $request->input('driver_filter', ''),
            'date_from' => $request->input('date_from', ''),
            'date_to' => $request->input('date_to', ''),
            'tab' => $request->input('tab', 'all'),
            'sort_field' => $request->input('sort_field', 'created_at'),
            'sort_direction' => $request->input('sort_direction', 'desc'),
        ];

        $query = DriverMedicalQualification::query()
            ->with(['userDriverDetail.user:id,name,email', 'userDriverDetail.carrier:id,name']);

        $this->applyStatusTab($query, $filters['tab']);

        if ($filters['search_term'] !== '') {
            $searchTerm = '%' . $filters['search_term'] . '%';
            $query->where(function ($builder) use ($searchTerm) {
                $builder
                    ->where('social_security_number', 'like', $searchTerm)
                    ->orWhere('medical_examiner_name', 'like', $searchTerm)
                    ->orWhere('medical_examiner_registry_number', 'like', $searchTerm)
                    ->orWhere('location', 'like', $searchTerm)
                    ->orWhereHas('userDriverDetail.user', function ($userQuery) use ($searchTerm) {
                        $userQuery
                            ->where('name', 'like', $searchTerm)
                            ->orWhere('email', 'like', $searchTerm);
                    })
                    ->orWhereHas('userDriverDetail', function ($driverQuery) use ($searchTerm) {
                        $driverQuery
                            ->where('middle_name', 'like', $searchTerm)
                            ->orWhere('last_name', 'like', $searchTerm)
                            ->orWhere('phone', 'like', $searchTerm);
                    });
            });
        }

        if ($filters['driver_filter'] !== '') {
            $query->where('user_driver_detail_id', $filters['driver_filter']);
        }

        if ($filters['carrier_filter'] !== '') {
            $query->whereHas('userDriverDetail', function ($builder) use ($filters) {
                $builder->where('carrier_id', $filters['carrier_filter']);
            });
        }

        if ($filters['date_from'] !== '') {
            $query->whereDate('created_at', '>=', $this->toDbDate($filters['date_from']));
        }

        if ($filters['date_to'] !== '') {
            $query->whereDate('created_at', '<=', $this->toDbDate($filters['date_to']));
        }

        $allowedSortFields = ['created_at', 'medical_card_expiration_date', 'medical_examiner_name'];
        $sortField = in_array($filters['sort_field'], $allowedSortFields, true) ? $filters['sort_field'] : 'created_at';
        $sortDirection = $filters['sort_direction'] === 'asc' ? 'asc' : 'desc';

        $records = $query
            ->orderBy($sortField, $sortDirection)
            ->paginate(15)
            ->withQueryString();

        $documentCounts = $this->documentCounts($records->pluck('id')->all());

        $records->through(function (DriverMedicalQualification $record) use ($documentCounts) {
            return $this->transformRecord($record, $documentCounts[$record->id] ?? 0);
        });

        return Inertia::render('admin/medical-records/Index', [
            'medicalRecords' => $records,
            'drivers' => $this->driverOptions(),
            'carriers' => Carrier::query()
                ->where('status', 1)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Carrier $carrier) => [
                    'id' => $carrier->id,
                    'name' => $carrier->name,
                ]),
            'filters' => $filters,
            'stats' => [
                'total' => DriverMedicalQualification::count(),
                'active' => DriverMedicalQualification::query()
                    ->where('medical_card_expiration_date', '>', now()->addDays(30))
                    ->count(),
                'expiring' => DriverMedicalQualification::query()
                    ->whereBetween('medical_card_expiration_date', [now()->startOfDay(), now()->copy()->addDays(30)->endOfDay()])
                    ->count(),
                'expired' => DriverMedicalQualification::query()
                    ->where('medical_card_expiration_date', '<', now()->startOfDay())
                    ->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/medical-records/Create', [
            'carriers' => Carrier::query()
                ->where('status', 1)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Carrier $carrier) => [
                    'id' => $carrier->id,
                    'name' => $carrier->name,
                ]),
            'drivers' => $this->driverOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePayload($request);

        DB::transaction(function () use ($request, $validated) {
            $record = DriverMedicalQualification::create($this->recordPayload($request, $validated));
            $this->syncMedia($record, $request);
        });

        return redirect()
            ->route('admin.medical-records.index')
            ->with('success', 'Medical record created successfully.');
    }

    public function edit(DriverMedicalQualification $medical_record): Response
    {
        $medical_record->load(['userDriverDetail.user:id,name,email', 'userDriverDetail.carrier:id,name']);

        return Inertia::render('admin/medical-records/Edit', [
            'record' => [
                'id' => $medical_record->id,
                'user_driver_detail_id' => $medical_record->user_driver_detail_id,
                'carrier_id' => $medical_record->userDriverDetail?->carrier_id,
                'social_security_number' => $medical_record->social_security_number,
                'hire_date' => $medical_record->hire_date?->format('Y-m-d'),
                'location' => $medical_record->location,
                'is_suspended' => (bool) $medical_record->is_suspended,
                'suspension_date' => $medical_record->suspension_date?->format('Y-m-d'),
                'is_terminated' => (bool) $medical_record->is_terminated,
                'termination_date' => $medical_record->termination_date?->format('Y-m-d'),
                'medical_examiner_name' => $medical_record->medical_examiner_name,
                'medical_examiner_registry_number' => $medical_record->medical_examiner_registry_number,
                'medical_card_expiration_date' => $medical_record->medical_card_expiration_date?->format('Y-m-d'),
                'driver_name' => $this->driverFullName($medical_record->userDriverDetail),
                'medical_card_file' => $this->mediaPayload($medical_record, 'medical_card'),
                'social_security_card_file' => $this->mediaPayload($medical_record, 'social_security_card'),
            ],
            'carriers' => Carrier::query()
                ->where('status', 1)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Carrier $carrier) => [
                    'id' => $carrier->id,
                    'name' => $carrier->name,
                ]),
            'drivers' => $this->driverOptions(),
        ]);
    }

    public function update(Request $request, DriverMedicalQualification $medical_record): RedirectResponse
    {
        $validated = $this->validatePayload($request, $medical_record->id);

        DB::transaction(function () use ($request, $validated, $medical_record) {
            $medical_record->update($this->recordPayload($request, $validated));
            $this->syncMedia($medical_record, $request);
        });

        return redirect()
            ->route('admin.medical-records.index')
            ->with('success', 'Medical record updated successfully.');
    }

    public function destroy(DriverMedicalQualification $medical_record): RedirectResponse
    {
        DB::transaction(function () use ($medical_record) {
            $medical_record->clearMediaCollection('medical_certificate');
            $medical_record->clearMediaCollection('test_results');
            $medical_record->clearMediaCollection('additional_documents');
            $medical_record->clearMediaCollection('medical_documents');
            $medical_record->clearMediaCollection('medical_card');
            $medical_record->clearMediaCollection('social_security_card');
            $medical_record->delete();
        });

        return redirect()
            ->route('admin.medical-records.index')
            ->with('success', 'Medical record deleted successfully.');
    }

    public function showDocuments(Request $request, DriverMedicalQualification $medical_record): Response
    {
        $medical_record->load(['userDriverDetail.user:id,name,email', 'userDriverDetail.carrier:id,name']);

        $filters = [
            'search_term' => trim((string) $request->input('search_term', '')),
            'date_from' => (string) $request->input('date_from', ''),
            'date_to' => (string) $request->input('date_to', ''),
            'collection' => (string) $request->input('collection', ''),
        ];

        $query = Media::query()
            ->where('model_type', DriverMedicalQualification::class)
            ->where('model_id', $medical_record->id)
            ->whereIn('collection_name', ['medical_card', 'medical_documents', 'social_security_card']);

        if ($filters['search_term'] !== '') {
            $searchTerm = '%' . $filters['search_term'] . '%';

            $query->where(function ($builder) use ($searchTerm) {
                $builder
                    ->where('file_name', 'like', $searchTerm)
                    ->orWhere('mime_type', 'like', $searchTerm)
                    ->orWhereHasMorph('model', [DriverMedicalQualification::class], function ($recordQuery) use ($searchTerm) {
                        $recordQuery
                            ->where('social_security_number', 'like', $searchTerm)
                            ->orWhere('medical_examiner_name', 'like', $searchTerm)
                            ->orWhereHas('userDriverDetail.user', function ($userQuery) use ($searchTerm) {
                                $userQuery
                                    ->where('name', 'like', $searchTerm)
                                    ->orWhere('email', 'like', $searchTerm);
                            });
                    });
            });
        }

        if ($filters['collection'] !== '') {
            $query->where('collection_name', $filters['collection']);
        }

        if ($filters['date_from'] !== '') {
            $query->whereDate('created_at', '>=', $this->toDbDate($filters['date_from']));
        }

        if ($filters['date_to'] !== '') {
            $query->whereDate('created_at', '<=', $this->toDbDate($filters['date_to']));
        }

        $documents = $query
            ->with('model')
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        $documents->getCollection()->loadMorph('model', [
            DriverMedicalQualification::class => ['userDriverDetail.user:id,name,email', 'userDriverDetail.carrier:id,name'],
        ]);

        $documents->through(function (Media $media) {
            return [
                'id' => $media->id,
                'file_name' => $media->file_name,
                'collection_name' => $media->collection_name,
                'collection_label' => match ($media->collection_name) {
                    'medical_card' => 'Medical Card',
                    'social_security_card' => 'Social Security Card',
                    default => 'Medical Document',
                },
                'mime_type' => $media->mime_type,
                'file_type' => $this->resolveFileType($media),
                'size' => (int) $media->size,
                'size_label' => $media->human_readable_size,
                'created_at_display' => $media->created_at?->format('n/j/Y g:i A'),
                'preview_url' => $media->getUrl(),
            ];
        });

        $statsQuery = Media::query()
            ->where('model_type', DriverMedicalQualification::class)
            ->where('model_id', $medical_record->id)
            ->whereIn('collection_name', ['medical_card', 'medical_documents', 'social_security_card']);

        return Inertia::render('admin/medical-records/Documents', [
            'documents' => $documents,
            'filters' => $filters,
            'record' => [
                'id' => $medical_record->id,
                'driver_name' => $this->driverFullName($medical_record->userDriverDetail),
                'carrier_name' => $medical_record->userDriverDetail?->carrier?->name,
                'social_security_number' => $medical_record->social_security_number,
                'medical_examiner_name' => $medical_record->medical_examiner_name,
            ],
            'stats' => [
                'total' => (clone $statsQuery)->count(),
                'medical_card' => (clone $statsQuery)->where('collection_name', 'medical_card')->count(),
                'social_security_card' => (clone $statsQuery)->where('collection_name', 'social_security_card')->count(),
                'medical_documents' => (clone $statsQuery)->where('collection_name', 'medical_documents')->count(),
            ],
        ]);
    }

    public function destroyMedia(Media $media): RedirectResponse
    {
        abort_unless(
            $media->model_type === DriverMedicalQualification::class
            && in_array($media->collection_name, ['medical_card', 'medical_documents', 'social_security_card'], true),
            404
        );

        $media->delete();

        return back()->with('success', 'Medical document deleted successfully.');
    }

    protected function validatePayload(Request $request, ?int $recordId = null): array
    {
        return $request->validate([
            'user_driver_detail_id' => [
                'required',
                'exists:user_driver_details,id',
                Rule::unique('driver_medical_qualifications', 'user_driver_detail_id')->ignore($recordId),
            ],
            'social_security_number' => 'required|string|max:255',
            'hire_date' => 'nullable|date',
            'location' => 'nullable|string|max:255',
            'is_suspended' => 'nullable|boolean',
            'suspension_date' => 'nullable|date',
            'is_terminated' => 'nullable|boolean',
            'termination_date' => 'nullable|date',
            'medical_examiner_name' => 'required|string|max:255',
            'medical_examiner_registry_number' => 'nullable|string|max:255',
            'medical_card_expiration_date' => 'nullable|date',
            'medical_card' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'social_security_card' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'medical_documents' => 'nullable|array',
            'medical_documents.*' => 'file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240',
        ]);
    }

    protected function recordPayload(Request $request, array $validated): array
    {
        $isSuspended = $request->boolean('is_suspended');
        $isTerminated = $request->boolean('is_terminated');

        return [
            'user_driver_detail_id' => $validated['user_driver_detail_id'],
            'social_security_number' => $validated['social_security_number'],
            'hire_date' => $this->toDbDate($validated['hire_date'] ?? null),
            'location' => $validated['location'] ?? null,
            'is_suspended' => $isSuspended,
            'suspension_date' => $isSuspended ? $this->toDbDate($validated['suspension_date'] ?? null) : null,
            'is_terminated' => $isTerminated,
            'termination_date' => $isTerminated ? $this->toDbDate($validated['termination_date'] ?? null) : null,
            'medical_examiner_name' => $validated['medical_examiner_name'],
            'medical_examiner_registry_number' => $validated['medical_examiner_registry_number'] ?? null,
            'medical_card_expiration_date' => $this->toDbDate($validated['medical_card_expiration_date'] ?? null),
        ];
    }

    protected function syncMedia(DriverMedicalQualification $record, Request $request): void
    {
        if ($request->hasFile('medical_card')) {
            $record->clearMediaCollection('medical_card');
            $record->addMedia($request->file('medical_card'))
                ->usingFileName('medical_card.' . ($request->file('medical_card')->getClientOriginalExtension() ?: 'jpg'))
                ->toMediaCollection('medical_card');
        }

        if ($request->hasFile('social_security_card')) {
            $record->clearMediaCollection('social_security_card');
            $record->addMedia($request->file('social_security_card'))
                ->usingFileName('social_security_card.' . ($request->file('social_security_card')->getClientOriginalExtension() ?: 'jpg'))
                ->toMediaCollection('social_security_card');
        }

        foreach ($request->file('medical_documents', []) as $document) {
            $record->addMedia($document)
                ->usingName(pathinfo($document->getClientOriginalName(), PATHINFO_FILENAME))
                ->toMediaCollection('medical_documents');
        }
    }

    protected function driverOptions()
    {
        return UserDriverDetail::query()
            ->with(['user:id,name,email', 'carrier:id,name'])
            ->whereHas('application', function ($query) {
                $query->where('status', DriverApplication::STATUS_APPROVED);
            })
            ->orderBy('carrier_id')
            ->orderBy('last_name')
            ->get()
            ->map(fn (UserDriverDetail $driver) => [
                'id' => $driver->id,
                'carrier_id' => $driver->carrier_id,
                'carrier_name' => $driver->carrier?->name,
                'name' => $this->driverFullName($driver),
                'email' => $driver->user?->email,
            ]);
    }

    protected function transformRecord(DriverMedicalQualification $record, int $documentCount): array
    {
        return [
            'id' => $record->id,
            'created_at' => $record->created_at?->toDateString(),
            'social_security_number' => $record->social_security_number,
            'medical_examiner_name' => $record->medical_examiner_name,
            'medical_examiner_registry_number' => $record->medical_examiner_registry_number,
            'medical_card_expiration_date' => $record->medical_card_expiration_date?->toDateString(),
            'status' => $record->status,
            'document_count' => $documentCount,
            'driver' => $record->userDriverDetail ? [
                'id' => $record->userDriverDetail->id,
                'name' => $this->driverFullName($record->userDriverDetail),
                'email' => $record->userDriverDetail->user?->email,
            ] : null,
            'carrier' => $record->userDriverDetail?->carrier ? [
                'id' => $record->userDriverDetail->carrier->id,
                'name' => $record->userDriverDetail->carrier->name,
            ] : null,
        ];
    }

    protected function documentCounts(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return DriverMedicalQualification::query()
            ->whereIn('id', $ids)
            ->get()
            ->mapWithKeys(fn (DriverMedicalQualification $record) => [
                $record->id => $record->getMedia()->count(),
            ])
            ->all();
    }

    protected function mediaPayload(DriverMedicalQualification $record, string $collection): ?array
    {
        $media = $record->getFirstMedia($collection);

        if (! $media) {
            return null;
        }

        return [
            'name' => $media->file_name,
            'url' => $media->getUrl(),
            'mime_type' => $media->mime_type,
            'size' => $media->size,
        ];
    }

    protected function applyStatusTab($query, string $tab): void
    {
        if ($tab === 'active') {
            $query->where('medical_card_expiration_date', '>', now()->addDays(30));
            return;
        }

        if ($tab === 'expiring') {
            $query->whereBetween('medical_card_expiration_date', [now()->startOfDay(), now()->copy()->addDays(30)->endOfDay()]);
            return;
        }

        if ($tab === 'expired') {
            $query->where('medical_card_expiration_date', '<', now()->startOfDay());
        }
    }

    protected function driverFullName(?UserDriverDetail $driver): string
    {
        if (! $driver) {
            return 'Unknown driver';
        }

        return trim(implode(' ', array_filter([
            $driver->user?->name,
            $driver->middle_name,
            $driver->last_name,
        ])));
    }

    protected function toDbDate(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $value)) {
            [$month, $day, $year] = array_map('intval', explode('/', $value));
            return sprintf('%04d-%02d-%02d', $year, $month, $day);
        }

        return $value;
    }

    protected function resolveFileType(Media $media): string
    {
        $mimeType = Str::lower((string) $media->mime_type);

        if (str_contains($mimeType, 'image/')) {
            return 'image';
        }

        if (str_contains($mimeType, 'pdf')) {
            return 'pdf';
        }

        return 'document';
    }
}
