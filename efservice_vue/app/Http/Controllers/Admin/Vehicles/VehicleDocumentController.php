<?php

namespace App\Http\Controllers\Admin\Vehicles;

use App\Http\Controllers\Admin\Vehicles\Concerns\UsesVehicleAdminHelpers;
use App\Http\Controllers\Controller;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Admin\Vehicle\VehicleDocument;
use App\Models\Admin\Vehicle\VehicleMaintenance;
use App\Models\EmergencyRepair;
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
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        $reportTypes = [VehicleDocument::DOC_TYPE_MAINTENANCE_RECORD, VehicleDocument::DOC_TYPE_REPAIR_RECORD];

        $query = $vehicle->documents()->with('media')
            ->whereNotIn('document_type', $reportTypes);

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

        $maintenanceReports = $vehicle->documents()->with('media')
            ->where('document_type', VehicleDocument::DOC_TYPE_MAINTENANCE_RECORD)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (VehicleDocument $document) => $this->documentRow($document))
            ->values();

        $repairReports = $vehicle->documents()->with('media')
            ->where('document_type', VehicleDocument::DOC_TYPE_REPAIR_RECORD)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (VehicleDocument $document) => $this->documentRow($document))
            ->values();

        $statsQuery = $vehicle->documents();

        $hasMaintenanceRecords = VehicleMaintenance::where('vehicle_id', $vehicle->id)->exists();
        $hasRepairRecords = EmergencyRepair::where('vehicle_id', $vehicle->id)->exists();
        $hasDocumentsWithFiles = $vehicle->documents()->whereHas('media')->exists();

        $documentTypeOptionsFiltered = collect($this->documentTypeOptions())
            ->reject(fn ($label, $key) => in_array($key, $reportTypes, true))
            ->all();

        return Inertia::render('admin/vehicles/documents/Index', [
            'vehicle' => [
                'id'                  => $vehicle->id,
                'title'               => trim(implode(' ', array_filter([$vehicle->year, $vehicle->make, $vehicle->model]))) ?: 'Vehicle',
                'company_unit_number' => $vehicle->company_unit_number,
                'vin'                 => $vehicle->vin,
                'carrier_name'        => $vehicle->carrier?->name,
            ],
            'documents'          => $documents,
            'maintenanceReports' => $maintenanceReports,
            'repairReports'      => $repairReports,
            'filters'            => $filters,
            'documentTypes'      => $documentTypeOptionsFiltered,
            'documentStatuses'   => $this->documentStatusOptions(),
            'hasMaintenanceRecords'  => $hasMaintenanceRecords,
            'hasRepairRecords'       => $hasRepairRecords,
            'hasDocumentsWithFiles'  => $hasDocumentsWithFiles,
            'stats' => [
                'total'   => (clone $statsQuery)->count(),
                'active'  => (clone $statsQuery)->where('status', VehicleDocument::STATUS_ACTIVE)->count(),
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

    public function downloadAll(Vehicle $vehicle): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $this->authorizeVehicle($vehicle);

        // Load all documents with their media filtered to document_files collection only
        $documents = $vehicle->documents()
            ->with(['media' => fn ($q) => $q->where('collection_name', 'document_files')])
            ->get()
            ->filter(fn (VehicleDocument $doc) => $doc->media->isNotEmpty());

        abort_if($documents->isEmpty(), 404, 'No document files to download.');

        $vehicleLabel = Str::slug(
            trim(implode('-', array_filter([$vehicle->year, $vehicle->make, $vehicle->model]))) ?: 'vehicle'
        );

        $tempDir = storage_path('app/temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $zipPath = $tempDir . '/vehicle-' . $vehicle->id . '-documents-' . time() . '.zip';
        $zip = new \ZipArchive();

        $opened = $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        abort_if($opened !== true, 500, 'Could not create ZIP archive.');

        $nameCounts = [];
        $addedCount = 0;
        $tempFiles = [];

        foreach ($documents as $doc) {
            /** @var \Spatie\MediaLibrary\MediaCollections\Models\Media $media */
            $media = $doc->media->first();
            if (! $media) {
                continue;
            }

            $localPath = $media->getPath();

            if (! file_exists($localPath)) {
                // Fallback: legacy files were stored under "vehicle/" (singular) instead of "vehicles/"
                $legacyPath = str_replace(
                    '/vehicles/' . $vehicle->id . '/documents/',
                    '/vehicle/' . $vehicle->id . '/documents/',
                    $localPath
                );

                if (file_exists($legacyPath)) {
                    $localPath = $legacyPath;
                } else {
                    // Last resort: scan the disk for a file matching this filename
                    $disk = \Illuminate\Support\Facades\Storage::disk($media->disk ?: 'public');
                    $found = collect($disk->allFiles('vehicle/' . $vehicle->id . '/documents'))
                        ->first(fn ($f) => basename($f) === $media->file_name);

                    if ($found) {
                        $localPath = storage_path('app/public/' . $found);
                    } else {
                        continue;
                    }
                }
            }

            $folder = Str::slug($doc->document_type_name ?: $doc->document_type, '-');
            $filename = $media->file_name;

            $key = $folder . '/' . $filename;
            $nameCounts[$key] = ($nameCounts[$key] ?? 0) + 1;
            if ($nameCounts[$key] > 1) {
                $ext = pathinfo($filename, PATHINFO_EXTENSION);
                $base = pathinfo($filename, PATHINFO_FILENAME);
                $filename = $base . '-' . $nameCounts[$key] . ($ext ? '.' . $ext : '');
            }

            $zip->addFile($localPath, $folder . '/' . $filename);
            $addedCount++;
        }

        $zip->close();

        foreach ($tempFiles as $tmpFile) {
            @unlink($tmpFile);
        }

        abort_if(! file_exists($zipPath) || $addedCount === 0, 404, 'No downloadable files found.');

        return response()
            ->download($zipPath, $vehicleLabel . '-documents.zip')
            ->deleteFileAfterSend(true);
    }

    public function generateMaintenanceReport(Vehicle $vehicle): RedirectResponse
    {
        $this->authorizeVehicle($vehicle);
        $vehicle->load('carrier');

        $maintenances = VehicleMaintenance::where('vehicle_id', $vehicle->id)
            ->orderBy('service_date', 'asc')
            ->get();

        abort_if($maintenances->isEmpty(), 404, 'No maintenance records found for this vehicle.');

        $fileName = 'maintenance-report-' . $vehicle->id . '-' . now()->format('YmdHis') . '.pdf';
        $tempDir = storage_path('app/temp');
        $tempPath = $tempDir . DIRECTORY_SEPARATOR . $fileName;

        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        Pdf::loadView('admin.vehicles.maintenance.full-report-pdf', [
            'vehicle' => $vehicle,
            'maintenances' => $maintenances,
        ])->setPaper('letter', 'portrait')->save($tempPath);

        try {
            $document = VehicleDocument::create([
                'vehicle_id' => $vehicle->id,
                'document_type' => VehicleDocument::DOC_TYPE_MAINTENANCE_RECORD,
                'document_number' => 'MR-' . $vehicle->id . '-' . now()->format('Ymd'),
                'issued_date' => now()->toDateString(),
                'status' => VehicleDocument::STATUS_ACTIVE,
                'notes' => 'Auto-generated Vehicle Service Due Status Report (49 C.F.R. 396.3). Generated on ' . now()->format('m/d/Y h:i A') . '. Contains ' . $maintenances->count() . ' maintenance record(s).',
            ]);

            $document->addMedia($tempPath)
                ->usingFileName($fileName)
                ->toMediaCollection('document_files');
        } finally {
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }

        return redirect()
            ->route('admin.vehicles.documents.index', $vehicle)
            ->with('success', 'Maintenance report generated and saved to vehicle documents.');
    }

    public function generateRepairReport(Vehicle $vehicle): RedirectResponse
    {
        $this->authorizeVehicle($vehicle);
        $vehicle->load('carrier');

        $repairs = EmergencyRepair::where('vehicle_id', $vehicle->id)
            ->orderBy('repair_date', 'asc')
            ->get();

        abort_if($repairs->isEmpty(), 404, 'No repair records found for this vehicle.');

        $fileName = 'repair-report-' . $vehicle->id . '-' . now()->format('YmdHis') . '.pdf';
        $tempDir = storage_path('app/temp');
        $tempPath = $tempDir . DIRECTORY_SEPARATOR . $fileName;

        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        Pdf::loadView('admin.vehicles.emergency-repairs.full-report-pdf', [
            'vehicle' => $vehicle,
            'repairs' => $repairs,
        ])->setPaper('letter', 'portrait')->save($tempPath);

        try {
            $document = VehicleDocument::create([
                'vehicle_id' => $vehicle->id,
                'document_type' => VehicleDocument::DOC_TYPE_REPAIR_RECORD,
                'document_number' => 'RR-' . $vehicle->id . '-' . now()->format('Ymd'),
                'issued_date' => now()->toDateString(),
                'status' => VehicleDocument::STATUS_ACTIVE,
                'notes' => 'Auto-generated Inspection, Repair & Maintenance Record (49 C.F.R. 396.3). Generated on ' . now()->format('m/d/Y h:i A') . '. Contains ' . $repairs->count() . ' repair record(s).',
            ]);

            $document->addMedia($tempPath)
                ->usingFileName($fileName)
                ->toMediaCollection('document_files');
        } finally {
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }

        return redirect()
            ->route('admin.vehicles.documents.index', $vehicle)
            ->with('success', 'Repair report generated and saved to vehicle documents.');
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
