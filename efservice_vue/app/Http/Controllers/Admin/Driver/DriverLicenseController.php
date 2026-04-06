<?php

namespace App\Http\Controllers\Admin\Driver;

use App\Helpers\Constants;
use App\Http\Controllers\Controller;
use App\Models\Admin\Driver\DriverApplication;
use App\Models\Admin\Driver\DriverLicense;
use App\Models\Admin\Driver\LicenseEndorsement;
use App\Models\Carrier;
use App\Models\UserDriverDetail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DriverLicenseController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'search_term' => trim((string) $request->input('search_term', '')),
            'carrier_filter' => $request->input('carrier_filter', ''),
            'driver_filter' => $request->input('driver_filter', ''),
            'date_from' => $request->input('date_from', ''),
            'date_to' => $request->input('date_to', ''),
            'sort_field' => $request->input('sort_field', 'created_at'),
            'sort_direction' => $request->input('sort_direction', 'desc'),
        ];

        $query = DriverLicense::query()
            ->with(['driverDetail.user:id,name,email', 'driverDetail.carrier:id,name']);

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

        if ($filters['carrier_filter'] !== '') {
            $query->whereHas('driverDetail', function ($builder) use ($filters) {
                $builder->where('carrier_id', $filters['carrier_filter']);
            });
        }

        if ($filters['date_from'] !== '') {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if ($filters['date_to'] !== '') {
            $query->whereDate('created_at', '<=', $filters['date_to']);
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

        return Inertia::render('admin/licenses/Index', [
            'licenses' => $licenses,
            'drivers' => $this->driverOptions(),
            'carriers' => Carrier::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Carrier $carrier) => [
                    'id' => $carrier->id,
                    'name' => $carrier->name,
                ]),
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/licenses/Create', [
            'carriers' => Carrier::query()
                ->where('status', 1)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Carrier $carrier) => [
                    'id' => $carrier->id,
                    'name' => $carrier->name,
                ]),
            'drivers' => $this->driverOptions(),
            'states' => Constants::usStates(),
            'endorsements' => LicenseEndorsement::query()
                ->where('is_active', true)
                ->orderBy('code')
                ->get(['id', 'code', 'name'])
                ->map(fn (LicenseEndorsement $endorsement) => [
                    'id' => $endorsement->id,
                    'code' => $endorsement->code,
                    'name' => $endorsement->name,
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
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
            ->route('admin.licenses.index')
            ->with('success', 'License created successfully.');
    }

    public function edit(DriverLicense $license): Response
    {
        $license->load(['driverDetail.user:id,name,email', 'driverDetail.carrier:id,name', 'endorsements:id,code,name']);

        return Inertia::render('admin/licenses/Edit', [
            'license' => [
                'id' => $license->id,
                'user_driver_detail_id' => $license->user_driver_detail_id,
                'carrier_id' => $license->driverDetail?->carrier_id,
                'license_number' => $license->license_number,
                'license_class' => $license->license_class,
                'state_of_issue' => $license->state_of_issue,
                'expiration_date' => $license->expiration_date?->format('Y-m-d'),
                'is_cdl' => (bool) $license->is_cdl,
                'is_primary' => (bool) $license->is_primary,
                'endorsement_ids' => $license->endorsements->pluck('id')->all(),
                'front_url' => $license->getFirstMediaUrl('license_front') ?: null,
                'back_url' => $license->getFirstMediaUrl('license_back') ?: null,
                'driver_name' => $this->driverFullName($license->driverDetail),
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
            'states' => Constants::usStates(),
            'endorsements' => LicenseEndorsement::query()
                ->where('is_active', true)
                ->orderBy('code')
                ->get(['id', 'code', 'name'])
                ->map(fn (LicenseEndorsement $endorsement) => [
                    'id' => $endorsement->id,
                    'code' => $endorsement->code,
                    'name' => $endorsement->name,
                ]),
        ]);
    }

    public function update(Request $request, DriverLicense $license): RedirectResponse
    {
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
            ->route('admin.licenses.index')
            ->with('success', 'License updated successfully.');
    }

    public function destroy(DriverLicense $license): RedirectResponse
    {
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
            ->route('admin.licenses.index')
            ->with('success', 'License deleted successfully.');
    }

    public function documents(Request $request): Response
    {
        return $this->renderDocumentsPage($request);
    }

    public function showDocuments(Request $request, DriverLicense $license): Response
    {
        $license->load(['driverDetail.user:id,name,email', 'driverDetail.carrier:id,name']);

        return $this->renderDocumentsPage($request, $license);
    }

    public function destroyMedia(Media $media): RedirectResponse
    {
        abort_unless(
            $media->model_type === DriverLicense::class
            && in_array($media->collection_name, ['license_front', 'license_back', 'license_documents'], true),
            404
        );

        $media->delete();

        return back()->with('success', 'License document deleted successfully.');
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

    protected function transformLicense(DriverLicense $license, int $documentCount): array
    {
        return [
            'id' => $license->id,
            'created_at' => $license->created_at?->toDateString(),
            'license_number' => $license->license_number,
            'license_class' => $license->license_class,
            'state_of_issue' => $license->state_of_issue,
            'expiration_date' => $license->expiration_date?->toDateString(),
            'is_cdl' => (bool) $license->is_cdl,
            'is_primary' => (bool) $license->is_primary,
            'document_count' => $documentCount,
            'driver' => $license->driverDetail ? [
                'id' => $license->driverDetail->id,
                'name' => $this->driverFullName($license->driverDetail),
                'email' => $license->driverDetail->user?->email,
            ] : null,
            'carrier' => $license->driverDetail?->carrier ? [
                'id' => $license->driverDetail->carrier->id,
                'name' => $license->driverDetail->carrier->name,
            ] : null,
        ];
    }

    protected function driverFullName(?UserDriverDetail $driver): string
    {
        if (! $driver) {
            return 'N/A';
        }

        return trim(implode(' ', array_filter([
            $driver->user?->name,
            $driver->middle_name,
            $driver->last_name,
        ]))) ?: 'N/A';
    }

    protected function syncMedia(DriverLicense $license, Request $request): void
    {
        if ($request->hasFile('license_front_image')) {
            $license->clearMediaCollection('license_front');
            $license->addMediaFromRequest('license_front_image')
                ->usingName('License Front')
                ->toMediaCollection('license_front');
        }

        if ($request->hasFile('license_back_image')) {
            $license->clearMediaCollection('license_back');
            $license->addMediaFromRequest('license_back_image')
                ->usingName('License Back')
                ->toMediaCollection('license_back');
        }

        foreach ($request->file('license_documents', []) as $document) {
            $license->addMedia($document)
                ->usingName(pathinfo($document->getClientOriginalName(), PATHINFO_FILENAME))
                ->toMediaCollection('license_documents');
        }
    }

    protected function resolvePrimaryFlag(int $driverId, bool $requestedPrimary, ?int $ignoreLicenseId = null): bool
    {
        if ($requestedPrimary) {
            return true;
        }

        $query = DriverLicense::query()->where('user_driver_detail_id', $driverId);

        if ($ignoreLicenseId !== null) {
            $query->where('id', '!=', $ignoreLicenseId);
        }

        return ! $query->where('is_primary', true)->exists();
    }

    protected function syncPrimaryLicense(DriverLicense $license): void
    {
        if (! $license->is_primary) {
            return;
        }

        DriverLicense::query()
            ->where('user_driver_detail_id', $license->user_driver_detail_id)
            ->where('id', '!=', $license->id)
            ->update(['is_primary' => false]);
    }

    protected function licenseDocumentCounts(array $licenseIds): array
    {
        if ($licenseIds === []) {
            return [];
        }

        return Media::query()
            ->where('model_type', DriverLicense::class)
            ->whereIn('model_id', $licenseIds)
            ->whereIn('collection_name', ['license_front', 'license_back', 'license_documents'])
            ->select('model_id', DB::raw('count(*) as count'))
            ->groupBy('model_id')
            ->pluck('count', 'model_id')
            ->map(fn ($count) => (int) $count)
            ->all();
    }

    protected function renderDocumentsPage(Request $request, ?DriverLicense $license = null): Response
    {
        $filters = [
            'search_term' => trim((string) $request->input('search_term', '')),
            'carrier_filter' => (string) $request->input('carrier_filter', ''),
            'license_filter' => (string) $request->input('license_filter', $license?->id ? (string) $license->id : ''),
            'date_from' => (string) $request->input('date_from', ''),
            'date_to' => (string) $request->input('date_to', ''),
            'collection' => (string) $request->input('collection', ''),
        ];

        $query = Media::query()
            ->where('model_type', DriverLicense::class)
            ->whereIn('collection_name', ['license_front', 'license_back', 'license_documents']);

        if ($license) {
            $query->where('model_id', $license->id);
        }

        if ($filters['license_filter'] !== '') {
            $query->where('model_id', $filters['license_filter']);
        }

        if ($filters['carrier_filter'] !== '') {
            $query->whereHasMorph('model', [DriverLicense::class], function ($builder) use ($filters) {
                $builder->whereHas('driverDetail', function ($driverQuery) use ($filters) {
                    $driverQuery->where('carrier_id', $filters['carrier_filter']);
                });
            });
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
            ->whereIn('collection_name', ['license_front', 'license_back', 'license_documents']);

        if ($license) {
            $statsQuery->where('model_id', $license->id);
        }

        return Inertia::render('admin/licenses/Documents', [
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
                ->orderBy('license_number')
                ->get()
                ->map(fn (DriverLicense $licenseItem) => [
                    'id' => $licenseItem->id,
                    'license_number' => $licenseItem->license_number,
                    'carrier_name' => $licenseItem->driverDetail?->carrier?->name,
                ]),
            'carriers' => Carrier::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Carrier $carrier) => [
                    'id' => $carrier->id,
                    'name' => $carrier->name,
                ]),
            'stats' => [
                'total' => (clone $statsQuery)->count(),
                'front' => (clone $statsQuery)->where('collection_name', 'license_front')->count(),
                'back' => (clone $statsQuery)->where('collection_name', 'license_back')->count(),
                'additional' => (clone $statsQuery)->where('collection_name', 'license_documents')->count(),
            ],
        ]);
    }

    protected function parseUsDate(?string $value): ?string
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
