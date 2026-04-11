<?php

namespace App\Http\Controllers\Carrier;

use App\Helpers\Constants;
use App\Http\Controllers\Admin\Driver\DriverLicenseController;
use App\Http\Controllers\Carrier\Concerns\ResolvesCarrierContext;
use App\Models\Admin\Driver\DriverLicense;
use App\Models\Admin\Driver\LicenseEndorsement;
use App\Models\Carrier;
use App\Models\UserDriverDetail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class CarrierLicenseController extends DriverLicenseController
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
            'sort_field' => (string) $request->input('sort_field', 'created_at'),
            'sort_direction' => (string) $request->input('sort_direction', 'desc'),
        ];

        $query = DriverLicense::query()
            ->with(['driverDetail.user:id,name,email', 'driverDetail.carrier:id,name'])
            ->whereHas('driverDetail', fn ($builder) => $builder->where('carrier_id', $carrier->id));

        if ($filters['search_term'] !== '') {
            $searchTerm = '%' . $filters['search_term'] . '%';
            $query->where(function ($builder) use ($searchTerm) {
                $builder
                    ->where('license_number', 'like', $searchTerm)
                    ->orWhere('license_class', 'like', $searchTerm)
                    ->orWhere('state_of_issue', 'like', $searchTerm)
                    ->orWhereHas('driverDetail.user', function ($userQuery) use ($searchTerm) {
                        $userQuery
                            ->where('name', 'like', $searchTerm)
                            ->orWhere('email', 'like', $searchTerm);
                    })
                    ->orWhereHas('driverDetail', function ($driverQuery) use ($searchTerm) {
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
            $query->whereDate('created_at', '>=', $this->parseUsDate($filters['date_from']));
        }

        if ($filters['date_to'] !== '') {
            $query->whereDate('created_at', '<=', $this->parseUsDate($filters['date_to']));
        }

        $allowedSortFields = ['created_at', 'license_number', 'expiration_date'];
        $sortField = in_array($filters['sort_field'], $allowedSortFields, true) ? $filters['sort_field'] : 'created_at';
        $sortDirection = $filters['sort_direction'] === 'asc' ? 'asc' : 'desc';

        $licenses = $query
            ->orderBy($sortField, $sortDirection)
            ->paginate(15)
            ->withQueryString();

        $documentCounts = $this->licenseDocumentCounts($licenses->pluck('id')->all());

        $licenses->through(function (DriverLicense $license) use ($documentCounts) {
            return $this->transformLicense($license, $documentCounts[$license->id] ?? 0);
        });

        return Inertia::render('carrier/licenses/Index', [
            'licenses' => $licenses,
            'drivers' => $this->carrierDriverOptions((int) $carrier->id),
            'carriers' => [self::carrierOption($carrier)],
            'filters' => $filters,
            'carrier' => self::carrierOption($carrier),
            'isCarrierContext' => true,
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function create(): Response
    {
        $carrier = $this->resolveCarrier();

        return Inertia::render('carrier/licenses/Create', [
            'carriers' => [self::carrierOption($carrier)],
            'drivers' => $this->carrierDriverOptions((int) $carrier->id),
            'states' => Constants::usStates(),
            'endorsements' => $this->endorsementOptions(),
            'carrier' => self::carrierOption($carrier),
            'isCarrierContext' => true,
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'expiration_date' => $this->parseUsDate((string) $request->input('expiration_date')),
        ]);

        $carrierId = (int) $this->resolveCarrier()->id;
        $validated = $request->validate([
            'user_driver_detail_id' => 'required|exists:user_driver_details,id',
            'license_number' => 'required|string|max:255',
            'license_class' => 'required|string|max:255',
            'state_of_issue' => 'required|string|max:5',
            'expiration_date' => 'required|date|after:today',
            'is_cdl' => 'nullable|boolean',
            'is_primary' => 'nullable|boolean',
            'endorsement_ids' => 'nullable|array',
            'endorsement_ids.*' => 'integer|exists:license_endorsements,id',
            'license_front_image' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'license_back_image' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'license_documents' => 'nullable|array',
            'license_documents.*' => 'file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240',
        ]);

        $this->ensureCarrierOwnsDriver((int) $validated['user_driver_detail_id'], $carrierId);

        DB::transaction(function () use ($request, $validated) {
            $license = DriverLicense::create([
                'user_driver_detail_id' => $validated['user_driver_detail_id'],
                'license_number' => $validated['license_number'],
                'license_class' => $validated['license_class'],
                'state_of_issue' => $validated['state_of_issue'],
                'expiration_date' => $validated['expiration_date'],
                'is_cdl' => $request->boolean('is_cdl'),
                'is_primary' => $this->resolvePrimaryFlag(
                    (int) $validated['user_driver_detail_id'],
                    $request->boolean('is_primary')
                ),
            ]);

            $this->syncPrimaryLicense($license);
            $license->endorsements()->sync($request->boolean('is_cdl') ? ($validated['endorsement_ids'] ?? []) : []);
            $this->syncMedia($license, $request);
        });

        return redirect()
            ->route('carrier.licenses.index')
            ->with('success', 'License created successfully.');
    }

    public function show(DriverLicense $license): Response
    {
        $this->authorizeCarrierLicense($license);
        $license->load(['driverDetail.user:id,name,email', 'driverDetail.carrier:id,name', 'endorsements:id,code,name']);

        $additionalDocuments = $license->getMedia('license_documents')
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

        return Inertia::render('carrier/licenses/Show', [
            'license' => [
                'id' => $license->id,
                'license_number' => $license->license_number,
                'license_class' => $license->license_class,
                'state_of_issue' => $license->state_of_issue,
                'expiration_date' => $license->expiration_date?->format('Y-m-d'),
                'restrictions' => $license->restrictions,
                'is_cdl' => (bool) $license->is_cdl,
                'is_primary' => (bool) $license->is_primary,
                'front_url' => $license->getFirstMediaUrl('license_front') ?: null,
                'back_url' => $license->getFirstMediaUrl('license_back') ?: null,
                'document_count' => (int) (($license->getFirstMedia('license_front') ? 1 : 0) + ($license->getFirstMedia('license_back') ? 1 : 0) + $additionalDocuments->count()),
                'driver' => $license->driverDetail ? [
                    'id' => $license->driverDetail->id,
                    'name' => $this->driverFullName($license->driverDetail),
                    'email' => $license->driverDetail->user?->email,
                ] : null,
                'carrier' => $license->driverDetail?->carrier ? [
                    'id' => $license->driverDetail->carrier->id,
                    'name' => $license->driverDetail->carrier->name,
                ] : null,
                'endorsements' => $license->endorsements
                    ->map(fn (LicenseEndorsement $endorsement) => [
                        'id' => $endorsement->id,
                        'code' => $endorsement->code,
                        'name' => $endorsement->name,
                    ])
                    ->values(),
                'documents' => $additionalDocuments,
            ],
            'isCarrierContext' => true,
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function edit(DriverLicense $license): Response
    {
        $this->authorizeCarrierLicense($license);
        $carrier = $this->resolveCarrier();
        $license->load(['driverDetail.user:id,name,email', 'driverDetail.carrier:id,name', 'endorsements:id,code,name']);

        return Inertia::render('carrier/licenses/Edit', [
            'license' => [
                'id' => $license->id,
                'user_driver_detail_id' => $license->user_driver_detail_id,
                'carrier_id' => $license->driverDetail?->carrier_id,
                'driver_name' => $this->driverFullName($license->driverDetail),
                'license_number' => $license->license_number,
                'license_class' => $license->license_class,
                'state_of_issue' => $license->state_of_issue,
                'expiration_date' => $license->expiration_date?->format('n/j/Y'),
                'is_cdl' => (bool) $license->is_cdl,
                'is_primary' => (bool) $license->is_primary,
                'endorsement_ids' => $license->endorsements->pluck('id')->all(),
                'front_url' => $license->getFirstMediaUrl('license_front') ?: null,
                'back_url' => $license->getFirstMediaUrl('license_back') ?: null,
            ],
            'carriers' => [self::carrierOption($carrier)],
            'drivers' => $this->carrierDriverOptions((int) $carrier->id),
            'states' => Constants::usStates(),
            'endorsements' => $this->endorsementOptions(),
            'carrier' => self::carrierOption($carrier),
            'isCarrierContext' => true,
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function update(Request $request, DriverLicense $license): RedirectResponse
    {
        $request->merge([
            'expiration_date' => $this->parseUsDate((string) $request->input('expiration_date')),
        ]);

        $this->authorizeCarrierLicense($license);
        $carrierId = (int) $this->resolveCarrier()->id;
        $validated = $request->validate([
            'user_driver_detail_id' => 'required|exists:user_driver_details,id',
            'license_number' => 'required|string|max:255',
            'license_class' => 'required|string|max:255',
            'state_of_issue' => 'required|string|max:5',
            'expiration_date' => 'required|date|after:today',
            'is_cdl' => 'nullable|boolean',
            'is_primary' => 'nullable|boolean',
            'endorsement_ids' => 'nullable|array',
            'endorsement_ids.*' => 'integer|exists:license_endorsements,id',
            'license_front_image' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'license_back_image' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'license_documents' => 'nullable|array',
            'license_documents.*' => 'file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240',
        ]);

        $this->ensureCarrierOwnsDriver((int) $validated['user_driver_detail_id'], $carrierId);

        DB::transaction(function () use ($request, $validated, $license) {
            $license->update([
                'user_driver_detail_id' => $validated['user_driver_detail_id'],
                'license_number' => $validated['license_number'],
                'license_class' => $validated['license_class'],
                'state_of_issue' => $validated['state_of_issue'],
                'expiration_date' => $validated['expiration_date'],
                'is_cdl' => $request->boolean('is_cdl'),
                'is_primary' => $this->resolvePrimaryFlag(
                    (int) $validated['user_driver_detail_id'],
                    $request->boolean('is_primary'),
                    $license->id
                ),
            ]);

            $this->syncPrimaryLicense($license);
            $license->endorsements()->sync($request->boolean('is_cdl') ? ($validated['endorsement_ids'] ?? []) : []);
            $this->syncMedia($license, $request);
        });

        return redirect()
            ->route('carrier.licenses.show', $license)
            ->with('success', 'License updated successfully.');
    }

    public function destroy(DriverLicense $license): RedirectResponse
    {
        $this->authorizeCarrierLicense($license);

        DB::transaction(function () use ($license) {
            $driverId = $license->user_driver_detail_id;
            $wasPrimary = (bool) $license->is_primary;

            $license->clearMediaCollection('license_front');
            $license->clearMediaCollection('license_back');
            $license->clearMediaCollection('license_documents');
            $license->endorsements()->detach();
            $license->delete();

            if ($wasPrimary) {
                $replacement = DriverLicense::query()
                    ->where('user_driver_detail_id', $driverId)
                    ->orderByDesc('created_at')
                    ->first();

                if ($replacement && ! $replacement->is_primary) {
                    $replacement->update(['is_primary' => true]);
                }
            }
        });

        return redirect()
            ->route('carrier.licenses.index')
            ->with('success', 'License deleted successfully.');
    }

    public function documents(Request $request): Response
    {
        return $this->renderCarrierDocumentsPage($request);
    }

    public function showDocuments(Request $request, DriverLicense $license): Response
    {
        $this->authorizeCarrierLicense($license);
        $license->load(['driverDetail.user:id,name,email', 'driverDetail.carrier:id,name']);

        return $this->renderCarrierDocumentsPage($request, $license);
    }

    public function destroyMedia(Media $media): RedirectResponse
    {
        abort_unless(
            $media->model_type === DriverLicense::class
            && in_array($media->collection_name, ['license_front', 'license_back', 'license_documents'], true),
            404
        );

        /** @var DriverLicense|null $license */
        $license = DriverLicense::query()->find($media->model_id);
        abort_unless($license, 404);
        $this->authorizeCarrierLicense($license);

        $media->delete();

        return back()->with('success', 'License document deleted successfully.');
    }

    protected function renderCarrierDocumentsPage(Request $request, ?DriverLicense $license = null): Response
    {
        $carrier = $this->resolveCarrier();
        $filters = [
            'search_term' => trim((string) $request->input('search_term', '')),
            'carrier_filter' => '',
            'license_filter' => (string) $request->input('license_filter', $license?->id ? (string) $license->id : ''),
            'date_from' => (string) $request->input('date_from', ''),
            'date_to' => (string) $request->input('date_to', ''),
            'collection' => (string) $request->input('collection', ''),
        ];

        $query = Media::query()
            ->where('model_type', DriverLicense::class)
            ->whereIn('collection_name', ['license_front', 'license_back', 'license_documents'])
            ->whereHasMorph('model', [DriverLicense::class], function ($builder) use ($carrier) {
                $builder->whereHas('driverDetail', fn ($driverQuery) => $driverQuery->where('carrier_id', $carrier->id));
            });

        if ($license) {
            $query->where('model_id', $license->id);
        }

        if ($filters['license_filter'] !== '') {
            $query->where('model_id', $filters['license_filter']);
        }

        if ($filters['search_term'] !== '') {
            $searchTerm = '%' . $filters['search_term'] . '%';

            $query->where(function ($builder) use ($searchTerm) {
                $builder
                    ->where('file_name', 'like', $searchTerm)
                    ->orWhere('mime_type', 'like', $searchTerm)
                    ->orWhereHasMorph('model', [DriverLicense::class], function ($licenseQuery) use ($searchTerm) {
                        $licenseQuery
                            ->where('license_number', 'like', $searchTerm)
                            ->orWhereHas('driverDetail.user', function ($userQuery) use ($searchTerm) {
                                $userQuery
                                    ->where('name', 'like', $searchTerm)
                                    ->orWhere('email', 'like', $searchTerm);
                            })
                            ->orWhereHas('driverDetail', function ($driverQuery) use ($searchTerm) {
                                $driverQuery
                                    ->where('middle_name', 'like', $searchTerm)
                                    ->orWhere('last_name', 'like', $searchTerm);
                            });
                    });
            });
        }

        if ($filters['collection'] !== '') {
            $query->where('collection_name', $filters['collection']);
        }

        if ($filters['date_from'] !== '') {
            $query->whereDate('created_at', '>=', $this->parseUsDate($filters['date_from']));
        }

        if ($filters['date_to'] !== '') {
            $query->whereDate('created_at', '<=', $this->parseUsDate($filters['date_to']));
        }

        $documents = $query
            ->with('model')
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        $documents->getCollection()->loadMorph('model', [
            DriverLicense::class => ['driverDetail.user:id,name,email', 'driverDetail.carrier:id,name'],
        ]);

        $documents->through(function (Media $media) {
            /** @var DriverLicense|null $license */
            $license = $media->model;
            $driver = $license?->driverDetail;

            return [
                'id' => $media->id,
                'license_id' => $license?->id,
                'license_number' => $license?->license_number,
                'carrier_name' => $driver?->carrier?->name ?? 'N/A',
                'driver_name' => $this->driverFullName($driver),
                'file_name' => $media->file_name,
                'collection_name' => $media->collection_name,
                'collection_label' => match ($media->collection_name) {
                    'license_front' => 'Front Image',
                    'license_back' => 'Back Image',
                    default => 'Additional Document',
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
            ->where('model_type', DriverLicense::class)
            ->whereIn('collection_name', ['license_front', 'license_back', 'license_documents'])
            ->whereHasMorph('model', [DriverLicense::class], function ($builder) use ($carrier) {
                $builder->whereHas('driverDetail', fn ($driverQuery) => $driverQuery->where('carrier_id', $carrier->id));
            });

        if ($license) {
            $statsQuery->where('model_id', $license->id);
        }

        return Inertia::render('carrier/licenses/Documents', [
            'documents' => $documents,
            'filters' => $filters,
            'license' => $license ? [
                'id' => $license->id,
                'license_number' => $license->license_number,
                'driver_name' => $this->driverFullName($license->driverDetail),
                'carrier_name' => $license->driverDetail?->carrier?->name,
            ] : null,
            'licenses' => DriverLicense::query()
                ->with(['driverDetail.carrier:id,name'])
                ->whereHas('driverDetail', fn ($builder) => $builder->where('carrier_id', $carrier->id))
                ->orderBy('license_number')
                ->get()
                ->map(fn (DriverLicense $licenseItem) => [
                    'id' => $licenseItem->id,
                    'license_number' => $licenseItem->license_number,
                    'carrier_name' => $licenseItem->driverDetail?->carrier?->name,
                ]),
            'carriers' => [self::carrierOption($carrier)],
            'stats' => [
                'total' => (clone $statsQuery)->count(),
                'front' => (clone $statsQuery)->where('collection_name', 'license_front')->count(),
                'back' => (clone $statsQuery)->where('collection_name', 'license_back')->count(),
                'additional' => (clone $statsQuery)->where('collection_name', 'license_documents')->count(),
            ],
            'carrier' => self::carrierOption($carrier),
            'isCarrierContext' => true,
            'routeNames' => $this->routeNames(),
        ]);
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

    protected function authorizeCarrierLicense(DriverLicense $license): void
    {
        $license->loadMissing('driverDetail');
        abort_unless((int) $license->driverDetail?->carrier_id === (int) $this->resolveCarrierId(), 403);
    }

    protected function endorsementOptions()
    {
        return LicenseEndorsement::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name'])
            ->map(fn (LicenseEndorsement $endorsement) => [
                'id' => $endorsement->id,
                'code' => $endorsement->code,
                'name' => $endorsement->name,
            ]);
    }

    protected function routeNames(): array
    {
        return [
            'index' => 'carrier.licenses.index',
            'create' => 'carrier.licenses.create',
            'store' => 'carrier.licenses.store',
            'show' => 'carrier.licenses.show',
            'edit' => 'carrier.licenses.edit',
            'update' => 'carrier.licenses.update',
            'destroy' => 'carrier.licenses.destroy',
            'documentsIndex' => 'carrier.licenses.documents.index',
            'documentsShow' => 'carrier.licenses.documents.show',
            'mediaDestroy' => 'carrier.licenses.media.destroy',
            'driverShow' => 'carrier.drivers.show',
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
