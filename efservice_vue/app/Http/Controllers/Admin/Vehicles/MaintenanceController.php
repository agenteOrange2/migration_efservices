<?php

namespace App\Http\Controllers\Admin\Vehicles;

use App\Http\Controllers\Admin\Vehicles\Concerns\UsesVehicleAdminHelpers;
use App\Http\Controllers\Controller;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Admin\Vehicle\VehicleDocument;
use App\Models\Admin\Vehicle\VehicleMaintenance;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MaintenanceController extends Controller
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
            'status' => (string) $request->input('status', ''),
            'date_from' => (string) $request->input('date_from', ''),
            'date_to' => (string) $request->input('date_to', ''),
        ];

        $baseQuery = $this->maintenanceBaseQuery($carrierId, $vehicleId);
        $query = clone $baseQuery;

        if ($filters['search'] !== '') {
            $term = '%' . $filters['search'] . '%';
            $query->where(function (Builder $builder) use ($term) {
                $builder
                    ->where('unit', 'like', $term)
                    ->orWhere('service_tasks', 'like', $term)
                    ->orWhere('vendor_mechanic', 'like', $term)
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

        if ($filters['date_from'] !== '' && $from = $this->parseUsDate($filters['date_from'])) {
            $query->whereDate('service_date', '>=', $from->toDateString());
        }

        if ($filters['date_to'] !== '' && $to = $this->parseUsDate($filters['date_to'])) {
            $query->whereDate('service_date', '<=', $to->toDateString());
        }

        $this->applyStatusFilter($query, $filters['status']);

        $maintenances = $query
            ->orderByDesc('service_date')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        $maintenances->through(fn (VehicleMaintenance $maintenance) => $this->maintenanceRow($maintenance));

        $summaryQuery = clone $baseQuery;
        $overdueItems = (clone $summaryQuery)
            ->overdue()
            ->orderBy('next_service_date')
            ->limit(5)
            ->get()
            ->map(fn (VehicleMaintenance $maintenance) => $this->maintenanceSummaryCard($maintenance))
            ->values()
            ->all();

        $upcomingItems = (clone $summaryQuery)
            ->upcoming()
            ->orderBy('next_service_date')
            ->limit(5)
            ->get()
            ->map(fn (VehicleMaintenance $maintenance) => $this->maintenanceSummaryCard($maintenance))
            ->values()
            ->all();

        return Inertia::render('admin/maintenance/Index', [
            'maintenances' => $maintenances,
            'filters' => $filters,
            'carriers' => $this->carrierOptions(),
            'vehicles' => $this->vehicleOptions($carrierId),
            'statusOptions' => $this->statusOptions(),
            'stats' => [
                'total' => (clone $summaryQuery)->count(),
                'pending' => (clone $summaryQuery)->pending()->count(),
                'completed' => (clone $summaryQuery)->completed()->count(),
                'overdue' => (clone $summaryQuery)->overdue()->count(),
                'upcoming' => (clone $summaryQuery)->upcoming()->count(),
                'historical' => (clone $summaryQuery)->where('is_historical', true)->count(),
            ],
            'overdueItems' => $overdueItems,
            'upcomingItems' => $upcomingItems,
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

        return Inertia::render('admin/maintenance/Create', [
            'maintenance' => null,
            'carriers' => $this->carrierOptions(),
            'vehicles' => $this->vehicleOptions($carrierId),
            'maintenanceTypes' => $this->maintenanceTypes(),
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

        $maintenance = DB::transaction(function () use ($request, $validated, $vehicle) {
            $maintenance = VehicleMaintenance::create($this->maintenancePayload($request, $validated, $vehicle));

            $this->storeAttachments($maintenance, $request);

            return $maintenance;
        });

        return redirect()
            ->route('admin.maintenance.show', $maintenance)
            ->with('success', 'Maintenance record created successfully.');
    }

    public function show(VehicleMaintenance $maintenance): Response
    {
        $maintenance->load(['vehicle.carrier', 'media']);
        $this->authorizeVehicle($maintenance->vehicle);

        $generatedReports = $maintenance->vehicle->documents()
            ->where('document_type', VehicleDocument::DOC_TYPE_MAINTENANCE_RECORD)
            ->where('notes', 'like', '%' . $this->reportNoteToken($maintenance) . '%')
            ->latest('created_at')
            ->get()
            ->map(fn (VehicleDocument $document) => $this->reportRow($document))
            ->values()
            ->all();

        return Inertia::render('admin/maintenance/Show', [
            'maintenance' => [
                'id' => $maintenance->id,
                'vehicle' => $this->vehicleContextPayload($maintenance->vehicle),
                'unit' => $maintenance->unit,
                'service_tasks' => $maintenance->service_tasks,
                'vendor_mechanic' => $maintenance->vendor_mechanic,
                'service_date' => $this->formatDateForUi($maintenance->service_date),
                'next_service_date' => $this->formatDateForUi($maintenance->next_service_date),
                'cost' => $maintenance->cost !== null ? '$' . number_format((float) $maintenance->cost, 2) : null,
                'odometer' => $maintenance->odometer ? number_format((int) $maintenance->odometer) : null,
                'description' => $maintenance->description,
                'notes' => $maintenance->notes,
                'status' => $maintenance->status,
                'status_label' => $maintenance->status ? 'Completed' : ($maintenance->isOverdue() ? 'Overdue' : ($maintenance->isUpcoming() ? 'Upcoming' : 'Pending')),
                'is_historical' => (bool) $maintenance->is_historical,
                'attachments' => $maintenance->getMedia('maintenance_files')->map(fn (Media $media) => [
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

    public function edit(VehicleMaintenance $maintenance): Response
    {
        $maintenance->load('vehicle.carrier:id,name');
        $this->authorizeVehicle($maintenance->vehicle);

        return Inertia::render('admin/maintenance/Edit', [
            'maintenance' => $this->maintenanceFormPayload($maintenance),
            'carriers' => $this->carrierOptions(),
            'vehicles' => $this->vehicleOptions((int) $maintenance->vehicle->carrier_id),
            'maintenanceTypes' => $this->maintenanceTypes(),
            'selectedCarrierId' => (int) $maintenance->vehicle->carrier_id,
            'contextVehicle' => $this->vehicleContextPayload($maintenance->vehicle),
            'isSuperadmin' => $this->isSuperadmin(),
        ]);
    }

    public function update(Request $request, VehicleMaintenance $maintenance): RedirectResponse
    {
        $maintenance->load('vehicle');
        $this->authorizeVehicle($maintenance->vehicle);

        $validated = $this->validatePayload($request, $maintenance);
        $vehicle = Vehicle::query()->findOrFail((int) $validated['vehicle_id']);
        $this->authorizeVehicle($vehicle);

        DB::transaction(function () use ($request, $validated, $maintenance, $vehicle) {
            $maintenance->update($this->maintenancePayload($request, $validated, $vehicle));
            $this->storeAttachments($maintenance, $request);
        });

        return redirect()
            ->route('admin.maintenance.show', $maintenance)
            ->with('success', 'Maintenance record updated successfully.');
    }

    public function destroy(VehicleMaintenance $maintenance): RedirectResponse
    {
        $maintenance->load('vehicle');
        $this->authorizeVehicle($maintenance->vehicle);

        DB::transaction(function () use ($maintenance) {
            $maintenance->clearMediaCollection('maintenance_files');
            $maintenance->delete();
        });

        return redirect()
            ->route('admin.maintenance.index')
            ->with('success', 'Maintenance record deleted successfully.');
    }

    public function toggleStatus(VehicleMaintenance $maintenance): RedirectResponse
    {
        $maintenance->load('vehicle');
        $this->authorizeVehicle($maintenance->vehicle);

        $maintenance->update([
            'status' => ! $maintenance->status,
        ]);

        return back()->with('success', 'Maintenance status updated successfully.');
    }

    public function reschedule(Request $request, VehicleMaintenance $maintenance): RedirectResponse
    {
        $maintenance->load('vehicle');
        $this->authorizeVehicle($maintenance->vehicle);

        $validator = Validator::make($request->all(), [
            'next_service_date' => ['required', 'string'],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        $validator->after(function ($validator) use ($request) {
            $date = $this->parseUsDate((string) $request->input('next_service_date'));

            if (! $date) {
                $validator->errors()->add('next_service_date', 'Invalid date format. Use M/D/YYYY.');
                return;
            }

            if ($date->lt(now()->startOfDay())) {
                $validator->errors()->add('next_service_date', 'The new service date must be today or later.');
            }
        });

        $validated = $validator->validate();
        $date = $this->parseUsDate($validated['next_service_date']);

        $noteLine = sprintf(
            "[%s] Rescheduled to %s. Reason: %s",
            now()->format('Y-m-d H:i:s'),
            $date?->format('n/j/Y'),
            trim($validated['reason'])
        );

        $maintenance->update([
            'next_service_date' => $date?->format('Y-m-d'),
            'notes' => trim(implode("\n\n", array_filter([$maintenance->notes, $noteLine]))),
        ]);

        return back()->with('success', 'Maintenance was rescheduled successfully.');
    }

    public function storeDocuments(Request $request, VehicleMaintenance $maintenance): RedirectResponse
    {
        $maintenance->load('vehicle');
        $this->authorizeVehicle($maintenance->vehicle);

        $request->validate([
            'attachments' => ['required', 'array', 'min:1'],
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx'],
        ]);

        $this->storeAttachments($maintenance, $request);

        return back()->with('success', 'Documents uploaded successfully.');
    }

    public function deleteDocument(VehicleMaintenance $maintenance, Media $media): RedirectResponse
    {
        $maintenance->load('vehicle');
        $this->authorizeVehicle($maintenance->vehicle);

        abort_unless($maintenance->media()->whereKey($media->id)->exists(), 404);

        $media->delete();

        return back()->with('success', 'Attachment deleted successfully.');
    }

    public function generateReport(VehicleMaintenance $maintenance): RedirectResponse
    {
        $maintenance->load('vehicle.carrier');
        $this->authorizeVehicle($maintenance->vehicle);

        $fileName = 'maintenance-report-' . $maintenance->id . '-' . now()->format('YmdHis') . '.pdf';
        $tempDir = storage_path('app/temp');
        $tempPath = $tempDir . DIRECTORY_SEPARATOR . $fileName;

        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        Pdf::loadView('admin.vehicles.maintenance.report-pdf', [
            'maintenance' => $maintenance,
            'vehicle' => $maintenance->vehicle,
        ])->setPaper('letter', 'portrait')->save($tempPath);

        try {
            $document = VehicleDocument::create([
                'vehicle_id' => $maintenance->vehicle_id,
                'document_type' => VehicleDocument::DOC_TYPE_MAINTENANCE_RECORD,
                'document_number' => 'MR-' . $maintenance->vehicle_id . '-' . $maintenance->id . '-' . now()->format('Ymd'),
                'issued_date' => now()->toDateString(),
                'status' => VehicleDocument::STATUS_ACTIVE,
                'notes' => 'Maintenance report generated. ' . $this->reportNoteToken($maintenance),
            ]);

            $document->addMedia($tempPath)
                ->usingFileName($fileName)
                ->toMediaCollection('document_files');
        } finally {
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }

        return back()->with('success', 'Maintenance report generated and saved to vehicle documents.');
    }

    public function deleteReport(VehicleMaintenance $maintenance, VehicleDocument $document): RedirectResponse
    {
        $maintenance->load('vehicle');
        $this->authorizeVehicle($maintenance->vehicle);

        abort_unless(
            (int) $document->vehicle_id === (int) $maintenance->vehicle_id
            && $document->document_type === VehicleDocument::DOC_TYPE_MAINTENANCE_RECORD
            && str_contains((string) $document->notes, $this->reportNoteToken($maintenance)),
            404
        );

        $document->clearMediaCollection('document_files');
        $document->delete();

        return back()->with('success', 'Generated report deleted successfully.');
    }

    public function calendar(Request $request): Response
    {
        $carrierId = $this->resolvedCarrierId($request);
        $vehicleId = $request->filled('vehicle_id') ? (int) $request->input('vehicle_id') : null;
        $month = $this->parseCalendarMonth((string) $request->input('month', now()->format('Y-m')));
        $contextVehicle = $vehicleId ? Vehicle::query()->with('carrier:id,name')->findOrFail($vehicleId) : null;

        if ($contextVehicle) {
            $this->authorizeVehicle($contextVehicle);
        }

        $filters = [
            'carrier_id' => $this->isSuperadmin() ? (string) $request->input('carrier_id', '') : (string) ($carrierId ?? ''),
            'vehicle_id' => $vehicleId ? (string) $vehicleId : '',
            'status' => (string) $request->input('status', ''),
            'month' => $month->format('Y-m'),
        ];

        $gridStart = $month->copy()->startOfMonth()->startOfWeek();
        $gridEnd = $month->copy()->endOfMonth()->endOfWeek();

        $query = $this->maintenanceBaseQuery($carrierId, $vehicleId)
            ->whereBetween('next_service_date', [$gridStart->toDateString(), $gridEnd->toDateString()]);

        $this->applyStatusFilter($query, $filters['status']);

        $items = $query->orderBy('next_service_date')->get();

        $itemsByDate = $items
            ->groupBy(fn (VehicleMaintenance $maintenance) => optional($maintenance->next_service_date)->format('Y-m-d'))
            ->map(fn (Collection $dayItems) => $dayItems->map(fn (VehicleMaintenance $maintenance) => [
                'id' => $maintenance->id,
                'title' => $maintenance->service_tasks,
                'vehicle_label' => $this->vehicleLabel($maintenance->vehicle),
                'status' => $maintenance->status ? 'completed' : ($maintenance->isOverdue() ? 'overdue' : 'pending'),
                'show_url' => route('admin.maintenance.show', $maintenance),
            ])->values()->all())
            ->all();

        return Inertia::render('admin/maintenance/Calendar', [
            'filters' => $filters,
            'carriers' => $this->carrierOptions(),
            'vehicles' => $this->vehicleOptions($carrierId),
            'statusOptions' => $this->statusOptions(),
            'calendar' => [
                'month_label' => $month->format('F Y'),
                'previous_month' => $month->copy()->subMonth()->format('Y-m'),
                'next_month' => $month->copy()->addMonth()->format('Y-m'),
                'days' => $this->calendarDays($month, $itemsByDate),
            ],
            'upcomingItems' => $this->maintenanceBaseQuery($carrierId, $vehicleId)
                ->upcoming()
                ->orderBy('next_service_date')
                ->limit(8)
                ->get()
                ->map(fn (VehicleMaintenance $maintenance) => $this->maintenanceSummaryCard($maintenance))
                ->values()
                ->all(),
            'contextVehicle' => $contextVehicle ? $this->vehicleContextPayload($contextVehicle) : null,
            'isSuperadmin' => $this->isSuperadmin(),
        ]);
    }

    public function reports(Request $request): Response
    {
        $carrierId = $this->resolvedCarrierId($request);
        $vehicleId = $request->filled('vehicle_id') ? (int) $request->input('vehicle_id') : null;
        $contextVehicle = $vehicleId ? Vehicle::query()->with('carrier:id,name')->findOrFail($vehicleId) : null;

        if ($contextVehicle) {
            $this->authorizeVehicle($contextVehicle);
        }

        $filters = [
            'carrier_id' => $this->isSuperadmin() ? (string) $request->input('carrier_id', '') : (string) ($carrierId ?? ''),
            'vehicle_id' => $vehicleId ? (string) $vehicleId : '',
            'period' => (string) $request->input('period', 'this_month'),
            'status' => (string) $request->input('status', ''),
            'start_date' => (string) $request->input('start_date', ''),
            'end_date' => (string) $request->input('end_date', ''),
        ];

        [$rangeStart, $rangeEnd] = $this->resolveReportRange($filters['period'], $filters['start_date'], $filters['end_date']);

        $query = $this->maintenanceBaseQuery($carrierId, $vehicleId);

        if ($rangeStart && $rangeEnd) {
            $query->whereBetween('service_date', [$rangeStart->toDateString(), $rangeEnd->toDateString()]);
        }

        $this->applyStatusFilter($query, $filters['status']);

        $records = $query
            ->orderByDesc('service_date')
            ->paginate(15)
            ->withQueryString();

        $records->through(fn (VehicleMaintenance $maintenance) => $this->maintenanceRow($maintenance));

        $statsQuery = clone $query;
        $allRows = (clone $statsQuery)->get();
        $serviceTypeDistribution = $allRows
            ->pluck('service_tasks')
            ->filter()
            ->countBy()
            ->map(fn ($count, $label) => ['label' => $label, 'count' => (int) $count])
            ->values()
            ->sortByDesc('count')
            ->take(8)
            ->values()
            ->all();

        $costByMonth = collect(range(5, 0))
            ->map(function (int $monthsAgo) use ($carrierId, $vehicleId, $filters) {
                $month = now()->copy()->subMonths($monthsAgo);
                $monthQuery = $this->maintenanceBaseQuery($carrierId, $vehicleId)
                    ->whereYear('service_date', $month->year)
                    ->whereMonth('service_date', $month->month);

                $this->applyStatusFilter($monthQuery, $filters['status']);

                return [
                    'label' => $month->format('M Y'),
                    'count' => $monthQuery->count(),
                    'cost' => (float) $monthQuery->sum('cost'),
                ];
            })
            ->values()
            ->all();

        return Inertia::render('admin/maintenance/Reports', [
            'filters' => $filters,
            'carriers' => $this->carrierOptions(),
            'vehicles' => $this->vehicleOptions($carrierId),
            'statusOptions' => $this->statusOptions(),
            'periodOptions' => $this->periodOptions(),
            'records' => $records,
            'serviceTypeDistribution' => $serviceTypeDistribution,
            'costByMonth' => $costByMonth,
            'stats' => [
                'total' => (clone $statsQuery)->count(),
                'completed' => (clone $statsQuery)->completed()->count(),
                'pending' => (clone $statsQuery)->pending()->count(),
                'overdue' => (clone $statsQuery)->overdue()->count(),
                'total_cost' => (float) ((clone $statsQuery)->sum('cost') ?: 0),
                'avg_cost' => round((float) ((clone $statsQuery)->avg('cost') ?: 0), 2),
                'vehicles_serviced' => $allRows->pluck('vehicle_id')->unique()->count(),
            ],
            'contextVehicle' => $contextVehicle ? $this->vehicleContextPayload($contextVehicle) : null,
            'isSuperadmin' => $this->isSuperadmin(),
        ]);
    }

    public function vehicleIndex(Vehicle $vehicle): RedirectResponse
    {
        $this->authorizeVehicle($vehicle);

        return redirect()->route('admin.maintenance.index', [
            'vehicle_id' => $vehicle->id,
        ]);
    }

    public function createForVehicle(Vehicle $vehicle): RedirectResponse
    {
        $this->authorizeVehicle($vehicle);

        return redirect()->route('admin.maintenance.create', [
            'vehicle_id' => $vehicle->id,
        ]);
    }

    protected function maintenanceBaseQuery(?int $carrierId, ?int $vehicleId = null): Builder
    {
        $query = VehicleMaintenance::query()->with(['vehicle.carrier', 'media']);
        $this->applyVehicleRelationCarrierScope($query, $carrierId);

        if ($vehicleId) {
            $query->where('vehicle_id', $vehicleId);
        }

        return $query;
    }

    protected function validatePayload(Request $request, ?VehicleMaintenance $maintenance = null): array
    {
        $validator = Validator::make($request->all(), [
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'unit' => ['required', 'string', 'max:255'],
            'service_tasks' => ['required', 'string', 'max:255'],
            'service_date' => ['required', 'string'],
            'next_service_date' => ['nullable', 'string'],
            'vendor_mechanic' => ['required', 'string', 'max:255'],
            'cost' => ['required', 'numeric', 'min:0'],
            'odometer' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'status' => ['nullable', 'boolean'],
            'is_historical' => ['nullable', 'boolean'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx'],
        ]);

        $validator->after(function ($validator) use ($request) {
            $serviceDate = $this->parseUsDate((string) $request->input('service_date'));
            $nextServiceDate = $this->parseUsDate((string) $request->input('next_service_date'));

            if (! $serviceDate) {
                $validator->errors()->add('service_date', 'Invalid date format. Use M/D/YYYY.');
            }

            if ($request->filled('next_service_date') && ! $nextServiceDate) {
                $validator->errors()->add('next_service_date', 'Invalid date format. Use M/D/YYYY.');
            }

            if ($serviceDate && $nextServiceDate && ! $request->boolean('is_historical') && $nextServiceDate->lt($serviceDate)) {
                $validator->errors()->add('next_service_date', 'Next service date must be after the service date.');
            }
        });

        return $validator->validate();
    }

    protected function maintenancePayload(Request $request, array $validated, Vehicle $vehicle): array
    {
        $serviceDate = $this->parseUsDate((string) $validated['service_date']);
        $nextServiceDate = $request->filled('next_service_date')
            ? $this->parseUsDate((string) $validated['next_service_date'])
            : $serviceDate?->copy()->addMonths(3);

        return [
            'vehicle_id' => $vehicle->id,
            'unit' => trim((string) $validated['unit']),
            'service_date' => $serviceDate?->format('Y-m-d'),
            'next_service_date' => $nextServiceDate?->format('Y-m-d'),
            'notes' => $this->emptyToNull($validated['notes'] ?? null),
            'service_tasks' => trim((string) $validated['service_tasks']),
            'vendor_mechanic' => trim((string) $validated['vendor_mechanic']),
            'description' => $this->emptyToNull($validated['description'] ?? null),
            'cost' => $validated['cost'],
            'odometer' => $validated['odometer'] !== null && $validated['odometer'] !== '' ? (int) $validated['odometer'] : null,
            'status' => $request->boolean('status'),
            'is_historical' => $request->boolean('is_historical'),
            'created_by' => $request->route('maintenance') instanceof VehicleMaintenance
                ? $request->route('maintenance')->created_by
                : auth()->id(),
            'updated_by' => auth()->id(),
        ];
    }

    protected function maintenanceFormPayload(VehicleMaintenance $maintenance): array
    {
        return [
            'id' => $maintenance->id,
            'vehicle_id' => (string) $maintenance->vehicle_id,
            'carrier_id' => $maintenance->vehicle?->carrier_id ? (string) $maintenance->vehicle->carrier_id : '',
            'unit' => $maintenance->unit,
            'service_tasks' => $maintenance->service_tasks,
            'service_date' => $this->formatDateForUi($maintenance->service_date),
            'next_service_date' => $this->formatDateForUi($maintenance->next_service_date),
            'vendor_mechanic' => $maintenance->vendor_mechanic,
            'cost' => $maintenance->cost !== null ? (string) $maintenance->cost : '',
            'odometer' => $maintenance->odometer !== null ? (string) $maintenance->odometer : '',
            'description' => $maintenance->description,
            'notes' => $maintenance->notes,
            'status' => (bool) $maintenance->status,
            'is_historical' => (bool) $maintenance->is_historical,
            'attachments' => $maintenance->getMedia('maintenance_files')->map(fn (Media $media) => [
                'id' => $media->id,
                'name' => $media->file_name,
                'url' => $media->getUrl(),
                'size' => $media->human_readable_size,
                'mime_type' => $media->mime_type,
            ])->values()->all(),
        ];
    }

    protected function maintenanceRow(VehicleMaintenance $maintenance): array
    {
        return [
            'id' => $maintenance->id,
            'vehicle' => $this->vehicleContextPayload($maintenance->vehicle),
            'unit' => $maintenance->unit,
            'service_tasks' => $maintenance->service_tasks,
            'vendor_mechanic' => $maintenance->vendor_mechanic,
            'service_date' => $this->formatDateForUi($maintenance->service_date),
            'next_service_date' => $this->formatDateForUi($maintenance->next_service_date),
            'cost' => $maintenance->cost !== null ? '$' . number_format((float) $maintenance->cost, 2) : null,
            'odometer' => $maintenance->odometer ? number_format((int) $maintenance->odometer) : null,
            'status' => $maintenance->status ? 'completed' : ($maintenance->isOverdue() ? 'overdue' : ($maintenance->isUpcoming() ? 'upcoming' : 'pending')),
            'status_label' => $maintenance->status ? 'Completed' : ($maintenance->isOverdue() ? 'Overdue' : ($maintenance->isUpcoming() ? 'Upcoming' : 'Pending')),
            'is_historical' => (bool) $maintenance->is_historical,
            'attachments_count' => $maintenance->getMedia('maintenance_files')->count(),
        ];
    }

    protected function maintenanceSummaryCard(VehicleMaintenance $maintenance): array
    {
        return [
            'id' => $maintenance->id,
            'title' => $maintenance->service_tasks,
            'vehicle_label' => $this->vehicleLabel($maintenance->vehicle),
            'service_date' => $this->formatDateForUi($maintenance->service_date),
            'next_service_date' => $this->formatDateForUi($maintenance->next_service_date),
            'status_label' => $maintenance->status ? 'Completed' : ($maintenance->isOverdue() ? 'Overdue' : ($maintenance->isUpcoming() ? 'Upcoming' : 'Pending')),
            'show_url' => route('admin.maintenance.show', $maintenance),
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

    protected function storeAttachments(VehicleMaintenance $maintenance, Request $request): void
    {
        foreach ($request->file('attachments', []) as $file) {
            $maintenance->addMedia($file)->toMediaCollection('maintenance_files');
        }
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

    protected function statusOptions(): array
    {
        return [
            'pending' => 'Pending',
            'completed' => 'Completed',
            'overdue' => 'Overdue',
            'upcoming' => 'Upcoming',
            'historical' => 'Historical',
        ];
    }

    protected function maintenanceTypes(): array
    {
        return [
            'Preventive',
            'Corrective',
            'Inspection',
            'Oil Change',
            'Tire Rotation',
            'Brake Service',
            'Engine Service',
            'Transmission Service',
            'DOT Inspection',
            'Annual Safety Inspection',
            'Other',
        ];
    }

    protected function applyStatusFilter(Builder $query, string $status): void
    {
        match ($status) {
            'completed' => $query->completed(),
            'pending' => $query->pending(),
            'overdue' => $query->overdue(),
            'upcoming' => $query->upcoming(),
            'historical' => $query->where('is_historical', true),
            default => null,
        };
    }

    protected function periodOptions(): array
    {
        return [
            'today' => 'Today',
            'this_week' => 'This Week',
            'this_month' => 'This Month',
            'this_year' => 'This Year',
            'custom' => 'Custom Range',
            'all' => 'All Time',
        ];
    }

    protected function resolveReportRange(string $period, string $startDate, string $endDate): array
    {
        return match ($period) {
            'today' => [now()->copy()->startOfDay(), now()->copy()->endOfDay()],
            'this_week' => [now()->copy()->startOfWeek(), now()->copy()->endOfWeek()],
            'this_year' => [now()->copy()->startOfYear(), now()->copy()->endOfYear()],
            'custom' => [$this->parseUsDate($startDate), $this->parseUsDate($endDate)?->endOfDay()],
            'all' => [null, null],
            default => [now()->copy()->startOfMonth(), now()->copy()->endOfMonth()],
        };
    }

    protected function parseCalendarMonth(string $value): Carbon
    {
        try {
            return Carbon::createFromFormat('Y-m', $value)->startOfMonth();
        } catch (\Throwable) {
            return now()->startOfMonth();
        }
    }

    protected function calendarDays(Carbon $month, array $itemsByDate): array
    {
        $start = $month->copy()->startOfMonth()->startOfWeek();
        $end = $month->copy()->endOfMonth()->endOfWeek();
        $days = [];

        for ($cursor = $start->copy(); $cursor->lte($end); $cursor->addDay()) {
            $key = $cursor->format('Y-m-d');
            $days[] = [
                'date' => $key,
                'day' => (int) $cursor->format('j'),
                'in_month' => $cursor->month === $month->month,
                'is_today' => $cursor->isToday(),
                'items' => $itemsByDate[$key] ?? [],
            ];
        }

        return $days;
    }

    protected function reportNoteToken(VehicleMaintenance $maintenance): string
    {
        return 'Maintenance ID #' . $maintenance->id;
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
