<?php

namespace App\Http\Controllers\Admin\Vehicles;

use App\Http\Controllers\Admin\Vehicles\Concerns\UsesVehicleAdminHelpers;
use App\Http\Controllers\Controller;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Admin\Vehicle\VehicleDocument;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class VehicleDocumentController extends Controller
{
    use UsesVehicleAdminHelpers;

    public function overview(Request $request): Response
    {
        $carrierId = $this->resolvedCarrierId($request);

        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'carrier_id' => $this->isSuperadmin() ? (string) $request->input('carrier_id', '') : (string) ($carrierId ?? ''),
            'vehicle_status' => (string) $request->input('vehicle_status', ''),
            'document_type' => (string) $request->input('document_type', ''),
            'document_status' => (string) $request->input('document_status', ''),
            'sort_field' => (string) $request->input('sort_field', 'created_at'),
            'sort_direction' => (string) $request->input('sort_direction', 'desc'),
        ];

        $query = Vehicle::query()
            ->with([
                'carrier:id,name',
                'documents' => fn ($builder) => $builder->with('media')->orderBy('expiration_date'),
            ])
            ->withCount([
                'documents',
                'documents as active_documents_count' => fn (Builder $builder) => $builder->where('status', VehicleDocument::STATUS_ACTIVE),
                'documents as expired_documents_count' => fn (Builder $builder) => $builder->where(function (Builder $expiredQuery) {
                    $expiredQuery
                        ->where('status', VehicleDocument::STATUS_EXPIRED)
                        ->orWhere(function (Builder $dateQuery) {
                            $dateQuery
                                ->whereNotNull('expiration_date')
                                ->whereDate('expiration_date', '<', now()->toDateString());
                        });
                }),
                'documents as pending_documents_count' => fn (Builder $builder) => $builder->where('status', VehicleDocument::STATUS_PENDING),
                'documents as expiring_soon_documents_count' => fn (Builder $builder) => $builder
                    ->whereNotNull('expiration_date')
                    ->whereDate('expiration_date', '>=', now()->toDateString())
                    ->whereDate('expiration_date', '<=', now()->addDays(30)->toDateString()),
            ]);

        $this->applyCarrierScope($query, $carrierId);

        if ($filters['search'] !== '') {
            $term = '%' . $filters['search'] . '%';
            $query->where(function (Builder $builder) use ($term) {
                $builder
                    ->where('company_unit_number', 'like', $term)
                    ->orWhere('make', 'like', $term)
                    ->orWhere('model', 'like', $term)
                    ->orWhere('vin', 'like', $term)
                    ->orWhere('registration_number', 'like', $term)
                    ->orWhereHas('carrier', fn (Builder $carrierQuery) => $carrierQuery->where('name', 'like', $term))
                    ->orWhereHas('documents', function (Builder $documentQuery) use ($term) {
                        $documentQuery
                            ->where('document_type', 'like', $term)
                            ->orWhere('document_number', 'like', $term)
                            ->orWhere('notes', 'like', $term)
                            ->orWhereHas('media', fn (Builder $mediaQuery) => $mediaQuery->where('file_name', 'like', $term));
                    });
            });
        }

        if ($filters['vehicle_status'] !== '') {
            match ($filters['vehicle_status']) {
                'out_of_service' => $query->where(function (Builder $builder) {
                    $builder->where('status', 'out_of_service')->orWhere('out_of_service', true);
                }),
                'suspended' => $query->where(function (Builder $builder) {
                    $builder->where('status', 'suspended')->orWhere('suspended', true);
                }),
                default => $query->where('status', $filters['vehicle_status']),
            };
        }

        if ($filters['document_type'] !== '') {
            $query->whereHas('documents', fn (Builder $builder) => $builder->where('document_type', $filters['document_type']));
        }

        if ($filters['document_status'] !== '') {
            $query->whereHas('documents', fn (Builder $builder) => $builder->where('status', $filters['document_status']));
        }

        $statsVehicleQuery = clone $query;

        $allowedSorts = ['created_at', 'company_unit_number', 'make', 'year'];
        $sortField = in_array($filters['sort_field'], $allowedSorts, true) ? $filters['sort_field'] : 'created_at';
        $sortDirection = $filters['sort_direction'] === 'asc' ? 'asc' : 'desc';

        $vehicles = $query->orderBy($sortField, $sortDirection)->paginate(12)->withQueryString();
        $vehicles->through(fn (Vehicle $vehicle) => $this->overviewRow($vehicle));

        $documentStatsQuery = VehicleDocument::query();
        $this->applyVehicleRelationCarrierScope($documentStatsQuery, $carrierId);

        if ($filters['document_type'] !== '') {
            $documentStatsQuery->where('document_type', $filters['document_type']);
        }

        if ($filters['document_status'] !== '') {
            $documentStatsQuery->where('status', $filters['document_status']);
        }

        return Inertia::render('admin/vehicles/documents/Overview', [
            'vehicles' => $vehicles,
            'filters' => $filters,
            'carriers' => $this->carrierOptions(),
            'documentTypes' => $this->documentTypeOptions(),
            'documentStatuses' => $this->documentStatusOptions(),
            'vehicleStatusOptions' => $this->vehicleStatusOptions(),
            'stats' => [
                'vehicles' => (clone $statsVehicleQuery)->count(),
                'documents' => (clone $documentStatsQuery)->count(),
                'active' => (clone $documentStatsQuery)->where('status', VehicleDocument::STATUS_ACTIVE)->count(),
                'expired' => (clone $documentStatsQuery)->where(function (Builder $builder) {
                    $builder
                        ->where('status', VehicleDocument::STATUS_EXPIRED)
                        ->orWhere(function (Builder $dateQuery) {
                            $dateQuery
                                ->whereNotNull('expiration_date')
                                ->whereDate('expiration_date', '<', now()->toDateString());
                        });
                })->count(),
                'expiring_soon' => (clone $documentStatsQuery)
                    ->whereNotNull('expiration_date')
                    ->whereDate('expiration_date', '>=', now()->toDateString())
                    ->whereDate('expiration_date', '<=', now()->addDays(30)->toDateString())
                    ->count(),
            ],
            'isSuperadmin' => $this->isSuperadmin(),
        ]);
    }

    public function index(Vehicle $vehicle, Request $request): Response
    {
        $this->authorizeVehicle($vehicle);

        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'document_type' => (string) $request->input('document_type', ''),
            'status' => (string) $request->input('status', ''),
            'sort_field' => (string) $request->input('sort_field', 'expiration_date'),
            'sort_direction' => (string) $request->input('sort_direction', 'asc'),
        ];

        $query = $vehicle->documents()->with('media');

        if ($filters['search'] !== '') {
            $term = '%' . $filters['search'] . '%';
            $query->where(function (Builder $builder) use ($term) {
                $builder
                    ->where('document_type', 'like', $term)
                    ->orWhere('document_number', 'like', $term)
                    ->orWhere('notes', 'like', $term)
                    ->orWhereHas('media', fn (Builder $mediaQuery) => $mediaQuery->where('file_name', 'like', $term));
            });
        }

        if ($filters['document_type'] !== '') {
            $query->where('document_type', $filters['document_type']);
        }

        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        $allowedSorts = ['expiration_date', 'issued_date', 'created_at', 'document_type', 'status'];
        $sortField = in_array($filters['sort_field'], $allowedSorts, true) ? $filters['sort_field'] : 'expiration_date';
        $sortDirection = $filters['sort_direction'] === 'desc' ? 'desc' : 'asc';

        $documents = $query->orderBy($sortField, $sortDirection)->paginate(15)->withQueryString();
        $documents->through(fn (VehicleDocument $document) => $this->documentRow($document));

        $statsQuery = $vehicle->documents();

        return Inertia::render('admin/vehicles/documents/Index', [
            'vehicle' => [
                'id' => $vehicle->id,
                'title' => trim(implode(' ', array_filter([$vehicle->year, $vehicle->make, $vehicle->model]))) ?: 'Vehicle',
                'company_unit_number' => $vehicle->company_unit_number,
                'vin' => $vehicle->vin,
                'carrier_name' => $vehicle->carrier?->name,
            ],
            'documents' => $documents,
            'filters' => $filters,
            'documentTypes' => $this->documentTypeOptions(),
            'documentStatuses' => $this->documentStatusOptions(),
            'stats' => [
                'total' => (clone $statsQuery)->count(),
                'active' => (clone $statsQuery)->where('status', VehicleDocument::STATUS_ACTIVE)->count(),
                'expired' => (clone $statsQuery)->where(function (Builder $builder) {
                    $builder
                        ->where('status', VehicleDocument::STATUS_EXPIRED)
                        ->orWhere(function (Builder $dateQuery) {
                            $dateQuery
                                ->whereNotNull('expiration_date')
                                ->whereDate('expiration_date', '<', now()->toDateString());
                        });
                })->count(),
                'pending' => (clone $statsQuery)->where('status', VehicleDocument::STATUS_PENDING)->count(),
            ],
        ]);
    }

    public function store(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $this->authorizeVehicle($vehicle);
        $validated = $this->validateDocumentPayload($request, true);

        DB::transaction(function () use ($request, $validated, $vehicle) {
            $document = VehicleDocument::create($this->documentPayload($request, $validated, $vehicle));

            $document->addMediaFromRequest('document_file')
                ->usingName(pathinfo($request->file('document_file')->getClientOriginalName(), PATHINFO_FILENAME))
                ->toMediaCollection('document_files');
        });

        return redirect()
            ->route('admin.vehicles.documents.index', $vehicle)
            ->with('success', 'Vehicle document uploaded successfully.');
    }

    public function update(Request $request, Vehicle $vehicle, VehicleDocument $document): RedirectResponse
    {
        $this->authorizeVehicle($vehicle);
        $this->assertVehicleDocument($vehicle, $document);
        $validated = $this->validateDocumentPayload($request, false);

        DB::transaction(function () use ($request, $validated, $vehicle, $document) {
            $document->update($this->documentPayload($request, $validated, $vehicle));

            if ($request->hasFile('document_file')) {
                $document->clearMediaCollection('document_files');
                $document->addMediaFromRequest('document_file')
                    ->usingName(pathinfo($request->file('document_file')->getClientOriginalName(), PATHINFO_FILENAME))
                    ->toMediaCollection('document_files');
            }
        });

        return redirect()
            ->route('admin.vehicles.documents.index', $vehicle)
            ->with('success', 'Vehicle document updated successfully.');
    }

    public function destroy(Vehicle $vehicle, VehicleDocument $document): RedirectResponse
    {
        $this->authorizeVehicle($vehicle);
        $this->assertVehicleDocument($vehicle, $document);

        DB::transaction(function () use ($document) {
            $document->clearMediaCollection('document_files');
            $document->delete();
        });

        return redirect()
            ->route('admin.vehicles.documents.index', $vehicle)
            ->with('success', 'Vehicle document deleted successfully.');
    }

    protected function validateDocumentPayload(Request $request, bool $fileRequired): array
    {
        $rules = [
            'document_type' => ['required', 'string', 'max:100'],
            'document_number' => ['nullable', 'string', 'max:255'],
            'issued_date' => ['nullable', 'string', 'max:20'],
            'expiration_date' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', 'in:' . implode(',', array_keys($this->documentStatusOptions()))],
            'notes' => ['nullable', 'string', 'max:2000'],
            'document_file' => [$fileRequired ? 'required' : 'nullable', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:10240'],
        ];

        $validator = Validator::make($request->all(), $rules);

        $validator->after(function ($validator) use ($request) {
            foreach (['issued_date', 'expiration_date'] as $field) {
                $value = $request->input($field);

                if ($value !== null && $value !== '' && ! $this->parseUsDate((string) $value)) {
                    $validator->errors()->add($field, 'Invalid date format. Use M/D/YYYY.');
                }
            }
        });

        return $validator->validate();
    }

    protected function documentPayload(Request $request, array $validated, Vehicle $vehicle): array
    {
        $issuedDate = $this->parseUsDate($validated['issued_date'] ?? null);
        $expirationDate = $this->parseUsDate($validated['expiration_date'] ?? null);
        $status = $validated['status'] ?? VehicleDocument::STATUS_ACTIVE;

        if (! $request->filled('status') && $expirationDate instanceof Carbon && $expirationDate->isPast()) {
            $status = VehicleDocument::STATUS_EXPIRED;
        }

        return [
            'vehicle_id' => $vehicle->id,
            'document_type' => $validated['document_type'],
            'document_number' => $this->emptyToNull($validated['document_number'] ?? null),
            'issued_date' => $issuedDate?->format('Y-m-d'),
            'expiration_date' => $expirationDate?->format('Y-m-d'),
            'status' => $status,
            'notes' => $this->emptyToNull($validated['notes'] ?? null),
        ];
    }

    protected function overviewRow(Vehicle $vehicle): array
    {
        $nextExpiring = $vehicle->documents
            ->filter(fn (VehicleDocument $document) => $document->expiration_date !== null)
            ->sortBy('expiration_date')
            ->first();

        return [
            'id' => $vehicle->id,
            'title' => trim(implode(' ', array_filter([$vehicle->year, $vehicle->make, $vehicle->model]))) ?: 'Vehicle',
            'company_unit_number' => $vehicle->company_unit_number,
            'vin' => $vehicle->vin,
            'carrier_name' => $vehicle->carrier?->name,
            'status' => $vehicle->status,
            'status_label' => $this->vehicleStatusOptions()[$vehicle->status] ?? Str::headline((string) $vehicle->status),
            'documents_count' => (int) ($vehicle->documents_count ?? 0),
            'active_documents_count' => (int) ($vehicle->active_documents_count ?? 0),
            'expired_documents_count' => (int) ($vehicle->expired_documents_count ?? 0),
            'pending_documents_count' => (int) ($vehicle->pending_documents_count ?? 0),
            'expiring_soon_documents_count' => (int) ($vehicle->expiring_soon_documents_count ?? 0),
            'next_expiring_document' => $nextExpiring ? [
                'document_type_label' => $nextExpiring->document_type_name,
                'expiration_date' => $this->formatDateForUi($nextExpiring->expiration_date),
            ] : null,
            'documents_preview' => $vehicle->documents
                ->take(3)
                ->map(fn (VehicleDocument $document) => $this->documentRow($document))
                ->values()
                ->all(),
        ];
    }

    protected function documentRow(VehicleDocument $document): array
    {
        /** @var Media|null $media */
        $media = $document->getFirstMedia('document_files');
        $extension = Str::lower(pathinfo($media?->file_name ?? '', PATHINFO_EXTENSION));

        return [
            'id' => $document->id,
            'document_type' => $document->document_type,
            'document_type_label' => $document->document_type_name,
            'document_number' => $document->document_number,
            'issued_date' => $this->formatDateForUi($document->issued_date),
            'expiration_date' => $this->formatDateForUi($document->expiration_date),
            'status' => $document->status,
            'status_label' => $document->status_name,
            'notes' => $document->notes,
            'file_name' => $media?->file_name,
            'file_type' => $extension ?: 'file',
            'size_label' => $media?->human_readable_size,
            'preview_url' => $media?->getUrl(),
            'created_at' => $document->created_at?->format('n/j/Y g:i A'),
            'is_expired' => $document->expiration_date?->isPast() ?? false,
            'is_expiring_soon' => $document->expiration_date?->isFuture() && $document->expiration_date?->lte(now()->addDays(30)),
        ];
    }

    protected function assertVehicleDocument(Vehicle $vehicle, VehicleDocument $document): void
    {
        abort_unless((int) $document->vehicle_id === (int) $vehicle->id, 404);
    }

    protected function authorizeVehicle(Vehicle $vehicle): void
    {
        if (! $this->isSuperadmin() && (int) $vehicle->carrier_id !== (int) ($this->currentCarrierId() ?: 0)) {
            abort(403);
        }
    }

    protected function emptyToNull(?string $value): ?string
    {
        $value = $value !== null ? trim($value) : null;

        return $value === '' ? null : $value;
    }
}
