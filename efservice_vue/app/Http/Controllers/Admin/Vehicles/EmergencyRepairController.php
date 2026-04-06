<?php

namespace App\Http\Controllers\Admin\Vehicles;

use App\Http\Controllers\Admin\Vehicles\Concerns\UsesVehicleAdminHelpers;
use App\Http\Controllers\Controller;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Admin\Vehicle\VehicleDocument;
use App\Models\EmergencyRepair;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class EmergencyRepairController extends Controller
{
    use UsesVehicleAdminHelpers;

    public function index(Request $request): Response
    {
        $carrierId = $this->resolvedCarrierId($request);
        $vehicleId = $request->filled('vehicle_id') ? (int) $request->input('vehicle_id') : null;
        $contextVehicle = $vehicleId ? Vehicle::query()->with('carrier:id,name')->findOrFail($vehicleId) : null;

        if ($contextVehicle) {
            $this->authorizeVehicle($contextVehicle);
        }

        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'carrier_id' => $this->isSuperadmin() ? (string) $request->input('carrier_id', '') : (string) ($carrierId ?? ''),
            'vehicle_id' => $vehicleId ? (string) $vehicleId : '',
            'driver_id' => (string) $request->input('driver_id', ''),
            'status' => (string) $request->input('status', ''),
            'date_from' => (string) $request->input('date_from', ''),
            'date_to' => (string) $request->input('date_to', ''),
        ];

        $query = $this->repairBaseQuery($carrierId, $vehicleId);

        if ($filters['search'] !== '') {
            $term = '%' . $filters['search'] . '%';
            $query->where(function (Builder $builder) use ($term) {
                $builder
                    ->where('repair_name', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhere('notes', 'like', $term)
                    ->orWhereHas('vehicle', function (Builder $vehicleQuery) use ($term) {
                        $vehicleQuery
                            ->where('make', 'like', $term)
                            ->orWhere('model', 'like', $term)
                            ->orWhere('vin', 'like', $term)
                            ->orWhere('company_unit_number', 'like', $term)
                            ->orWhereHas('carrier', fn (Builder $carrierQuery) => $carrierQuery->where('name', 'like', $term));
                    });
            });
        }

        if ($filters['driver_id'] !== '') {
            $query->whereHas('vehicle.currentDriverAssignment', fn (Builder $builder) => $builder->where('user_driver_detail_id', (int) $filters['driver_id']));
        }

        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if ($filters['date_from'] !== '' && $from = $this->parseUsDate($filters['date_from'])) {
            $query->whereDate('repair_date', '>=', $from->toDateString());
        }

        if ($filters['date_to'] !== '' && $to = $this->parseUsDate($filters['date_to'])) {
            $query->whereDate('repair_date', '<=', $to->toDateString());
        }

        $repairs = $query
            ->orderByDesc('repair_date')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        $repairs->through(fn (EmergencyRepair $repair) => $this->repairRow($repair));

        $statsQuery = $this->repairBaseQuery($carrierId, $vehicleId);

        return Inertia::render('admin/vehicles/emergency-repairs/Index', [
            'repairs' => $repairs,
            'filters' => $filters,
            'carriers' => $this->carrierOptions(),
            'vehicles' => $this->vehicleOptions($carrierId),
            'drivers' => $this->driverOptions($carrierId),
            'statusOptions' => $this->statusOptions(),
            'stats' => [
                'total' => (clone $statsQuery)->count(),
                'pending' => (clone $statsQuery)->where('status', 'pending')->count(),
                'in_progress' => (clone $statsQuery)->where('status', 'in_progress')->count(),
                'completed' => (clone $statsQuery)->where('status', 'completed')->count(),
                'total_cost' => (float) ((clone $statsQuery)->sum('cost') ?: 0),
            ],
            'contextVehicle' => $contextVehicle ? $this->vehicleContextPayload($contextVehicle) : null,
            'isSuperadmin' => $this->isSuperadmin(),
        ]);
    }

    public function create(Request $request): Response
    {
        $carrierId = $this->resolvedCarrierId($request);
        $contextVehicle = $request->filled('vehicle_id')
            ? Vehicle::query()->with('carrier:id,name')->findOrFail((int) $request->input('vehicle_id'))
            : null;

        if ($contextVehicle) {
            $this->authorizeVehicle($contextVehicle);
            $carrierId = (int) $contextVehicle->carrier_id;
        }

        return Inertia::render('admin/vehicles/emergency-repairs/Create', [
            'repair' => null,
            'carriers' => $this->carrierOptions(),
            'vehicles' => $this->vehicleOptions($carrierId),
            'drivers' => $this->driverOptions($carrierId),
            'statusOptions' => $this->statusOptions(),
            'selectedCarrierId' => $carrierId,
            'contextVehicle' => $contextVehicle ? $this->vehicleContextPayload($contextVehicle) : null,
            'isSuperadmin' => $this->isSuperadmin(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePayload($request);
        $vehicle = Vehicle::query()->findOrFail((int) $validated['vehicle_id']);
        $this->authorizeVehicle($vehicle);

        $repair = DB::transaction(function () use ($request, $validated, $vehicle) {
            $repair = EmergencyRepair::create($this->repairPayload($validated, $vehicle));
            $this->storeAttachments($repair, $request);

            return $repair;
        });

        return redirect()
            ->route('admin.vehicles.emergency-repairs.show', $repair)
            ->with('success', 'Emergency repair created successfully.');
    }

    public function show(EmergencyRepair $emergencyRepair): Response
    {
        $emergencyRepair->load([
            'vehicle.carrier',
            'vehicle.currentDriverAssignment.driver.user:id,name,email',
            'vehicle.currentDriverAssignment.ownerOperatorDetail',
            'vehicle.currentDriverAssignment.thirdPartyDetail',
            'media',
        ]);
        $this->authorizeVehicle($emergencyRepair->vehicle);

        $generatedReports = $emergencyRepair->vehicle->documents()
            ->where('document_type', VehicleDocument::DOC_TYPE_REPAIR_RECORD)
            ->where('notes', 'like', '%' . $this->reportNoteToken($emergencyRepair) . '%')
            ->latest('created_at')
            ->get()
            ->map(fn (VehicleDocument $document) => $this->reportRow($document))
            ->values()
            ->all();

        return Inertia::render('admin/vehicles/emergency-repairs/Show', [
            'repair' => [
                'id' => $emergencyRepair->id,
                'vehicle' => $this->vehicleContextPayload($emergencyRepair->vehicle),
                'driver_name' => $this->currentAssignmentName($emergencyRepair->vehicle),
                'repair_name' => $emergencyRepair->repair_name,
                'repair_date' => $this->formatDateForUi($emergencyRepair->repair_date),
                'cost' => $emergencyRepair->cost !== null ? '$' . number_format((float) $emergencyRepair->cost, 2) : null,
                'odometer' => $emergencyRepair->odometer ? number_format((int) $emergencyRepair->odometer) : null,
                'status' => $emergencyRepair->status,
                'status_label' => str($emergencyRepair->status)->replace('_', ' ')->title()->toString(),
                'description' => $emergencyRepair->description,
                'notes' => $emergencyRepair->notes,
                'attachments' => $emergencyRepair->getMedia('emergency_repair_files')->map(fn (Media $media) => [
                    'id' => $media->id,
                    'name' => $media->file_name,
                    'url' => $media->getUrl(),
                    'size' => $media->human_readable_size,
                    'mime_type' => $media->mime_type,
                ])->values()->all(),
                'generated_reports' => $generatedReports,
            ],
        ]);
    }

    public function edit(EmergencyRepair $emergencyRepair): Response
    {
        $emergencyRepair->load('vehicle.carrier:id,name');
        $this->authorizeVehicle($emergencyRepair->vehicle);

        return Inertia::render('admin/vehicles/emergency-repairs/Edit', [
            'repair' => $this->repairFormPayload($emergencyRepair),
            'carriers' => $this->carrierOptions(),
            'vehicles' => $this->vehicleOptions((int) $emergencyRepair->vehicle->carrier_id),
            'drivers' => $this->driverOptions((int) $emergencyRepair->vehicle->carrier_id),
            'statusOptions' => $this->statusOptions(),
            'selectedCarrierId' => (int) $emergencyRepair->vehicle->carrier_id,
            'contextVehicle' => $this->vehicleContextPayload($emergencyRepair->vehicle),
            'isSuperadmin' => $this->isSuperadmin(),
        ]);
    }

    public function update(Request $request, EmergencyRepair $emergencyRepair): RedirectResponse
    {
        $emergencyRepair->load('vehicle');
        $this->authorizeVehicle($emergencyRepair->vehicle);

        $validated = $this->validatePayload($request);
        $vehicle = Vehicle::query()->findOrFail((int) $validated['vehicle_id']);
        $this->authorizeVehicle($vehicle);

        DB::transaction(function () use ($request, $validated, $emergencyRepair, $vehicle) {
            $emergencyRepair->update($this->repairPayload($validated, $vehicle));
            $this->storeAttachments($emergencyRepair, $request);
        });

        return redirect()
            ->route('admin.vehicles.emergency-repairs.show', $emergencyRepair)
            ->with('success', 'Emergency repair updated successfully.');
    }

    public function destroy(EmergencyRepair $emergencyRepair): RedirectResponse
    {
        $emergencyRepair->load('vehicle');
        $this->authorizeVehicle($emergencyRepair->vehicle);

        DB::transaction(function () use ($emergencyRepair) {
            $emergencyRepair->clearMediaCollection('emergency_repair_files');
            $emergencyRepair->delete();
        });

        return redirect()
            ->route('admin.vehicles.emergency-repairs.index')
            ->with('success', 'Emergency repair deleted successfully.');
    }

    public function uploadDocument(Request $request, EmergencyRepair $emergencyRepair): RedirectResponse
    {
        $emergencyRepair->load('vehicle');
        $this->authorizeVehicle($emergencyRepair->vehicle);

        $request->validate([
            'attachments' => ['required', 'array', 'min:1'],
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
        ]);

        $this->storeAttachments($emergencyRepair, $request);

        return back()->with('success', 'Documents uploaded successfully.');
    }

    public function deleteFile(EmergencyRepair $emergencyRepair, Media $media): RedirectResponse
    {
        $emergencyRepair->load('vehicle');
        $this->authorizeVehicle($emergencyRepair->vehicle);

        abort_unless($emergencyRepair->media()->whereKey($media->id)->exists(), 404);

        $media->delete();

        return back()->with('success', 'Attachment deleted successfully.');
    }

    public function generateSingleReport(EmergencyRepair $emergencyRepair): RedirectResponse
    {
        $emergencyRepair->load('vehicle.carrier');
        $this->authorizeVehicle($emergencyRepair->vehicle);

        $fileName = 'emergency-repair-report-' . $emergencyRepair->id . '-' . now()->format('YmdHis') . '.pdf';
        $tempDir = storage_path('app/temp');
        $tempPath = $tempDir . DIRECTORY_SEPARATOR . $fileName;

        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        Pdf::loadView('admin.vehicles.emergency-repairs.report-pdf', [
            'repair' => $emergencyRepair,
            'vehicle' => $emergencyRepair->vehicle,
        ])->setPaper('letter', 'portrait')->save($tempPath);

        try {
            $document = VehicleDocument::create([
                'vehicle_id' => $emergencyRepair->vehicle_id,
                'document_type' => VehicleDocument::DOC_TYPE_REPAIR_RECORD,
                'document_number' => 'RR-' . $emergencyRepair->vehicle_id . '-' . $emergencyRepair->id . '-' . now()->format('Ymd'),
                'issued_date' => now()->toDateString(),
                'status' => VehicleDocument::STATUS_ACTIVE,
                'notes' => 'Emergency repair report generated. ' . $this->reportNoteToken($emergencyRepair),
            ]);

            $document->addMedia($tempPath)
                ->usingFileName($fileName)
                ->toMediaCollection('document_files');
        } finally {
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }

        return back()->with('success', 'Emergency repair report generated and saved to vehicle documents.');
    }

    public function deleteRepairReport(EmergencyRepair $emergencyRepair, VehicleDocument $document): RedirectResponse
    {
        $emergencyRepair->load('vehicle');
        $this->authorizeVehicle($emergencyRepair->vehicle);

        abort_unless(
            (int) $document->vehicle_id === (int) $emergencyRepair->vehicle_id
            && $document->document_type === VehicleDocument::DOC_TYPE_REPAIR_RECORD
            && str_contains((string) $document->notes, $this->reportNoteToken($emergencyRepair)),
            404
        );

        $document->clearMediaCollection('document_files');
        $document->delete();

        return back()->with('success', 'Generated report deleted successfully.');
    }

    public function vehicleIndex(Vehicle $vehicle): RedirectResponse
    {
        $this->authorizeVehicle($vehicle);

        return redirect()->route('admin.vehicles.emergency-repairs.index', [
            'vehicle_id' => $vehicle->id,
        ]);
    }

    public function createForVehicle(Vehicle $vehicle): RedirectResponse
    {
        $this->authorizeVehicle($vehicle);

        return redirect()->route('admin.vehicles.emergency-repairs.create', [
            'vehicle_id' => $vehicle->id,
        ]);
    }

    protected function repairBaseQuery(?int $carrierId, ?int $vehicleId = null): Builder
    {
        $query = EmergencyRepair::query()->with([
            'vehicle.carrier',
            'vehicle.currentDriverAssignment.driver.user:id,name,email',
            'vehicle.currentDriverAssignment.ownerOperatorDetail',
            'vehicle.currentDriverAssignment.thirdPartyDetail',
            'media',
        ]);
        $this->applyVehicleRelationCarrierScope($query, $carrierId);

        if ($vehicleId) {
            $query->where('vehicle_id', $vehicleId);
        }

        return $query;
    }

    protected function validatePayload(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'repair_name' => ['required', 'string', 'max:255'],
            'repair_date' => ['required', 'string'],
            'cost' => ['required', 'numeric', 'min:0'],
            'odometer' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:pending,in_progress,completed'],
            'description' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
        ]);

        $validator->after(function ($validator) use ($request) {
            if (! $this->parseUsDate((string) $request->input('repair_date'))) {
                $validator->errors()->add('repair_date', 'Invalid date format. Use M/D/YYYY.');
            }
        });

        return $validator->validate();
    }

    protected function repairPayload(array $validated, Vehicle $vehicle): array
    {
        return [
            'vehicle_id' => $vehicle->id,
            'repair_name' => trim((string) $validated['repair_name']),
            'repair_date' => $this->parseUsDate((string) $validated['repair_date'])?->format('Y-m-d'),
            'cost' => $validated['cost'],
            'odometer' => $validated['odometer'] !== null && $validated['odometer'] !== '' ? (int) $validated['odometer'] : null,
            'status' => $validated['status'],
            'description' => $this->emptyToNull($validated['description'] ?? null),
            'notes' => $this->emptyToNull($validated['notes'] ?? null),
        ];
    }

    protected function repairFormPayload(EmergencyRepair $repair): array
    {
        return [
            'id' => $repair->id,
            'vehicle_id' => (string) $repair->vehicle_id,
            'carrier_id' => $repair->vehicle?->carrier_id ? (string) $repair->vehicle->carrier_id : '',
            'repair_name' => $repair->repair_name,
            'repair_date' => $this->formatDateForUi($repair->repair_date),
            'cost' => $repair->cost !== null ? (string) $repair->cost : '',
            'odometer' => $repair->odometer !== null ? (string) $repair->odometer : '',
            'status' => $repair->status,
            'description' => $repair->description,
            'notes' => $repair->notes,
            'attachments' => $repair->getMedia('emergency_repair_files')->map(fn (Media $media) => [
                'id' => $media->id,
                'name' => $media->file_name,
                'url' => $media->getUrl(),
                'size' => $media->human_readable_size,
                'mime_type' => $media->mime_type,
            ])->values()->all(),
        ];
    }

    protected function repairRow(EmergencyRepair $repair): array
    {
        return [
            'id' => $repair->id,
            'vehicle' => $this->vehicleContextPayload($repair->vehicle),
            'driver_name' => $this->currentAssignmentName($repair->vehicle),
            'repair_name' => $repair->repair_name,
            'repair_date' => $this->formatDateForUi($repair->repair_date),
            'cost' => $repair->cost !== null ? '$' . number_format((float) $repair->cost, 2) : null,
            'odometer' => $repair->odometer ? number_format((int) $repair->odometer) : null,
            'status' => $repair->status,
            'status_label' => str($repair->status)->replace('_', ' ')->title()->toString(),
            'attachments_count' => $repair->getMedia('emergency_repair_files')->count(),
        ];
    }

    protected function reportRow(VehicleDocument $document): array
    {
        $media = $document->getFirstMedia('document_files');

        return [
            'id' => $document->id,
            'document_number' => $document->document_number,
            'issued_date' => $this->formatDateForUi($document->issued_date),
            'status_label' => $document->status_name,
            'url' => $media?->getUrl(),
            'file_name' => $media?->file_name,
        ];
    }

    protected function vehicleOptions(?int $carrierId = null): Collection
    {
        $query = Vehicle::query()->with('carrier:id,name')->orderBy('company_unit_number')->orderBy('make')->orderBy('model');
        $this->applyCarrierScope($query, $carrierId);

        return $query->get()->map(fn (Vehicle $vehicle) => [
            'id' => $vehicle->id,
            'carrier_id' => $vehicle->carrier_id,
            'carrier_name' => $vehicle->carrier?->name,
            'label' => $this->vehicleLabel($vehicle),
        ])->values();
    }

    protected function vehicleContextPayload(Vehicle $vehicle): array
    {
        return [
            'id' => $vehicle->id,
            'carrier_id' => $vehicle->carrier_id,
            'carrier_name' => $vehicle->carrier?->name,
            'label' => $this->vehicleLabel($vehicle),
            'year' => $vehicle->year,
            'make' => $vehicle->make,
            'model' => $vehicle->model,
            'company_unit_number' => $vehicle->company_unit_number,
            'vin' => $vehicle->vin,
        ];
    }

    protected function vehicleLabel(Vehicle $vehicle): string
    {
        return trim(collect([
            $vehicle->company_unit_number ? 'Unit ' . $vehicle->company_unit_number : null,
            trim(implode(' ', array_filter([$vehicle->year, $vehicle->make, $vehicle->model]))) ?: null,
            $vehicle->vin ? 'VIN ' . $vehicle->vin : null,
        ])->filter()->implode(' - ')) ?: 'Vehicle #' . $vehicle->id;
    }

    protected function currentAssignmentName(Vehicle $vehicle): ?string
    {
        $assignment = $vehicle->currentDriverAssignment;

        if (! $assignment) {
            return null;
        }

        return $assignment->driver
            ? $this->driverFullName($assignment->driver)
            : ($assignment->ownerOperatorDetail?->owner_name ?: $assignment->thirdPartyDetail?->third_party_name);
    }

    protected function statusOptions(): array
    {
        return [
            'pending' => 'Pending',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
        ];
    }

    protected function storeAttachments(EmergencyRepair $repair, Request $request): void
    {
        foreach ($request->file('attachments', []) as $file) {
            $repair->addMedia($file)->toMediaCollection('emergency_repair_files');
        }
    }

    protected function reportNoteToken(EmergencyRepair $repair): string
    {
        return 'Emergency Repair ID #' . $repair->id;
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
