<?php

namespace App\Http\Controllers\Admin\Driver;

use App\Http\Controllers\Controller;
use App\Models\Admin\Driver\DriverInspection;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Carrier;
use App\Models\UserDriverDetail;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class InspectionsController extends Controller
{
    public function index(Request $request): Response
    {
        $query = DriverInspection::query()
            ->with([
                'userDriverDetail.user',
                'userDriverDetail.carrier',
                'vehicle',
                'media' => fn ($q) => $q->where('collection_name', 'inspection_documents'),
            ]);

        if ($request->filled('search_term')) {
            $term = trim((string) $request->search_term);
            $query->where(function ($builder) use ($term) {
                $builder
                    ->where('inspection_type', 'like', "%{$term}%")
                    ->orWhere('notes', 'like', "%{$term}%")
                    ->orWhere('inspector_name', 'like', "%{$term}%")
                    ->orWhereHas('userDriverDetail.user', fn ($userQuery) => $userQuery->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('userDriverDetail.carrier', fn ($carrierQuery) => $carrierQuery->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('vehicle', function ($vehicleQuery) use ($term) {
                        $vehicleQuery
                            ->where('company_unit_number', 'like', "%{$term}%")
                            ->orWhere('make', 'like', "%{$term}%")
                            ->orWhere('model', 'like', "%{$term}%");
                    });
            });
        }

        if ($request->filled('driver_filter')) {
            $query->where('user_driver_detail_id', $request->driver_filter);
        }

        if ($request->filled('carrier_filter')) {
            $query->whereHas('userDriverDetail', fn ($driverQuery) => $driverQuery->where('carrier_id', $request->carrier_filter));
        }

        if ($request->filled('vehicle_filter')) {
            $query->where('vehicle_id', $request->vehicle_filter);
        }

        if ($request->filled('date_from') && ($dateFrom = $this->parseUsDate($request->date_from))) {
            $query->whereDate('inspection_date', '>=', $dateFrom->format('Y-m-d'));
        }

        if ($request->filled('date_to') && ($dateTo = $this->parseUsDate($request->date_to))) {
            $query->whereDate('inspection_date', '<=', $dateTo->format('Y-m-d'));
        }

        if ($request->filled('inspection_type')) {
            $query->where('inspection_type', $request->inspection_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $sortField = in_array($request->get('sort_field'), ['inspection_date', 'created_at', 'inspection_type', 'status'], true)
            ? $request->get('sort_field')
            : 'inspection_date';
        $sortDirection = $request->get('sort_direction') === 'asc' ? 'asc' : 'desc';

        $inspections = $query->orderBy($sortField, $sortDirection)->paginate(10)->withQueryString();
        $inspections->getCollection()->transform(fn (DriverInspection $inspection) => $this->inspectionRow($inspection));

        return Inertia::render('admin/inspections/Index', [
            'inspections' => $inspections,
            'drivers' => $this->driverOptions(),
            'carriers' => $this->carrierOptions(),
            'vehicles' => $this->vehicleOptions(),
            'inspectionTypes' => $this->inspectionTypeOptions(),
            'statuses' => $this->statusOptions(),
            'filters' => [
                'search_term' => (string) $request->get('search_term', ''),
                'carrier_filter' => (string) $request->get('carrier_filter', ''),
                'driver_filter' => (string) $request->get('driver_filter', ''),
                'vehicle_filter' => (string) $request->get('vehicle_filter', ''),
                'date_from' => (string) $request->get('date_from', ''),
                'date_to' => (string) $request->get('date_to', ''),
                'inspection_type' => (string) $request->get('inspection_type', ''),
                'status' => (string) $request->get('status', ''),
                'sort_field' => $sortField,
                'sort_direction' => $sortDirection,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/inspections/Create', [
            'carriers' => $this->carrierOptions(),
            'drivers' => $this->driverOptions(),
            'vehicles' => $this->vehicleOptions(),
            'inspectionTypes' => $this->inspectionTypeOptions(),
            'inspectionLevels' => $this->inspectionLevelOptions(),
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function edit(DriverInspection $inspection): Response
    {
        $inspection->load(['userDriverDetail.user', 'userDriverDetail.carrier', 'vehicle']);

        return Inertia::render('admin/inspections/Edit', [
            'inspection' => $this->inspectionPayload($inspection),
            'carriers' => $this->carrierOptions(),
            'drivers' => $this->driverOptions(),
            'vehicles' => $this->vehicleOptions(),
            'inspectionTypes' => $this->inspectionTypeOptions(),
            'inspectionLevels' => $this->inspectionLevelOptions(),
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePayload($request);
        return $this->saveInspection($request, $validated);
    }

    public function update(DriverInspection $inspection, Request $request): RedirectResponse
    {
        $validated = $this->validatePayload($request);
        return $this->saveInspection($request, $validated, $inspection);
    }

    public function destroy(DriverInspection $inspection): RedirectResponse
    {
        $inspection->delete();
        return redirect()->route('admin.inspections.index')->with('success', 'Inspection record deleted successfully.');
    }

    public function documents(Request $request): Response
    {
        $query = Media::query()
            ->where('collection_name', 'inspection_documents')
            ->where('model_type', DriverInspection::class);

        if ($request->filled('driver_filter')) {
            $query->whereJsonContains('custom_properties->driver_id', (int) $request->driver_filter);
        }

        if ($request->filled('carrier_filter')) {
            $inspectionIds = DriverInspection::whereHas('userDriverDetail', fn ($driverQuery) => $driverQuery->where('carrier_id', $request->carrier_filter))
                ->pluck('id');
            $query->whereIn('model_id', $inspectionIds);
        }

        if ($request->filled('date_from') && ($dateFrom = $this->parseUsDate($request->date_from))) {
            $query->whereDate('created_at', '>=', $dateFrom->format('Y-m-d'));
        }

        if ($request->filled('date_to') && ($dateTo = $this->parseUsDate($request->date_to))) {
            $query->whereDate('created_at', '<=', $dateTo->format('Y-m-d'));
        }

        if ($request->filled('search_term')) {
            $term = '%' . trim((string) $request->search_term) . '%';
            $query->where(fn ($builder) => $builder->where('name', 'like', $term)->orWhere('file_name', 'like', $term));
        }

        $sortField = in_array($request->get('sort_field'), ['created_at', 'file_name'], true) ? $request->get('sort_field') : 'created_at';
        $sortDirection = $request->get('sort_direction') === 'asc' ? 'asc' : 'desc';

        $query->with(['model' => fn (MorphTo $morphTo) => $morphTo->morphWith([
            DriverInspection::class => ['userDriverDetail.user', 'userDriverDetail.carrier', 'vehicle'],
        ])]);
        $documents = $query->orderBy($sortField, $sortDirection)->paginate(20)->withQueryString();
        $documents->getCollection()->transform(fn (Media $media) => $this->documentRow($media));

        return Inertia::render('admin/inspections/Documents', [
            'documents' => $documents,
            'drivers' => $this->driverOptions(),
            'carriers' => $this->carrierOptions(),
            'filters' => [
                'search_term' => (string) $request->get('search_term', ''),
                'carrier_filter' => (string) $request->get('carrier_filter', ''),
                'driver_filter' => (string) $request->get('driver_filter', ''),
                'date_from' => (string) $request->get('date_from', ''),
                'date_to' => (string) $request->get('date_to', ''),
                'sort_field' => $sortField,
                'sort_direction' => $sortDirection,
            ],
        ]);
    }

    public function driverHistory(UserDriverDetail $driver, Request $request): Response
    {
        $query = $driver->inspections()->with([
            'userDriverDetail.user',
            'userDriverDetail.carrier',
            'vehicle',
            'media' => fn ($q) => $q->where('collection_name', 'inspection_documents'),
        ]);

        if ($request->filled('search_term')) {
            $term = trim((string) $request->search_term);
            $query->where(function ($builder) use ($term) {
                $builder
                    ->where('inspection_type', 'like', "%{$term}%")
                    ->orWhere('notes', 'like', "%{$term}%")
                    ->orWhere('inspector_name', 'like', "%{$term}%");
            });
        }

        if ($request->filled('vehicle_filter')) {
            $query->where('vehicle_id', $request->vehicle_filter);
        }

        if ($request->filled('inspection_type')) {
            $query->where('inspection_type', $request->inspection_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $sortField = in_array($request->get('sort_field'), ['inspection_date', 'created_at', 'inspection_type', 'status'], true)
            ? $request->get('sort_field')
            : 'inspection_date';
        $sortDirection = $request->get('sort_direction') === 'asc' ? 'asc' : 'desc';

        $inspections = $query->orderBy($sortField, $sortDirection)->paginate(10)->withQueryString();
        $inspections->getCollection()->transform(fn (DriverInspection $inspection) => $this->inspectionRow($inspection));

        $driver->loadMissing(['user', 'carrier']);

        return Inertia::render('admin/inspections/DriverHistory', [
            'driver' => [
                'id' => $driver->id,
                'name' => $this->driverFullName($driver),
                'carrier_name' => $driver->carrier?->name,
            ],
            'inspections' => $inspections,
            'vehicles' => $this->vehicleOptions($driver->id),
            'inspectionTypes' => DriverInspection::where('user_driver_detail_id', $driver->id)->distinct()->pluck('inspection_type')->filter()->values(),
            'statuses' => DriverInspection::where('user_driver_detail_id', $driver->id)->distinct()->pluck('status')->filter()->values(),
            'filters' => [
                'search_term' => (string) $request->get('search_term', ''),
                'vehicle_filter' => (string) $request->get('vehicle_filter', ''),
                'inspection_type' => (string) $request->get('inspection_type', ''),
                'status' => (string) $request->get('status', ''),
                'sort_field' => $sortField,
                'sort_direction' => $sortDirection,
            ],
        ]);
    }

    public function driverDocuments(UserDriverDetail $driver, Request $request): Response
    {
        $inspectionIds = DriverInspection::where('user_driver_detail_id', $driver->id)->pluck('id');
        $query = Media::query()
            ->where('collection_name', 'inspection_documents')
            ->where('model_type', DriverInspection::class)
            ->whereIn('model_id', $inspectionIds);

        if ($request->filled('date_from') && ($dateFrom = $this->parseUsDate($request->date_from))) {
            $query->whereDate('created_at', '>=', $dateFrom->format('Y-m-d'));
        }

        if ($request->filled('date_to') && ($dateTo = $this->parseUsDate($request->date_to))) {
            $query->whereDate('created_at', '<=', $dateTo->format('Y-m-d'));
        }

        if ($request->filled('search_term')) {
            $term = '%' . trim((string) $request->search_term) . '%';
            $query->where(fn ($builder) => $builder->where('name', 'like', $term)->orWhere('file_name', 'like', $term));
        }

        $sortField = in_array($request->get('sort_field'), ['created_at', 'file_name'], true) ? $request->get('sort_field') : 'created_at';
        $sortDirection = $request->get('sort_direction') === 'asc' ? 'asc' : 'desc';

        $query->with(['model' => fn (MorphTo $morphTo) => $morphTo->morphWith([
            DriverInspection::class => ['userDriverDetail.user', 'userDriverDetail.carrier', 'vehicle'],
        ])]);
        $documents = $query->orderBy($sortField, $sortDirection)->paginate(20)->withQueryString();
        $documents->getCollection()->transform(fn (Media $media) => $this->documentRow($media));

        $driver->loadMissing(['user', 'carrier']);

        return Inertia::render('admin/inspections/DriverDocuments', [
            'driver' => [
                'id' => $driver->id,
                'name' => $this->driverFullName($driver),
                'carrier_name' => $driver->carrier?->name,
                'status_name' => (string) ($driver->status_name ?? 'Unknown'),
            ],
            'documents' => $documents,
            'filters' => [
                'search_term' => (string) $request->get('search_term', ''),
                'date_from' => (string) $request->get('date_from', ''),
                'date_to' => (string) $request->get('date_to', ''),
                'sort_field' => $sortField,
                'sort_direction' => $sortDirection,
            ],
        ]);
    }

    public function destroyMedia(Media $media): RedirectResponse
    {
        abort_unless($media->model_type === DriverInspection::class, 404);
        $inspection = DriverInspection::findOrFail($media->model_id);
        $inspection->safeDeleteMedia($media->id);

        return back()->with('success', 'Inspection document deleted successfully.');
    }

    protected function saveInspection(Request $request, array $validated, ?DriverInspection $inspection = null): RedirectResponse
    {
        $driver = UserDriverDetail::findOrFail($validated['user_driver_detail_id']);
        if ((int) $driver->carrier_id !== (int) $validated['carrier_id']) {
            return back()->withErrors(['user_driver_detail_id' => 'The selected driver does not belong to the selected carrier.'])->withInput();
        }

        if (! empty($validated['vehicle_id'])) {
            $vehicle = Vehicle::findOrFail($validated['vehicle_id']);
            if ((int) $vehicle->carrier_id !== (int) $validated['carrier_id']) {
                return back()->withErrors(['vehicle_id' => 'The selected vehicle does not belong to the selected carrier.'])->withInput();
            }
        }

        $inspectionDate = $this->parseUsDate($validated['inspection_date']);
        $correctedDate = $this->parseUsDate($validated['defects_corrected_date'] ?? null);

        if (! $inspectionDate) {
            return back()->withErrors(['inspection_date' => 'Invalid inspection date format.'])->withInput();
        }

        DB::transaction(function () use ($request, $validated, $driver, $inspectionDate, $correctedDate, $inspection) {
            $record = $inspection ?? new DriverInspection();
            $record->fill([
                'user_driver_detail_id' => $driver->id,
                'vehicle_id' => $validated['vehicle_id'] ?? null,
                'inspection_date' => $inspectionDate->format('Y-m-d'),
                'inspection_type' => $validated['inspection_type'],
                'inspection_level' => $validated['inspection_level'] ?? null,
                'inspector_name' => $validated['inspector_name'],
                'inspector_number' => $validated['inspector_number'] ?? null,
                'location' => $validated['location'] ?? null,
                'status' => $validated['status'] ?? null,
                'defects_found' => $validated['defects_found'] ?? null,
                'corrective_actions' => $validated['corrective_actions'] ?? null,
                'is_defects_corrected' => $request->boolean('is_defects_corrected'),
                'defects_corrected_date' => $request->boolean('is_defects_corrected') ? $correctedDate?->format('Y-m-d') : null,
                'corrected_by' => $validated['corrected_by'] ?? null,
                'is_vehicle_safe_to_operate' => $request->boolean('is_vehicle_safe_to_operate', true),
                'notes' => $validated['notes'] ?? null,
            ]);
            $record->save();

            foreach ($request->file('attachments', []) as $file) {
                $record->addMedia($file)
                    ->usingName(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                    ->withCustomProperties([
                        'inspection_id' => $record->id,
                        'driver_id' => $record->user_driver_detail_id,
                    ])
                    ->toMediaCollection('inspection_documents');
            }
        });

        $target = $inspection ? route('admin.inspections.edit', $inspection) : route('admin.inspections.index');
        return redirect($target)->with('success', $inspection ? 'Inspection record updated successfully.' : 'Inspection record created successfully.');
    }

    protected function validatePayload(Request $request): array
    {
        return $request->validate([
            'carrier_id' => 'required|exists:carriers,id',
            'user_driver_detail_id' => 'required|exists:user_driver_details,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'inspection_date' => 'required|string',
            'inspection_type' => 'required|string|max:255',
            'inspection_level' => 'nullable|string|max:255',
            'inspector_name' => 'required|string|max:100',
            'inspector_number' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:200',
            'status' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'defects_found' => 'nullable|string',
            'corrective_actions' => 'nullable|string',
            'is_defects_corrected' => 'nullable|boolean',
            'defects_corrected_date' => 'nullable|string',
            'corrected_by' => 'nullable|string|max:100',
            'is_vehicle_safe_to_operate' => 'nullable|boolean',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx',
        ]);
    }

    protected function inspectionRow(DriverInspection $inspection): array
    {
        return [
            'id' => $inspection->id,
            'created_at_display' => $inspection->created_at?->format('n/j/Y'),
            'inspection_date_display' => $inspection->inspection_date?->format('n/j/Y'),
            'inspection_type' => $inspection->inspection_type,
            'inspection_level' => $inspection->inspection_level,
            'inspector_name' => $inspection->inspector_name,
            'status' => $inspection->status,
            'document_count' => $inspection->relationLoaded('media')
                ? $inspection->media->where('collection_name', 'inspection_documents')->count()
                : $inspection->getMedia('inspection_documents')->count(),
            'driver' => $inspection->userDriverDetail ? [
                'id' => $inspection->userDriverDetail->id,
                'name' => $this->driverFullName($inspection->userDriverDetail),
                'email' => $inspection->userDriverDetail->user?->email,
            ] : null,
            'carrier' => $inspection->userDriverDetail?->carrier ? [
                'id' => $inspection->userDriverDetail->carrier->id,
                'name' => $inspection->userDriverDetail->carrier->name,
            ] : null,
            'vehicle' => $inspection->vehicle ? [
                'id' => $inspection->vehicle->id,
                'label' => $this->vehicleLabel($inspection->vehicle),
            ] : null,
        ];
    }

    protected function inspectionPayload(DriverInspection $inspection): array
    {
        return [
            'id' => $inspection->id,
            'carrier_id' => $inspection->userDriverDetail?->carrier_id,
            'user_driver_detail_id' => $inspection->user_driver_detail_id,
            'vehicle_id' => $inspection->vehicle_id,
            'driver_name' => $this->driverFullName($inspection->userDriverDetail),
            'inspection_date' => $inspection->inspection_date?->format('n/j/Y'),
            'inspection_type' => $inspection->inspection_type,
            'inspection_level' => $inspection->inspection_level,
            'inspector_name' => $inspection->inspector_name,
            'inspector_number' => $inspection->inspector_number,
            'location' => $inspection->location,
            'status' => $inspection->status,
            'defects_found' => $inspection->defects_found,
            'corrective_actions' => $inspection->corrective_actions,
            'is_defects_corrected' => (bool) $inspection->is_defects_corrected,
            'defects_corrected_date' => $inspection->defects_corrected_date?->format('n/j/Y'),
            'corrected_by' => $inspection->corrected_by,
            'is_vehicle_safe_to_operate' => (bool) $inspection->is_vehicle_safe_to_operate,
            'notes' => $inspection->notes,
            'documents' => $inspection->getMedia('inspection_documents')->map(fn (Media $media) => [
                'id' => $media->id,
                'name' => $media->file_name,
                'url' => $media->getUrl(),
                'size' => $media->human_readable_size,
                'mime_type' => $media->mime_type,
            ])->values(),
        ];
    }

    protected function documentRow(Media $media): array
    {
        /** @var DriverInspection|null $inspection */
        $inspection = $media->model;
        $driver = $inspection?->userDriverDetail;

        return [
            'id' => $media->id,
            'name' => $media->name ?: $media->file_name,
            'file_name' => $media->file_name,
            'mime_type' => $media->mime_type,
            'size_label' => $media->human_readable_size,
            'created_at_display' => $media->created_at?->format('n/j/Y g:i A'),
            'preview_url' => $media->getUrl(),
            'inspection_id' => $inspection?->id,
            'inspection_type' => $inspection?->inspection_type,
            'inspection_date_display' => $inspection?->inspection_date?->format('n/j/Y'),
            'driver_id' => $driver?->id,
            'driver_name' => $this->driverFullName($driver),
            'carrier_name' => $driver?->carrier?->name ?? 'N/A',
            'vehicle_label' => $inspection?->vehicle ? $this->vehicleLabel($inspection->vehicle) : 'N/A',
        ];
    }

    protected function carrierOptions()
    {
        return Carrier::query()->where('status', 1)->orderBy('name')->get(['id', 'name'])->map(fn ($carrier) => [
            'id' => $carrier->id,
            'name' => $carrier->name,
        ])->values();
    }

    protected function driverOptions()
    {
        return UserDriverDetail::query()->with(['user', 'carrier'])->orderByDesc('id')->get()->map(fn ($driver) => [
            'id' => $driver->id,
            'carrier_id' => $driver->carrier_id,
            'carrier_name' => $driver->carrier?->name,
            'name' => $this->driverFullName($driver),
            'email' => $driver->user?->email,
        ])->values();
    }

    protected function vehicleOptions(?int $driverId = null)
    {
        $query = Vehicle::query()->with('carrier')->orderBy('company_unit_number');
        if ($driverId) {
            $query->where(function ($builder) use ($driverId) {
                $builder
                    ->where('user_driver_detail_id', $driverId)
                    ->orWhereHas('driverInspections', fn ($inspectionQuery) => $inspectionQuery->where('user_driver_detail_id', $driverId));
            });
        }

        return $query->get()->map(fn (Vehicle $vehicle) => [
            'id' => $vehicle->id,
            'carrier_id' => $vehicle->carrier_id,
            'label' => $this->vehicleLabel($vehicle),
        ])->values();
    }

    protected function inspectionTypeOptions()
    {
        $defaults = collect(['DOT Roadside', 'State Police', 'Annual DOT', 'Pre-trip', 'Post-trip', 'Border Crossing', 'Weigh Station']);
        return $defaults->merge(DriverInspection::distinct()->pluck('inspection_type')->filter())->unique()->values();
    }

    protected function inspectionLevelOptions()
    {
        return collect([
            'Level I',
            'Level II',
            'Level III',
            'Level IV',
            'Level V',
            'Level VI',
        ]);
    }

    protected function statusOptions()
    {
        $defaults = collect(['Pass', 'Fail', 'Conditional Pass', 'Out of Service', 'Pending']);
        return $defaults->merge(DriverInspection::distinct()->pluck('status')->filter())->unique()->values();
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

    protected function vehicleLabel(Vehicle $vehicle): string
    {
        return trim(implode(' ', array_filter([
            $vehicle->company_unit_number,
            $vehicle->year,
            $vehicle->make,
            $vehicle->model,
        ])));
    }

    protected function parseUsDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        foreach (['n/j/Y', 'm/d/Y', 'Y-m-d'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value);
            } catch (\Throwable $e) {
            }
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
