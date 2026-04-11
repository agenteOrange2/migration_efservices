<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Admin\Driver\MedicalRecordsController;
use App\Http\Controllers\Carrier\Concerns\ResolvesCarrierContext;
use App\Models\Admin\Driver\DriverMedicalQualification;
use App\Models\Carrier;
use App\Models\UserDriverDetail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class CarrierMedicalRecordsController extends MedicalRecordsController
{
    use ResolvesCarrierContext;

    public function index(Request $request): Response
    {
        $carrier = $this->resolveCarrier();
        $filters = [
            'search_term' => trim((string) $request->input('search_term', '')),
            'carrier_filter' => '',
            'driver_filter' => (string) $request->input('driver_filter', ''),
            'date_from' => (string) $request->input('date_from', ''),
            'date_to' => (string) $request->input('date_to', ''),
            'tab' => (string) $request->input('tab', 'all'),
            'sort_field' => (string) $request->input('sort_field', 'created_at'),
            'sort_direction' => (string) $request->input('sort_direction', 'desc'),
        ];

        $query = DriverMedicalQualification::query()
            ->with(['userDriverDetail.user:id,name,email', 'userDriverDetail.carrier:id,name'])
            ->whereHas('userDriverDetail', fn ($builder) => $builder->where('carrier_id', $carrier->id));

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

        $statsBase = DriverMedicalQualification::query()
            ->whereHas('userDriverDetail', fn ($builder) => $builder->where('carrier_id', $carrier->id));

        return Inertia::render('carrier/medical-records/Index', [
            'medicalRecords' => $records,
            'drivers' => $this->carrierDriverOptions((int) $carrier->id),
            'carriers' => [self::carrierOption($carrier)],
            'filters' => $filters,
            'stats' => [
                'total' => (clone $statsBase)->count(),
                'active' => (clone $statsBase)->where('medical_card_expiration_date', '>', now()->addDays(30))->count(),
                'expiring' => (clone $statsBase)->whereBetween('medical_card_expiration_date', [now()->startOfDay(), now()->copy()->addDays(30)->endOfDay()])->count(),
                'expired' => (clone $statsBase)->where('medical_card_expiration_date', '<', now()->startOfDay())->count(),
            ],
            'carrier' => self::carrierOption($carrier),
            'isCarrierContext' => true,
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function create(): Response
    {
        $carrier = $this->resolveCarrier();

        return Inertia::render('carrier/medical-records/Create', [
            'carriers' => [self::carrierOption($carrier)],
            'drivers' => $this->carrierDriverOptions((int) $carrier->id),
            'carrier' => self::carrierOption($carrier),
            'isCarrierContext' => true,
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $carrierId = (int) $this->resolveCarrier()->id;
        $validated = $this->validatePayload($request);
        $this->ensureCarrierOwnsDriver((int) $validated['user_driver_detail_id'], $carrierId);

        DB::transaction(function () use ($request, $validated) {
            $record = DriverMedicalQualification::create($this->recordPayload($request, $validated));
            $this->syncMedia($record, $request);
        });

        return redirect()
            ->route('carrier.medical-records.index')
            ->with('success', 'Medical record created successfully.');
    }

    public function show(DriverMedicalQualification $medical_record): Response
    {
        $this->authorizeCarrierRecord($medical_record);
        $medical_record->load(['userDriverDetail.user:id,name,email', 'userDriverDetail.carrier:id,name', 'media']);

        $documents = $medical_record->getMedia('medical_documents')
            ->map(fn (Media $media) => [
                'id' => $media->id,
                'file_name' => $media->file_name,
                'preview_url' => $media->getUrl(),
                'size_label' => $media->human_readable_size,
                'mime_type' => $media->mime_type,
                'file_type' => $this->resolveFileType($media),
                'created_at_display' => $media->created_at?->format('n/j/Y g:i A'),
            ])
            ->values();

        return Inertia::render('carrier/medical-records/Show', [
            'record' => [
                'id' => $medical_record->id,
                'driver_name' => $this->driverFullName($medical_record->userDriverDetail),
                'driver_email' => $medical_record->userDriverDetail?->user?->email,
                'carrier_name' => $medical_record->userDriverDetail?->carrier?->name,
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
                'medical_card_file' => $this->mediaPayload($medical_record, 'medical_card'),
                'social_security_card_file' => $this->mediaPayload($medical_record, 'social_security_card'),
                'documents' => $documents,
                'document_counts' => [
                    'total' => $medical_record->getMedia()->count(),
                    'medical_card' => $medical_record->getMedia('medical_card')->count(),
                    'social_security_card' => $medical_record->getMedia('social_security_card')->count(),
                    'medical_documents' => $medical_record->getMedia('medical_documents')->count(),
                ],
            ],
            'routeNames' => $this->routeNames(),
            'isCarrierContext' => true,
        ]);
    }

    public function edit(DriverMedicalQualification $medical_record): Response
    {
        $this->authorizeCarrierRecord($medical_record);
        $carrier = $this->resolveCarrier();
        $medical_record->load(['userDriverDetail.user:id,name,email', 'userDriverDetail.carrier:id,name']);

        return Inertia::render('carrier/medical-records/Edit', [
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
            'carriers' => [self::carrierOption($carrier)],
            'drivers' => $this->carrierDriverOptions((int) $carrier->id),
            'carrier' => self::carrierOption($carrier),
            'isCarrierContext' => true,
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function update(Request $request, DriverMedicalQualification $medical_record): RedirectResponse
    {
        $this->authorizeCarrierRecord($medical_record);
        $carrierId = (int) $this->resolveCarrier()->id;
        $validated = $this->validatePayload($request, $medical_record->id);
        $this->ensureCarrierOwnsDriver((int) $validated['user_driver_detail_id'], $carrierId);

        DB::transaction(function () use ($request, $validated, $medical_record) {
            $medical_record->update($this->recordPayload($request, $validated));
            $this->syncMedia($medical_record, $request);
        });

        return redirect()
            ->route('carrier.medical-records.show', $medical_record)
            ->with('success', 'Medical record updated successfully.');
    }

    public function destroy(DriverMedicalQualification $medical_record): RedirectResponse
    {
        $this->authorizeCarrierRecord($medical_record);

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
            ->route('carrier.medical-records.index')
            ->with('success', 'Medical record deleted successfully.');
    }

    public function showDocuments(Request $request, DriverMedicalQualification $medical_record): Response
    {
        $this->authorizeCarrierRecord($medical_record);
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
                    ->orWhere('mime_type', 'like', $searchTerm);
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
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

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

        return Inertia::render('carrier/medical-records/Documents', [
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
            'routeNames' => $this->routeNames(),
            'isCarrierContext' => true,
        ]);
    }

    public function destroyMedia(Media $media): RedirectResponse
    {
        abort_unless(
            $media->model_type === DriverMedicalQualification::class
            && in_array($media->collection_name, ['medical_card', 'medical_documents', 'social_security_card'], true),
            404
        );

        /** @var DriverMedicalQualification|null $record */
        $record = DriverMedicalQualification::query()->find($media->model_id);
        abort_unless($record, 404);
        $this->authorizeCarrierRecord($record);

        $media->delete();

        return back()->with('success', 'Medical document deleted successfully.');
    }

    protected function carrierDriverOptions(int $carrierId)
    {
        return UserDriverDetail::query()
            ->with(['user:id,name,email', 'carrier:id,name'])
            ->where('carrier_id', $carrierId)
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

    protected function ensureCarrierOwnsDriver(int $driverId, int $carrierId): void
    {
        $exists = UserDriverDetail::query()
            ->where('id', $driverId)
            ->where('carrier_id', $carrierId)
            ->exists();

        abort_unless($exists, 403);
    }

    protected function authorizeCarrierRecord(DriverMedicalQualification $record): void
    {
        $record->loadMissing('userDriverDetail');
        abort_unless((int) $record->userDriverDetail?->carrier_id === (int) $this->resolveCarrierId(), 403);
    }

    protected function routeNames(): array
    {
        return [
            'index' => 'carrier.medical-records.index',
            'create' => 'carrier.medical-records.create',
            'store' => 'carrier.medical-records.store',
            'show' => 'carrier.medical-records.show',
            'edit' => 'carrier.medical-records.edit',
            'update' => 'carrier.medical-records.update',
            'destroy' => 'carrier.medical-records.destroy',
            'documentsShow' => 'carrier.medical-records.documents.show',
            'mediaDestroy' => 'carrier.medical-records.media.destroy',
        ];
    }

    protected static function carrierOption(Carrier $carrier): array
    {
        return [
            'id' => $carrier->id,
            'name' => $carrier->name,
        ];
    }
}
