<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Admin\Driver\InspectionsController;
use App\Http\Controllers\Carrier\Concerns\ResolvesCarrierContext;
use App\Models\Admin\Driver\DriverInspection;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Carrier;
use App\Models\UserDriverDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class CarrierDriverInspectionsController extends InspectionsController
{
    use ResolvesCarrierContext;

    public function index(Request $request): Response
    {
        $carrier = $this->resolveCarrier();

        $query = DriverInspection::query()
            ->with(['userDriverDetail.user', 'userDriverDetail.carrier', 'vehicle'])
            ->whereHas('userDriverDetail', fn ($driverQuery) => $driverQuery->where('carrier_id', $carrier->id));

        if ($request->filled('search_term')) {
            $term = trim((string) $request->search_term);
            $query->where(function ($builder) use ($term) {
                $builder
                    ->where('inspection_type', 'like', "%{$term}%")
                    ->orWhere('notes', 'like', "%{$term}%")
                    ->orWhere('inspector_name', 'like', "%{$term}%")
                    ->orWhereHas('userDriverDetail.user', fn ($userQuery) => $userQuery->where('name', 'like', "%{$term}%"))
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

        return Inertia::render('carrier/inspections/Index', [
            'inspections' => $inspections,
            'drivers' => $this->carrierDriverOptions((int) $carrier->id),
            'carriers' => [self::carrierOption($carrier)],
            'vehicles' => $this->carrierVehicleOptions((int) $carrier->id),
            'inspectionTypes' => $this->carrierInspectionTypeOptions((int) $carrier->id),
            'statuses' => $this->carrierStatusOptions((int) $carrier->id),
            'filters' => [
                'search_term' => (string) $request->get('search_term', ''),
                'carrier_filter' => '',
                'driver_filter' => (string) $request->get('driver_filter', ''),
                'vehicle_filter' => (string) $request->get('vehicle_filter', ''),
                'date_from' => (string) $request->get('date_from', ''),
                'date_to' => (string) $request->get('date_to', ''),
                'inspection_type' => (string) $request->get('inspection_type', ''),
                'status' => (string) $request->get('status', ''),
                'sort_field' => $sortField,
                'sort_direction' => $sortDirection,
            ],
            'carrier' => self::carrierOption($carrier),
            'isCarrierContext' => true,
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function create(?Request $request = null): Response
    {
        $request ??= request();
        $carrier = $this->resolveCarrier();
        $selectedDriverId = $request->filled('driver_id') ? (int) $request->integer('driver_id') : null;

        if ($selectedDriverId) {
            $this->findCarrierDriverOrFail($selectedDriverId, (int) $carrier->id);
        }

        return Inertia::render('carrier/inspections/Create', [
            'carriers' => [self::carrierOption($carrier)],
            'drivers' => $this->carrierDriverOptions((int) $carrier->id),
            'vehicles' => $this->carrierVehicleOptions((int) $carrier->id, $selectedDriverId),
            'inspectionTypes' => $this->carrierInspectionTypeOptions((int) $carrier->id),
            'inspectionLevels' => $this->inspectionLevelOptions(),
            'statuses' => $this->carrierStatusOptions((int) $carrier->id),
            'carrier' => self::carrierOption($carrier),
            'isCarrierContext' => true,
            'selectedDriverId' => $selectedDriverId ? (string) $selectedDriverId : '',
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function edit(DriverInspection $inspection): Response
    {
        $this->authorizeCarrierInspection($inspection);
        $carrier = $this->resolveCarrier();

        $inspection->load(['userDriverDetail.user', 'userDriverDetail.carrier', 'vehicle']);

        return Inertia::render('carrier/inspections/Edit', [
            'inspection' => $this->inspectionPayload($inspection),
            'carriers' => [self::carrierOption($carrier)],
            'drivers' => $this->carrierDriverOptions((int) $carrier->id),
            'vehicles' => $this->carrierVehicleOptions((int) $carrier->id, $inspection->user_driver_detail_id),
            'inspectionTypes' => $this->carrierInspectionTypeOptions((int) $carrier->id),
            'inspectionLevels' => $this->inspectionLevelOptions(),
            'statuses' => $this->carrierStatusOptions((int) $carrier->id),
            'carrier' => self::carrierOption($carrier),
            'isCarrierContext' => true,
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $carrier = $this->resolveCarrier();
        $validated = $this->validatePayload($request);

        return $this->persistInspection($request, $validated, $carrier);
    }

    public function update(DriverInspection $inspection, Request $request): RedirectResponse
    {
        $this->authorizeCarrierInspection($inspection);
        $carrier = $this->resolveCarrier();
        $validated = $this->validatePayload($request);

        return $this->persistInspection($request, $validated, $carrier, $inspection);
    }

    public function destroy(DriverInspection $inspection): RedirectResponse
    {
        $this->authorizeCarrierInspection($inspection);
        $inspection->delete();

        return redirect()->route('carrier.drivers.inspections.index')->with('success', 'Inspection record deleted successfully.');
    }

    public function documents(Request $request): Response
    {
        $carrier = $this->resolveCarrier();

        $inspectionIds = DriverInspection::query()
            ->whereHas('userDriverDetail', fn ($driverQuery) => $driverQuery->where('carrier_id', $carrier->id))
            ->pluck('id');

        $query = Media::query()
            ->where('collection_name', 'inspection_documents')
            ->where('model_type', DriverInspection::class)
            ->whereIn('model_id', $inspectionIds);

        if ($request->filled('driver_filter')) {
            $query->whereJsonContains('custom_properties->driver_id', (int) $request->driver_filter);
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

        $documents = $query->orderBy($sortField, $sortDirection)->paginate(20)->withQueryString();
        $documents->getCollection()->loadMorph('model', [
            DriverInspection::class => ['userDriverDetail.user', 'userDriverDetail.carrier', 'vehicle'],
        ]);
        $documents->getCollection()->transform(fn (Media $media) => $this->documentRow($media));

        return Inertia::render('carrier/inspections/Documents', [
            'documents' => $documents,
            'drivers' => $this->carrierDriverOptions((int) $carrier->id),
            'carriers' => [self::carrierOption($carrier)],
            'filters' => [
                'search_term' => (string) $request->get('search_term', ''),
                'carrier_filter' => '',
                'driver_filter' => (string) $request->get('driver_filter', ''),
                'date_from' => (string) $request->get('date_from', ''),
                'date_to' => (string) $request->get('date_to', ''),
                'sort_field' => $sortField,
                'sort_direction' => $sortDirection,
            ],
            'carrier' => self::carrierOption($carrier),
            'isCarrierContext' => true,
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function driverHistory(UserDriverDetail $driver, Request $request): Response
    {
        $this->authorizeCarrierDriver($driver);

        $query = $driver->inspections()->with(['userDriverDetail.user', 'userDriverDetail.carrier', 'vehicle']);

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

        return Inertia::render('carrier/inspections/DriverHistory', [
            'driver' => [
                'id' => $driver->id,
                'name' => $this->driverFullName($driver),
                'carrier_name' => $driver->carrier?->name,
            ],
            'inspections' => $inspections,
            'vehicles' => $this->carrierVehicleOptions((int) $this->resolveCarrierId(), $driver->id),
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
            'carrier' => self::carrierOption($this->resolveCarrier()),
            'isCarrierContext' => true,
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function driverDocuments(UserDriverDetail $driver, Request $request): Response
    {
        $this->authorizeCarrierDriver($driver);

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

        $documents = $query->orderBy($sortField, $sortDirection)->paginate(20)->withQueryString();
        $documents->getCollection()->loadMorph('model', [
            DriverInspection::class => ['userDriverDetail.user', 'userDriverDetail.carrier', 'vehicle'],
        ]);
        $documents->getCollection()->transform(fn (Media $media) => $this->documentRow($media));

        $driver->loadMissing(['user', 'carrier']);

        return Inertia::render('carrier/inspections/DriverDocuments', [
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
            'carrier' => self::carrierOption($this->resolveCarrier()),
            'isCarrierContext' => true,
            'routeNames' => $this->routeNames(),
        ]);
    }

    public function destroyMedia(Media $media): RedirectResponse
    {
        abort_unless($media->model_type === DriverInspection::class, 404);

        /** @var DriverInspection $inspection */
        $inspection = DriverInspection::query()->findOrFail($media->model_id);
        $this->authorizeCarrierInspection($inspection);
        $inspection->safeDeleteMedia($media->id);

        return back()->with('success', 'Inspection document deleted successfully.');
    }

    public function getVehiclesByDriver(UserDriverDetail $driver): JsonResponse
    {
        $this->authorizeCarrierDriver($driver);

        return response()->json(
            $this->carrierVehicleOptions((int) $this->resolveCarrierId(), $driver->id)
        );
    }

    protected function persistInspection(Request $request, array $validated, Carrier $carrier, ?DriverInspection $inspection = null): RedirectResponse
    {
        if ((int) $validated['carrier_id'] !== (int) $carrier->id) {
            return back()->withErrors(['carrier_id' => 'The selected carrier is not valid.'])->withInput();
        }

        $driver = $this->findCarrierDriverOrFail((int) $validated['user_driver_detail_id'], (int) $carrier->id);

        if (! empty($validated['vehicle_id'])) {
            $vehicle = Vehicle::query()
                ->where('id', $validated['vehicle_id'])
                ->where('carrier_id', $carrier->id)
                ->first();

            if (! $vehicle) {
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

        $target = $inspection
            ? route('carrier.drivers.inspections.edit', $inspection)
            : route('carrier.drivers.inspections.index');

        return redirect($target)->with('success', $inspection ? 'Inspection record updated successfully.' : 'Inspection record created successfully.');
    }

    protected function authorizeCarrierInspection(DriverInspection $inspection): void
    {
        $inspection->loadMissing('userDriverDetail');
        abort_unless((int) $inspection->userDriverDetail?->carrier_id === (int) $this->resolveCarrierId(), 403);
    }

    protected function authorizeCarrierDriver(UserDriverDetail $driver): void
    {
        abort_unless((int) $driver->carrier_id === (int) $this->resolveCarrierId(), 403);
    }

    protected function findCarrierDriverOrFail(int $driverId, int $carrierId): UserDriverDetail
    {
        return UserDriverDetail::query()
            ->where('id', $driverId)
            ->where('carrier_id', $carrierId)
            ->firstOrFail();
    }

    protected function carrierDriverOptions(int $carrierId)
    {
        return UserDriverDetail::query()
            ->with(['user', 'carrier'])
            ->where('carrier_id', $carrierId)
            ->orderByDesc('id')
            ->get()
            ->map(fn (UserDriverDetail $driver) => [
                'id' => $driver->id,
                'carrier_id' => $driver->carrier_id,
                'carrier_name' => $driver->carrier?->name,
                'name' => $this->driverFullName($driver),
                'email' => $driver->user?->email,
            ])
            ->values();
    }

    protected function carrierVehicleOptions(int $carrierId, ?int $driverId = null)
    {
        $query = Vehicle::query()
            ->with('carrier')
            ->where('carrier_id', $carrierId)
            ->orderBy('company_unit_number');

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

    protected function carrierInspectionTypeOptions(int $carrierId)
    {
        $defaults = collect(['DOT Roadside', 'State Police', 'Annual DOT', 'Pre-trip', 'Post-trip', 'Border Crossing', 'Weigh Station']);

        return $defaults
            ->merge(
                DriverInspection::query()
                    ->whereHas('userDriverDetail', fn ($driverQuery) => $driverQuery->where('carrier_id', $carrierId))
                    ->distinct()
                    ->pluck('inspection_type')
                    ->filter()
            )
            ->unique()
            ->values();
    }

    protected function carrierStatusOptions(int $carrierId)
    {
        $defaults = collect(['Pass', 'Fail', 'Conditional Pass', 'Out of Service', 'Pending']);

        return $defaults
            ->merge(
                DriverInspection::query()
                    ->whereHas('userDriverDetail', fn ($driverQuery) => $driverQuery->where('carrier_id', $carrierId))
                    ->distinct()
                    ->pluck('status')
                    ->filter()
            )
            ->unique()
            ->values();
    }

    protected function routeNames(): array
    {
        return [
            'index' => 'carrier.drivers.inspections.index',
            'create' => 'carrier.drivers.inspections.create',
            'store' => 'carrier.drivers.inspections.store',
            'edit' => 'carrier.drivers.inspections.edit',
            'update' => 'carrier.drivers.inspections.update',
            'destroy' => 'carrier.drivers.inspections.destroy',
            'documentsIndex' => 'carrier.drivers.inspections.documents.index',
            'driverHistory' => 'carrier.drivers.inspections.driver-history',
            'driverDocuments' => 'carrier.drivers.inspections.driver-documents',
            'mediaDestroy' => 'carrier.drivers.inspections.media.destroy',
            'driverShow' => 'carrier.drivers.show',
            'vehiclesByDriver' => 'carrier.drivers.inspections.vehicles.by.driver',
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
