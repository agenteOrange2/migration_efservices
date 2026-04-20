<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Driver\DriverAccident;
use App\Models\Admin\Driver\DriverApplication;
use App\Models\Admin\Driver\DriverTraining;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Admin\Vehicle\VehicleMaintenance;
use App\Models\Carrier;
use App\Models\CarrierDocument;
use App\Models\EmergencyRepair;
use App\Models\MigrationRecord;
use App\Models\Trip;
use App\Models\UserDriverDetail;
use App\Models\Hos\HosDailyLog;
use App\Models\Hos\HosViolation;
use App\Services\Driver\MigrationReportService;
use App\Services\HosReportService;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class ReportsController extends Controller
{
    public function __construct(
        protected ReportService $reportService,
        protected HosReportService $hosReportService,
        protected MigrationReportService $migrationReportService,
    ) {
    }

    public function index(Request $request): InertiaResponse
    {
        $filters = [
            'date_from' => (string) $request->input('date_from', now()->subDays(30)->format('n/j/Y')),
            'date_to' => (string) $request->input('date_to', now()->format('n/j/Y')),
        ];

        $stats = $this->reportService->getSystemOverviewReport([
            'date_from' => $this->parseUsDate($filters['date_from'], now()->subDays(30))?->startOfDay(),
            'date_to' => $this->parseUsDate($filters['date_to'], now())?->endOfDay(),
        ]);

        $quickLinks = [
            ['title' => 'Active Drivers', 'route' => route('admin.reports.active-drivers'), 'icon' => 'UserCheck', 'meta' => (string) ($stats['drivers']['active'] ?? 0) . ' active drivers'],
            ['title' => 'Inactive Drivers', 'route' => route('admin.reports.inactive-drivers'), 'icon' => 'UserMinus', 'meta' => (string) ($stats['drivers']['inactive'] ?? 0) . ' inactive drivers'],
            ['title' => 'Prospect Drivers', 'route' => route('admin.reports.driver-prospects'), 'icon' => 'BadgePlus', 'meta' => 'Application funnel'],
            ['title' => 'Equipment List', 'route' => route('admin.reports.equipment-list'), 'icon' => 'Truck', 'meta' => (string) ($stats['vehicles']['total'] ?? 0) . ' vehicles tracked'],
            ['title' => 'Carrier Documents', 'route' => route('admin.reports.carrier-documents'), 'icon' => 'FileArchive', 'meta' => (string) ($stats['documents']['total'] ?? 0) . ' documents'],
            ['title' => 'Accidents', 'route' => route('admin.reports.accidents'), 'icon' => 'AlertTriangle', 'meta' => (string) ($stats['accidents']['total'] ?? 0) . ' total accidents'],
            ['title' => 'Maintenances', 'route' => route('admin.reports.maintenances'), 'icon' => 'Wrench', 'meta' => (string) ($stats['maintenances']['total'] ?? 0) . ' maintenance items'],
            ['title' => 'Emergency Repairs', 'route' => route('admin.reports.emergency-repairs'), 'icon' => 'AlertCircle', 'meta' => (string) ($stats['emergency_repairs']['total'] ?? 0) . ' emergency repairs'],
            ['title' => 'Trainings', 'route' => route('admin.reports.trainings'), 'icon' => 'GraduationCap', 'meta' => ($stats['trainings']['completion_rate'] ?? 0) . '% completion rate'],
            ['title' => 'Migrations', 'route' => route('admin.reports.migrations'), 'icon' => 'ArrowRightLeft', 'meta' => 'Driver migration audit trail'],
            ['title' => 'Trips', 'route' => route('admin.reports.trips'), 'icon' => 'MapPin', 'meta' => 'Trip operational insight'],
            ['title' => 'HOS', 'route' => route('admin.reports.hos'), 'icon' => 'Clock', 'meta' => 'Compliance summary'],
            ['title' => 'Violations', 'route' => route('admin.reports.violations'), 'icon' => 'AlertOctagon', 'meta' => 'HOS violation tracking'],
        ];

        return Inertia::render('admin/reports/Index', [
            'filters' => $filters,
            'stats' => $stats,
            'quickLinks' => $quickLinks,
            'generatedAt' => now()->format('n/j/Y g:i A'),
        ]);
    }

    public function activeDrivers(Request $request): InertiaResponse|\Illuminate\Http\Response
    {
        $scope = $this->scopeContext();
        $tab = (string) $request->input('tab', 'all');
        $filters = [
            'tab' => $tab,
            'search' => trim((string) $request->input('search', '')),
            'carrier_id' => $this->carrierFilterValue($request, 'carrier_id'),
            'date_from' => (string) $request->input('date_from', ''),
            'date_to' => (string) $request->input('date_to', ''),
            'per_page' => max((int) $request->input('per_page', 15), 10),
        ];

        $query = UserDriverDetail::query()
            ->with(['user', 'carrier', 'application', 'primaryLicense']);
        $this->applyDriverScope($query, $scope, $filters['carrier_id']);
        $query->whereHas('application', fn (Builder $builder) => $builder->where('status', DriverApplication::STATUS_APPROVED));

        if ($filters['search'] !== '') {
            $search = '%' . $filters['search'] . '%';
            $query->where(function (Builder $builder) use ($search) {
                $builder
                    ->where('last_name', 'like', $search)
                    ->orWhere('phone', 'like', $search)
                    ->orWhereHas('user', fn (Builder $user) => $user->where('name', 'like', $search)->orWhere('email', 'like', $search))
                    ->orWhereHas('carrier', fn (Builder $carrier) => $carrier->where('name', 'like', $search))
                    ->orWhereHas('primaryLicense', fn (Builder $license) => $license->where('license_number', 'like', $search));
            });
        }

        if ($dateFrom = $this->parseUsDate($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $dateFrom->format('Y-m-d'));
        }

        if ($dateTo = $this->parseUsDate($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $dateTo->format('Y-m-d'));
        }

        if ($tab === 'active') {
            $query->where('status', UserDriverDetail::STATUS_ACTIVE);
        } elseif ($tab === 'inactive') {
            $query->where('status', UserDriverDetail::STATUS_INACTIVE);
        } elseif ($tab === 'new') {
            $query->where('created_at', '>=', now()->subDays(30));
        }

        $isPdf = $request->input('export') === 'pdf';

        $statsQuery = UserDriverDetail::query()->with('application');
        $this->applyDriverScope($statsQuery, $scope, $filters['carrier_id']);
        $statsQuery->whereHas('application', fn (Builder $builder) => $builder->where('status', DriverApplication::STATUS_APPROVED));

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'active' => (clone $statsQuery)->where('status', UserDriverDetail::STATUS_ACTIVE)->count(),
            'inactive' => (clone $statsQuery)->where('status', UserDriverDetail::STATUS_INACTIVE)->count(),
            'new' => (clone $statsQuery)->where('created_at', '>=', now()->subDays(30))->count(),
        ];

        if ($isPdf) {
            $driverItems = $query->orderByDesc('created_at')->get()
                ->map(fn (UserDriverDetail $driver) => $this->driverRow($driver));

            return $this->downloadSimplePdf(
                title: 'Active Drivers Report',
                filename: 'active_drivers_report_' . now()->format('Ymd_His') . '.pdf',
                columns: ['Driver', 'Email', 'Carrier', 'Registration', 'License', 'Status'],
                rows: $driverItems->map(fn (array $row) => [
                    $row['driver_name'],
                    $row['email'] ?: 'N/A',
                    $row['carrier_name'] ?: 'N/A',
                    $row['registered_at'] ?: 'N/A',
                    $row['license_number'] ?: 'N/A',
                    $row['status_label'],
                ])->all(),
                filters: $this->filterSummary([
                    'Carrier' => $this->carrierNameById($filters['carrier_id']),
                    'Tab' => str($filters['tab'])->replace('-', ' ')->title()->toString(),
                    'Search' => $filters['search'],
                    'From' => $filters['date_from'],
                    'To' => $filters['date_to'],
                ]),
                stats: [
                    'Total Drivers' => $stats['total'],
                    'Active' => $stats['active'],
                    'Inactive' => $stats['inactive'],
                    'New (30 Days)' => $stats['new'],
                ],
            );
        }

        $drivers = $query->orderByDesc('created_at')->paginate($filters['per_page'])->withQueryString();
        $drivers->through(fn (UserDriverDetail $driver) => $this->driverRow($driver));

        return Inertia::render('admin/reports/ActiveDrivers', [
            'filters' => $filters,
            'drivers' => $drivers,
            'stats' => $stats,
            'tabs' => [
                ['value' => 'all', 'label' => 'All Drivers'],
                ['value' => 'active', 'label' => 'Active'],
                ['value' => 'inactive', 'label' => 'Inactive'],
                ['value' => 'new', 'label' => 'New 30 Days'],
            ],
            'carriers' => $this->carrierOptions($scope),
            'canFilterCarriers' => $scope['is_superadmin'],
        ]);
    }

    public function activeDriversPdf(Request $request): \Illuminate\Http\Response
    {
        $request->merge(['export' => 'pdf']);
        return $this->activeDrivers($request);
    }

    public function inactiveDrivers(Request $request): InertiaResponse|\Illuminate\Http\Response
    {
        $scope = $this->scopeContext();
        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'carrier_id' => $this->carrierFilterValue($request, 'carrier_id'),
            'date_from' => (string) $request->input('date_from', ''),
            'date_to' => (string) $request->input('date_to', ''),
            'per_page' => max((int) $request->input('per_page', 15), 10),
        ];

        $query = UserDriverDetail::query()
            ->with(['user', 'carrier', 'application', 'primaryLicense'])
            ->where('status', UserDriverDetail::STATUS_INACTIVE);
        $this->applyDriverScope($query, $scope, $filters['carrier_id']);

        if ($filters['search'] !== '') {
            $search = '%' . $filters['search'] . '%';
            $query->where(function (Builder $builder) use ($search) {
                $builder
                    ->where('last_name', 'like', $search)
                    ->orWhereHas('user', fn (Builder $user) => $user->where('name', 'like', $search)->orWhere('email', 'like', $search))
                    ->orWhereHas('carrier', fn (Builder $carrier) => $carrier->where('name', 'like', $search));
            });
        }

        if ($dateFrom = $this->parseUsDate($filters['date_from'])) {
            $query->whereDate('updated_at', '>=', $dateFrom->format('Y-m-d'));
        }

        if ($dateTo = $this->parseUsDate($filters['date_to'])) {
            $query->whereDate('updated_at', '<=', $dateTo->format('Y-m-d'));
        }

        $isPdf = $request->routeIs('admin.reports.inactive-drivers-pdf');

        $statsBase = UserDriverDetail::query()->where('status', UserDriverDetail::STATUS_INACTIVE);
        $this->applyDriverScope($statsBase, $scope, $filters['carrier_id']);

        $stats = [
            'inactive' => (clone $statsBase)->count(),
            'with_termination_date' => (clone $statsBase)->whereNotNull('termination_date')->count(),
            'last_30_days' => (clone $statsBase)->where('updated_at', '>=', now()->subDays(30))->count(),
        ];

        if ($isPdf) {
            $driverItems = $query->orderByDesc('updated_at')->get()
                ->map(fn (UserDriverDetail $driver) => $this->driverRow($driver));

            return $this->downloadSimplePdf(
                title: 'Inactive Drivers Report',
                filename: 'inactive_drivers_report_' . now()->format('Ymd_His') . '.pdf',
                columns: ['Driver', 'Email', 'Carrier', 'Updated', 'Termination', 'Status'],
                rows: $driverItems->map(fn (array $row) => [
                    $row['driver_name'],
                    $row['email'] ?: 'N/A',
                    $row['carrier_name'] ?: 'N/A',
                    $row['updated_at'] ?: 'N/A',
                    $row['termination_date'] ?: 'N/A',
                    $row['status_label'],
                ])->all(),
                filters: $this->filterSummary([
                    'Carrier' => $this->carrierNameById($filters['carrier_id']),
                    'Search' => $filters['search'],
                    'From' => $filters['date_from'],
                    'To' => $filters['date_to'],
                ]),
                stats: [
                    'Inactive Drivers' => $stats['inactive'],
                    'With Termination Date' => $stats['with_termination_date'],
                    'Updated Last 30 Days' => $stats['last_30_days'],
                ],
            );
        }

        $drivers = $query->orderByDesc('updated_at')->paginate($filters['per_page'])->withQueryString();
        $drivers->through(fn (UserDriverDetail $driver) => $this->driverRow($driver));

        return Inertia::render('admin/reports/InactiveDrivers', [
            'filters' => $filters,
            'drivers' => $drivers,
            'stats' => $stats,
            'carriers' => $this->carrierOptions($scope),
            'canFilterCarriers' => $scope['is_superadmin'],
        ]);
    }

    public function inactiveDriversPdf(Request $request): \Illuminate\Http\Response
    {
        return $this->inactiveDrivers($request);
    }

    public function equipmentList(Request $request): InertiaResponse|\Illuminate\Http\Response
    {
        $scope = $this->scopeContext();
        $filters = [
            'tab' => (string) $request->input('tab', 'all'),
            'search' => trim((string) $request->input('search', '')),
            'carrier_id' => $this->carrierFilterValue($request, 'carrier_id'),
            'per_page' => max((int) $request->input('per_page', 15), 10),
        ];

        $query = Vehicle::query()->with(['carrier', 'driver.user']);
        $this->applyVehicleScope($query, $scope, $filters['carrier_id']);

        if ($filters['search'] !== '') {
            $search = '%' . $filters['search'] . '%';
            $query->where(function (Builder $builder) use ($search) {
                $builder
                    ->where('company_unit_number', 'like', $search)
                    ->orWhere('make', 'like', $search)
                    ->orWhere('model', 'like', $search)
                    ->orWhere('vin', 'like', $search)
                    ->orWhere('registration_number', 'like', $search)
                    ->orWhereHas('carrier', fn (Builder $carrier) => $carrier->where('name', 'like', $search))
                    ->orWhereHas('driver.user', fn (Builder $user) => $user->where('name', 'like', $search));
            });
        }

        if ($filters['tab'] === 'active') {
            $query->active();
        } elseif ($filters['tab'] === 'out_of_service') {
            $query->outOfService();
        } elseif ($filters['tab'] === 'suspended') {
            $query->suspended();
        }

        $vehicles = $query->orderBy('company_unit_number')->paginate($filters['per_page'])->withQueryString();
        $vehicles->through(fn (Vehicle $vehicle) => [
            'id' => $vehicle->id,
            'unit_number' => $vehicle->company_unit_number ?: (trim(($vehicle->year ?: '') . ' ' . ($vehicle->make ?: '') . ' ' . ($vehicle->model ?: '')) ?: 'Vehicle'),
            'vehicle_label' => trim(($vehicle->year ?: '') . ' ' . ($vehicle->make ?: '') . ' ' . ($vehicle->model ?: '')) ?: 'Vehicle',
            'carrier_name' => $vehicle->carrier?->name,
            'driver_name' => $vehicle->driver?->full_name ?: null,
            'type' => $vehicle->type ?: 'N/A',
            'vin' => $vehicle->vin ?: 'N/A',
            'driver_type' => $vehicle->driver_type ? str($vehicle->driver_type)->replace('_', ' ')->title()->toString() : 'N/A',
            'status' => $vehicle->status,
            'status_label' => str($vehicle->status)->replace('_', ' ')->title()->toString(),
            'registration_expiration' => $vehicle->registration_expiration_date?->format('n/j/Y'),
            'inspection_expiration' => $vehicle->annual_inspection_expiration_date?->format('n/j/Y'),
        ]);

        $statsQuery = Vehicle::query();
        $this->applyVehicleScope($statsQuery, $scope, $filters['carrier_id']);
        $stats = [
            'total' => (clone $statsQuery)->count(),
            'active' => (clone $statsQuery)->active()->count(),
            'out_of_service' => (clone $statsQuery)->outOfService()->count(),
            'suspended' => (clone $statsQuery)->suspended()->count(),
        ];

        if ($request->routeIs('admin.reports.equipment-list-pdf')) {
            return $this->downloadSimplePdf(
                title: 'Equipment List Report',
                filename: 'equipment_list_report_' . now()->format('Ymd_His') . '.pdf',
                columns: ['Unit', 'Vehicle', 'Carrier', 'Driver', 'Type', 'VIN', 'Status'],
                rows: collect($vehicles->items())->map(fn (array $row) => [
                    $row['unit_number'],
                    $row['vehicle_label'],
                    $row['carrier_name'] ?: 'N/A',
                    $row['driver_name'] ?: 'N/A',
                    $row['type'],
                    $row['vin'],
                    $row['status_label'],
                ])->all(),
                filters: $this->filterSummary([
                    'Carrier' => $this->carrierNameById($filters['carrier_id']),
                    'Tab' => str($filters['tab'])->replace('_', ' ')->title()->toString(),
                    'Search' => $filters['search'],
                ]),
                stats: [
                    'Total Vehicles' => $stats['total'],
                    'Active' => $stats['active'],
                    'Out of Service' => $stats['out_of_service'],
                    'Suspended' => $stats['suspended'],
                ],
            );
        }

        return Inertia::render('admin/reports/EquipmentList', [
            'filters' => $filters,
            'vehicles' => $vehicles,
            'stats' => $stats,
            'tabs' => [
                ['value' => 'all', 'label' => 'All Units'],
                ['value' => 'active', 'label' => 'Active'],
                ['value' => 'out_of_service', 'label' => 'Out of Service'],
                ['value' => 'suspended', 'label' => 'Suspended'],
            ],
            'carriers' => $this->carrierOptions($scope),
            'canFilterCarriers' => $scope['is_superadmin'],
        ]);
    }

    public function equipmentListPdf(Request $request): \Illuminate\Http\Response
    {
        return $this->equipmentList($request);
    }

    public function accidents(Request $request): InertiaResponse|\Illuminate\Http\Response
    {
        $scope = $this->scopeContext();
        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'carrier_id' => $this->carrierFilterValue($request, 'carrier_id'),
            'driver_id' => (string) $request->input('driver_id', ''),
            'date_from' => (string) $request->input('date_from', ''),
            'date_to' => (string) $request->input('date_to', ''),
            'per_page' => max((int) $request->input('per_page', 15), 10),
        ];

        $query = DriverAccident::query()->with(['userDriverDetail.user', 'userDriverDetail.carrier']);
        $this->applyAccidentScope($query, $scope, $filters['carrier_id']);

        if ($filters['search'] !== '') {
            $search = '%' . $filters['search'] . '%';
            $query->where(function (Builder $builder) use ($search) {
                $builder
                    ->where('nature_of_accident', 'like', $search)
                    ->orWhere('comments', 'like', $search)
                    ->orWhereHas('userDriverDetail.user', fn (Builder $user) => $user->where('name', 'like', $search))
                    ->orWhereHas('userDriverDetail.carrier', fn (Builder $carrier) => $carrier->where('name', 'like', $search));
            });
        }

        if ($filters['driver_id'] !== '') {
            $query->where('user_driver_detail_id', (int) $filters['driver_id']);
        }

        if ($dateFrom = $this->parseUsDate($filters['date_from'])) {
            $query->whereDate('accident_date', '>=', $dateFrom->format('Y-m-d'));
        }

        if ($dateTo = $this->parseUsDate($filters['date_to'])) {
            $query->whereDate('accident_date', '<=', $dateTo->format('Y-m-d'));
        }

        $accidents = $query->orderByDesc('accident_date')->paginate($filters['per_page'])->withQueryString();
        $accidents->through(fn (DriverAccident $accident) => $this->accidentRow($accident));

        $statsBase = DriverAccident::query();
        $this->applyAccidentScope($statsBase, $scope, $filters['carrier_id']);
        $stats = [
            'total' => (clone $statsBase)->count(),
            'with_injuries' => (clone $statsBase)->where('had_injuries', true)->count(),
            'with_fatalities' => (clone $statsBase)->where('had_fatalities', true)->count(),
            'last_30_days' => (clone $statsBase)->whereDate('accident_date', '>=', now()->subDays(30)->format('Y-m-d'))->count(),
        ];

        if ($request->input('export') === 'pdf') {
            return $this->downloadSimplePdf(
                title: 'Accidents Report',
                filename: 'accidents_report_' . now()->format('Ymd_His') . '.pdf',
                columns: ['Driver', 'Carrier', 'Date', 'Nature', 'Injuries', 'Fatalities'],
                rows: collect($accidents->items())->map(fn (array $row) => [
                    $row['driver_name'] ?: 'N/A',
                    $row['carrier_name'] ?: 'N/A',
                    $row['accident_date'] ?: 'N/A',
                    $row['nature'],
                    $row['injuries_label'],
                    $row['fatalities_label'],
                ])->all(),
                filters: $this->filterSummary([
                    'Carrier' => $this->carrierNameById($filters['carrier_id']),
                    'Driver' => $this->driverNameById($filters['driver_id']),
                    'Search' => $filters['search'],
                    'From' => $filters['date_from'],
                    'To' => $filters['date_to'],
                ]),
                stats: [
                    'Total Accidents' => $stats['total'],
                    'With Injuries' => $stats['with_injuries'],
                    'With Fatalities' => $stats['with_fatalities'],
                    'Last 30 Days' => $stats['last_30_days'],
                ],
            );
        }

        return Inertia::render('admin/reports/Accidents', [
            'filters' => $filters,
            'accidents' => $accidents,
            'stats' => $stats,
            'carriers' => $this->carrierOptions($scope),
            'drivers' => $this->driverOptions($scope, $filters['carrier_id']),
            'canFilterCarriers' => $scope['is_superadmin'],
            'registerRoute' => route('admin.reports.register-accident'),
            'listRoute' => route('admin.reports.accidents-list'),
        ]);
    }

    public function registerAccident(Request $request): InertiaResponse
    {
        $scope = $this->scopeContext();
        $carrierId = $this->carrierFilterValue($request, 'carrier_id');

        return Inertia::render('admin/reports/RegisterAccident', [
            'carriers' => $this->carrierOptions($scope),
            'drivers' => $this->driverOptions($scope, $carrierId),
            'filters' => [
                'carrier_id' => $carrierId,
            ],
            'canFilterCarriers' => $scope['is_superadmin'],
        ]);
    }

    public function storeAccident(Request $request): RedirectResponse
    {
        $scope = $this->scopeContext();

        $validated = $request->validate([
            'carrier_id' => ['required', 'exists:carriers,id'],
            'user_driver_detail_id' => ['required', 'exists:user_driver_details,id'],
            'accident_date' => ['required', 'string'],
            'nature_of_accident' => ['required', 'string', 'max:255'],
            'had_injuries' => ['nullable', 'boolean'],
            'number_of_injuries' => ['nullable', 'integer', 'min:0'],
            'had_fatalities' => ['nullable', 'boolean'],
            'number_of_fatalities' => ['nullable', 'integer', 'min:0'],
            'comments' => ['nullable', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:20480'],
        ]);

        $driver = UserDriverDetail::query()->findOrFail((int) $validated['user_driver_detail_id']);
        $this->ensureAllowedCarrier((int) $validated['carrier_id'], $scope);

        if ((int) $driver->carrier_id !== (int) $validated['carrier_id']) {
            return back()->withErrors(['user_driver_detail_id' => 'The selected driver does not belong to the selected carrier.'])->withInput();
        }

        $accidentDate = $this->parseUsDate($validated['accident_date']);
        if (! $accidentDate) {
            return back()->withErrors(['accident_date' => 'Please use a valid M/D/YYYY accident date.'])->withInput();
        }

        DB::transaction(function () use ($request, $validated, $driver, $accidentDate) {
            $accident = DriverAccident::create([
                'user_driver_detail_id' => $driver->id,
                'accident_date' => $accidentDate->format('Y-m-d'),
                'nature_of_accident' => $validated['nature_of_accident'],
                'had_injuries' => $request->boolean('had_injuries'),
                'number_of_injuries' => $request->boolean('had_injuries') ? (int) ($validated['number_of_injuries'] ?? 0) : 0,
                'had_fatalities' => $request->boolean('had_fatalities'),
                'number_of_fatalities' => $request->boolean('had_fatalities') ? (int) ($validated['number_of_fatalities'] ?? 0) : 0,
                'comments' => $validated['comments'] ?? null,
            ]);

            foreach ($request->file('attachments', []) as $file) {
                $accident->addMedia($file)->toMediaCollection('accident-images');
            }
        });

        return redirect()->route('admin.reports.accidents')->with('success', 'Accident registered successfully.');
    }

    public function accidentsList(Request $request): InertiaResponse
    {
        $request->merge([
            'per_page' => $request->input('per_page', 25),
        ]);

        $response = $this->accidents($request);
        if ($response instanceof InertiaResponse) {
            return Inertia::render('admin/reports/AccidentsList', (fn() => $this->props)->call($response));
        }

        abort(500);
    }

    public function getActiveDriversByCarrier(int $carrierId): JsonResponse
    {
        $scope = $this->scopeContext();
        $this->ensureAllowedCarrier($carrierId, $scope);

        $drivers = UserDriverDetail::query()
            ->with('user')
            ->where('carrier_id', $carrierId)
            ->where('status', UserDriverDetail::STATUS_ACTIVE)
            ->orderBy('last_name')
            ->get()
            ->map(fn (UserDriverDetail $driver) => [
                'id' => $driver->id,
                'name' => $driver->full_name ?: 'Unknown Driver',
            ])
            ->values();

        return response()->json($drivers);
    }

    public function carrierDocuments(Request $request): InertiaResponse|\Illuminate\Http\Response
    {
        $scope = $this->scopeContext();
        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'carrier_id' => $this->carrierFilterValue($request, 'carrier_id'),
            'status' => (string) $request->input('status', ''),
            'per_page' => max((int) $request->input('per_page', 15), 10),
        ];

        $query = Carrier::query()->with(['documents.documentType']);
        $this->applyCarrierScope($query, $scope, $filters['carrier_id']);
        $query->where('status', Carrier::STATUS_ACTIVE);

        if ($filters['search'] !== '') {
            $search = '%' . $filters['search'] . '%';
            $query->where('name', 'like', $search);
        }

        if ($filters['status'] !== '') {
            $query->whereHas('documents', fn (Builder $builder) => $builder->where('status', (int) $filters['status']));
        }

        $carriers = $query->orderBy('name')->paginate($filters['per_page'])->withQueryString();
        $carriers->through(fn (Carrier $carrier) => $this->carrierDocumentRow($carrier));

        $documentStats = CarrierDocument::query();
        $this->applyCarrierDocumentScope($documentStats, $scope, $filters['carrier_id']);

        $stats = [
            'total_documents' => (clone $documentStats)->count(),
            'approved' => (clone $documentStats)->where('status', CarrierDocument::STATUS_APPROVED)->count(),
            'pending' => (clone $documentStats)->where('status', CarrierDocument::STATUS_PENDING)->count(),
            'in_process' => (clone $documentStats)->where('status', CarrierDocument::STATUS_IN_PROCESS)->count(),
        ];

        if ($request->routeIs('admin.reports.carrier-documents-pdf')) {
            return $this->downloadSimplePdf(
                title: 'Carrier Documents Report',
                filename: 'carrier_documents_report_' . now()->format('Ymd_His') . '.pdf',
                columns: ['Carrier', 'Documents', 'Approved', 'Pending', 'In Process', 'Completion'],
                rows: collect($carriers->items())->map(fn (array $row) => [
                    $row['carrier_name'],
                    $row['total_documents'],
                    $row['approved_documents'],
                    $row['pending_documents'],
                    $row['in_process_documents'],
                    $row['completion_rate'] . '%',
                ])->all(),
                filters: $this->filterSummary([
                    'Carrier' => $this->carrierNameById($filters['carrier_id']),
                    'Search' => $filters['search'],
                    'Status' => $this->carrierDocumentStatusLabel($filters['status']),
                ]),
                stats: [
                    'Total Documents' => $stats['total_documents'],
                    'Approved' => $stats['approved'],
                    'Pending' => $stats['pending'],
                    'In Process' => $stats['in_process'],
                ],
            );
        }

        return Inertia::render('admin/reports/CarrierDocuments', [
            'filters' => $filters,
            'carriers' => $carriers,
            'stats' => $stats,
            'statusOptions' => [
                ['value' => '', 'label' => 'All statuses'],
                ['value' => (string) CarrierDocument::STATUS_PENDING, 'label' => 'Pending'],
                ['value' => (string) CarrierDocument::STATUS_APPROVED, 'label' => 'Approved'],
                ['value' => (string) CarrierDocument::STATUS_REJECTED, 'label' => 'Rejected'],
                ['value' => (string) CarrierDocument::STATUS_IN_PROCESS, 'label' => 'In Process'],
            ],
            'carriersOptions' => $this->carrierOptions($scope),
            'canFilterCarriers' => $scope['is_superadmin'],
        ]);
    }

    public function carrierDocumentsPdf(Request $request): \Illuminate\Http\Response
    {
        return $this->carrierDocuments($request);
    }

    public function driverProspects(Request $request): InertiaResponse|\Illuminate\Http\Response
    {
        $scope = $this->scopeContext();
        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'carrier_id' => $this->carrierFilterValue($request, 'carrier_id'),
            'status' => (string) $request->input('status', ''),
            'year' => (string) $request->input('year', ''),
            'date_from' => (string) $request->input('date_from', ''),
            'date_to' => (string) $request->input('date_to', ''),
            'per_page' => max((int) $request->input('per_page', 15), 10),
        ];

        $query = DriverApplication::query()
            ->with(['user', 'userDriverDetail.carrier', 'verifications'])
            ->where('status', '!=', DriverApplication::STATUS_APPROVED);

        if ($filters['carrier_id'] !== '') {
            $query->whereHas('userDriverDetail', fn (Builder $builder) => $builder->where('carrier_id', (int) $filters['carrier_id']));
        } elseif (! $scope['is_superadmin']) {
            $query->whereHas('userDriverDetail', fn (Builder $builder) => $builder->where('carrier_id', $scope['carrier_id']));
        }

        if ($filters['search'] !== '') {
            $search = '%' . $filters['search'] . '%';
            $query->where(function (Builder $builder) use ($search) {
                $builder
                    ->where('status', 'like', $search)
                    ->orWhereHas('user', fn (Builder $user) => $user->where('name', 'like', $search)->orWhere('email', 'like', $search))
                    ->orWhereHas('userDriverDetail.carrier', fn (Builder $carrier) => $carrier->where('name', 'like', $search));
            });
        }

        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if ($filters['year'] !== '') {
            $query->whereYear('created_at', (int) $filters['year']);
        }

        if ($dateFrom = $this->parseUsDate($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $dateFrom->format('Y-m-d'));
        }

        if ($dateTo = $this->parseUsDate($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $dateTo->format('Y-m-d'));
        }

        $isPdf = $request->routeIs('admin.reports.driver-prospects-pdf');

        $prospectMapper = function (DriverApplication $application): array {
            $driver = $application->userDriverDetail;
            return [
                'id' => $application->id,
                'driver_name' => $application->user?->name ?: 'Unknown',
                'email' => $application->user?->email,
                'carrier_name' => $driver?->carrier?->name,
                'status' => $application->status,
                'status_label' => str($application->status)->replace('_', ' ')->title()->toString(),
                'created_at' => $application->created_at?->format('n/j/Y'),
                'verification_count' => $application->verifications->count(),
                'driver_id' => $driver?->id,
            ];
        };

        $statsBase = DriverApplication::query()->where('status', '!=', DriverApplication::STATUS_APPROVED);
        if ($filters['carrier_id'] !== '') {
            $statsBase->whereHas('userDriverDetail', fn (Builder $builder) => $builder->where('carrier_id', (int) $filters['carrier_id']));
        } elseif (! $scope['is_superadmin']) {
            $statsBase->whereHas('userDriverDetail', fn (Builder $builder) => $builder->where('carrier_id', $scope['carrier_id']));
        }

        $stats = [
            'total' => (clone $statsBase)->count(),
            'draft' => (clone $statsBase)->where('status', DriverApplication::STATUS_DRAFT)->count(),
            'pending' => (clone $statsBase)->where('status', DriverApplication::STATUS_PENDING)->count(),
            'rejected' => (clone $statsBase)->where('status', DriverApplication::STATUS_REJECTED)->count(),
        ];

        $years = DriverApplication::query()
            ->selectRaw('YEAR(created_at) as year')
            ->whereNotNull('created_at')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->map(fn ($year) => (string) $year)
            ->values();

        if ($isPdf) {
            $prospectItems = $query->orderByDesc('created_at')->get()->map($prospectMapper);

            return $this->downloadSimplePdf(
                title: 'Driver Prospects Report',
                filename: 'driver_prospects_report_' . now()->format('Ymd_His') . '.pdf',
                columns: ['Driver', 'Email', 'Carrier', 'Status', 'Created', 'Verifications'],
                rows: $prospectItems->map(fn (array $row) => [
                    $row['driver_name'],
                    $row['email'] ?: 'N/A',
                    $row['carrier_name'] ?: 'N/A',
                    $row['status_label'],
                    $row['created_at'] ?: 'N/A',
                    $row['verification_count'],
                ])->all(),
                filters: $this->filterSummary([
                    'Carrier' => $this->carrierNameById($filters['carrier_id']),
                    'Search' => $filters['search'],
                    'Status' => $filters['status'],
                    'Year' => $filters['year'],
                    'From' => $filters['date_from'],
                    'To' => $filters['date_to'],
                ]),
                stats: [
                    'Total Prospects' => $stats['total'],
                    'Draft' => $stats['draft'],
                    'Pending' => $stats['pending'],
                    'Rejected' => $stats['rejected'],
                ],
            );
        }

        $prospects = $query->orderByDesc('created_at')->paginate($filters['per_page'])->withQueryString();
        $prospects->through($prospectMapper);

        return Inertia::render('admin/reports/DriverProspects', [
            'filters' => $filters,
            'prospects' => $prospects,
            'stats' => $stats,
            'carriers' => $this->carrierOptions($scope),
            'years' => $years,
            'canFilterCarriers' => $scope['is_superadmin'],
            'statusOptions' => [
                ['value' => '', 'label' => 'All statuses'],
                ['value' => DriverApplication::STATUS_DRAFT, 'label' => 'Draft'],
                ['value' => DriverApplication::STATUS_PENDING, 'label' => 'Pending'],
                ['value' => DriverApplication::STATUS_REJECTED, 'label' => 'Rejected'],
            ],
        ]);
    }

    public function driverProspectsPdf(Request $request): \Illuminate\Http\Response
    {
        return $this->driverProspects($request);
    }

    public function downloadCarrierDocuments(Carrier $carrier): RedirectResponse|BinaryFileResponse
    {
        $scope = $this->scopeContext();
        $this->ensureAllowedCarrier((int) $carrier->id, $scope);

        $carrier->load('documents.documentType');

        $mediaFiles = $carrier->documents
            ->map(fn (CarrierDocument $document) => $document->getFirstMedia('carrier_documents'))
            ->filter(fn ($media) => $media instanceof Media && file_exists($media->getPath()))
            ->values();

        if ($mediaFiles->isEmpty()) {
            return back()->with('warning', 'No downloadable files were found for this carrier.');
        }

        if ($mediaFiles->count() === 1) {
            $media = $mediaFiles->first();
            return Response::download($media->getPath(), $media->file_name);
        }

        $zipName = 'carrier_documents_' . str($carrier->name)->slug('_') . '_' . now()->format('Ymd_His') . '.zip';
        $zipPath = storage_path('app/temp/' . $zipName);

        if (! is_dir(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Unable to prepare ZIP download.');
        }

        foreach ($mediaFiles as $media) {
            $document = $carrier->documents->firstWhere('id', $media->model_id);
            $label = trim(($document?->documentType?->name ?: 'Document') . ' - ' . $media->file_name);
            $zip->addFile($media->getPath(), $label);
        }

        $zip->close();

        return Response::download($zipPath, $zipName)->deleteFileAfterSend(true);
    }

    public function maintenances(Request $request): InertiaResponse|\Illuminate\Http\Response
    {
        $scope = $this->scopeContext();
        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'carrier_id' => $this->carrierFilterValue($request, 'carrier_id'),
            'status' => (string) $request->input('status', ''),
            'date_from' => (string) $request->input('date_from', ''),
            'date_to' => (string) $request->input('date_to', ''),
            'per_page' => max((int) $request->input('per_page', 15), 10),
        ];

        $query = VehicleMaintenance::query()->with(['vehicle.carrier']);
        $this->applyVehicleRelationScope($query, $scope, $filters['carrier_id']);

        if ($filters['search'] !== '') {
            $search = '%' . $filters['search'] . '%';
            $query->where(function (Builder $builder) use ($search) {
                $builder
                    ->where('service_tasks', 'like', $search)
                    ->orWhere('vendor_mechanic', 'like', $search)
                    ->orWhere('description', 'like', $search)
                    ->orWhereHas('vehicle', fn (Builder $vehicle) => $vehicle->where('company_unit_number', 'like', $search)->orWhere('make', 'like', $search)->orWhere('model', 'like', $search));
            });
        }

        if ($filters['status'] === 'completed') {
            $query->completed();
        } elseif ($filters['status'] === 'pending') {
            $query->pending();
        } elseif ($filters['status'] === 'overdue') {
            $query->overdue();
        }

        if ($dateFrom = $this->parseUsDate($filters['date_from'])) {
            $query->whereDate('service_date', '>=', $dateFrom->format('Y-m-d'));
        }

        if ($dateTo = $this->parseUsDate($filters['date_to'])) {
            $query->whereDate('service_date', '<=', $dateTo->format('Y-m-d'));
        }

        $maintenances = $query->orderByDesc('service_date')->paginate($filters['per_page'])->withQueryString();
        $maintenances->through(fn (VehicleMaintenance $maintenance) => [
            'id' => $maintenance->id,
            'vehicle_label' => $this->vehicleDisplayLabel($maintenance->vehicle),
            'carrier_name' => $maintenance->vehicle?->carrier?->name,
            'service_date' => $maintenance->service_date?->format('n/j/Y'),
            'next_service_date' => $maintenance->next_service_date?->format('n/j/Y'),
            'service_tasks' => $maintenance->service_tasks ?: 'Maintenance',
            'vendor_mechanic' => $maintenance->vendor_mechanic ?: 'N/A',
            'cost' => (float) ($maintenance->cost ?: 0),
            'status' => $maintenance->status ? 'completed' : ($maintenance->isOverdue() ? 'overdue' : 'pending'),
        ]);

        $statsBase = VehicleMaintenance::query();
        $this->applyVehicleRelationScope($statsBase, $scope, $filters['carrier_id']);

        $stats = [
            'total' => (clone $statsBase)->count(),
            'completed' => (clone $statsBase)->completed()->count(),
            'pending' => (clone $statsBase)->pending()->count(),
            'overdue' => (clone $statsBase)->overdue()->count(),
            'total_cost' => (float) ((clone $statsBase)->sum('cost') ?: 0),
        ];

        if ($request->routeIs('admin.reports.maintenances-pdf')) {
            return $this->downloadSimplePdf(
                title: 'Maintenances Report',
                filename: 'maintenances_report_' . now()->format('Ymd_His') . '.pdf',
                columns: ['Vehicle', 'Carrier', 'Service Date', 'Task', 'Vendor', 'Status', 'Cost'],
                rows: collect($maintenances->items())->map(fn (array $row) => [
                    $row['vehicle_label'],
                    $row['carrier_name'] ?: 'N/A',
                    $row['service_date'] ?: 'N/A',
                    $row['service_tasks'],
                    $row['vendor_mechanic'],
                    str($row['status'])->replace('_', ' ')->title()->toString(),
                    '$' . number_format($row['cost'], 2),
                ])->all(),
                filters: $this->filterSummary([
                    'Carrier' => $this->carrierNameById($filters['carrier_id']),
                    'Search' => $filters['search'],
                    'Status' => $filters['status'],
                    'From' => $filters['date_from'],
                    'To' => $filters['date_to'],
                ]),
                stats: [
                    'Total Items' => $stats['total'],
                    'Completed' => $stats['completed'],
                    'Pending' => $stats['pending'],
                    'Overdue' => $stats['overdue'],
                    'Total Cost' => '$' . number_format($stats['total_cost'], 2),
                ],
            );
        }

        return Inertia::render('admin/reports/Maintenances', [
            'filters' => $filters,
            'maintenances' => $maintenances,
            'stats' => $stats,
            'carriers' => $this->carrierOptions($scope),
            'canFilterCarriers' => $scope['is_superadmin'],
            'statusOptions' => [
                ['value' => '', 'label' => 'All statuses'],
                ['value' => 'completed', 'label' => 'Completed'],
                ['value' => 'pending', 'label' => 'Pending'],
                ['value' => 'overdue', 'label' => 'Overdue'],
            ],
        ]);
    }

    public function maintenancesPdf(Request $request): \Illuminate\Http\Response
    {
        return $this->maintenances($request);
    }

    public function emergencyRepairs(Request $request): InertiaResponse|\Illuminate\Http\Response
    {
        $scope = $this->scopeContext();
        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'carrier_id' => $this->carrierFilterValue($request, 'carrier_id'),
            'status' => (string) $request->input('status', ''),
            'date_from' => (string) $request->input('date_from', ''),
            'date_to' => (string) $request->input('date_to', ''),
            'per_page' => max((int) $request->input('per_page', 15), 10),
        ];

        $query = EmergencyRepair::query()->with(['vehicle.carrier']);
        $this->applyVehicleRelationScope($query, $scope, $filters['carrier_id']);

        if ($filters['search'] !== '') {
            $search = '%' . $filters['search'] . '%';
            $query->where(function (Builder $builder) use ($search) {
                $builder
                    ->where('repair_name', 'like', $search)
                    ->orWhere('description', 'like', $search)
                    ->orWhereHas('vehicle', fn (Builder $vehicle) => $vehicle->where('company_unit_number', 'like', $search)->orWhere('make', 'like', $search)->orWhere('model', 'like', $search));
            });
        }

        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if ($dateFrom = $this->parseUsDate($filters['date_from'])) {
            $query->whereDate('repair_date', '>=', $dateFrom->format('Y-m-d'));
        }

        if ($dateTo = $this->parseUsDate($filters['date_to'])) {
            $query->whereDate('repair_date', '<=', $dateTo->format('Y-m-d'));
        }

        $repairs = $query->orderByDesc('repair_date')->paginate($filters['per_page'])->withQueryString();
        $repairs->through(fn (EmergencyRepair $repair) => [
            'id' => $repair->id,
            'vehicle_label' => $this->vehicleDisplayLabel($repair->vehicle),
            'carrier_name' => $repair->vehicle?->carrier?->name,
            'repair_name' => $repair->repair_name ?: 'Emergency Repair',
            'repair_date' => $repair->repair_date?->format('n/j/Y'),
            'status' => $repair->status ?: 'pending',
            'cost' => (float) ($repair->cost ?: 0),
            'odometer' => $repair->odometer ?: 'N/A',
        ]);

        $statsBase = EmergencyRepair::query();
        $this->applyVehicleRelationScope($statsBase, $scope, $filters['carrier_id']);

        $stats = [
            'total' => (clone $statsBase)->count(),
            'pending' => (clone $statsBase)->pending()->count(),
            'in_progress' => (clone $statsBase)->inProgress()->count(),
            'completed' => (clone $statsBase)->completed()->count(),
            'total_cost' => (float) ((clone $statsBase)->sum('cost') ?: 0),
        ];

        if ($request->routeIs('admin.reports.emergency-repairs-pdf')) {
            return $this->downloadSimplePdf(
                title: 'Emergency Repairs Report',
                filename: 'emergency_repairs_report_' . now()->format('Ymd_His') . '.pdf',
                columns: ['Vehicle', 'Carrier', 'Repair', 'Date', 'Status', 'Cost'],
                rows: collect($repairs->items())->map(fn (array $row) => [
                    $row['vehicle_label'],
                    $row['carrier_name'] ?: 'N/A',
                    $row['repair_name'],
                    $row['repair_date'] ?: 'N/A',
                    str($row['status'])->replace('_', ' ')->title()->toString(),
                    '$' . number_format($row['cost'], 2),
                ])->all(),
                filters: $this->filterSummary([
                    'Carrier' => $this->carrierNameById($filters['carrier_id']),
                    'Search' => $filters['search'],
                    'Status' => $filters['status'],
                    'From' => $filters['date_from'],
                    'To' => $filters['date_to'],
                ]),
                stats: [
                    'Total Repairs' => $stats['total'],
                    'Pending' => $stats['pending'],
                    'In Progress' => $stats['in_progress'],
                    'Completed' => $stats['completed'],
                    'Total Cost' => '$' . number_format($stats['total_cost'], 2),
                ],
            );
        }

        return Inertia::render('admin/reports/EmergencyRepairs', [
            'filters' => $filters,
            'repairs' => $repairs,
            'stats' => $stats,
            'carriers' => $this->carrierOptions($scope),
            'canFilterCarriers' => $scope['is_superadmin'],
            'statusOptions' => [
                ['value' => '', 'label' => 'All statuses'],
                ['value' => 'pending', 'label' => 'Pending'],
                ['value' => 'in_progress', 'label' => 'In Progress'],
                ['value' => 'completed', 'label' => 'Completed'],
            ],
        ]);
    }

    public function emergencyRepairsPdf(Request $request): \Illuminate\Http\Response
    {
        return $this->emergencyRepairs($request);
    }

    public function trainings(Request $request): InertiaResponse|\Illuminate\Http\Response
    {
        $scope = $this->scopeContext();
        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'carrier_id' => $this->carrierFilterValue($request, 'carrier_id'),
            'status' => (string) $request->input('status', ''),
            'date_from' => (string) $request->input('date_from', ''),
            'date_to' => (string) $request->input('date_to', ''),
            'per_page' => max((int) $request->input('per_page', 15), 10),
        ];

        $query = DriverTraining::query()->with(['driver.user', 'driver.carrier', 'training']);
        if ($filters['carrier_id'] !== '') {
            $query->whereHas('driver', fn (Builder $builder) => $builder->where('carrier_id', (int) $filters['carrier_id']));
        } elseif (! $scope['is_superadmin']) {
            $query->whereHas('driver', fn (Builder $builder) => $builder->where('carrier_id', $scope['carrier_id']));
        }

        if ($filters['search'] !== '') {
            $search = '%' . $filters['search'] . '%';
            $query->where(function (Builder $builder) use ($search) {
                $builder
                    ->whereHas('driver.user', fn (Builder $user) => $user->where('name', 'like', $search)->orWhere('email', 'like', $search))
                    ->orWhereHas('training', fn (Builder $training) => $training->where('title', 'like', $search));
            });
        }

        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if ($dateFrom = $this->parseUsDate($filters['date_from'])) {
            $query->whereDate('assigned_date', '>=', $dateFrom->format('Y-m-d'));
        }

        if ($dateTo = $this->parseUsDate($filters['date_to'])) {
            $query->whereDate('assigned_date', '<=', $dateTo->format('Y-m-d'));
        }

        $assignments = $query->orderByDesc('assigned_date')->paginate($filters['per_page'])->withQueryString();
        $assignments->through(fn (DriverTraining $assignment) => [
            'id' => $assignment->id,
            'driver_name' => $assignment->driver?->full_name ?: 'Unknown Driver',
            'carrier_name' => $assignment->driver?->carrier?->name,
            'training_title' => $assignment->training?->title ?: 'Training',
            'assigned_date' => $assignment->assigned_date?->format('n/j/Y'),
            'due_date' => $assignment->due_date?->format('n/j/Y'),
            'completed_date' => $assignment->completed_date?->format('n/j/Y'),
            'status' => $assignment->status ?: 'assigned',
        ]);

        $statsBase = DriverTraining::query();
        if ($filters['carrier_id'] !== '') {
            $statsBase->whereHas('driver', fn (Builder $builder) => $builder->where('carrier_id', (int) $filters['carrier_id']));
        } elseif (! $scope['is_superadmin']) {
            $statsBase->whereHas('driver', fn (Builder $builder) => $builder->where('carrier_id', $scope['carrier_id']));
        }

        $total = (clone $statsBase)->count();
        $completed = (clone $statsBase)->where('status', 'completed')->count();

        $stats = [
            'total' => $total,
            'completed' => $completed,
            'assigned' => (clone $statsBase)->where('status', 'assigned')->count(),
            'in_progress' => (clone $statsBase)->where('status', 'in_progress')->count(),
            'overdue' => (clone $statsBase)->where('status', '!=', 'completed')->whereNotNull('due_date')->where('due_date', '<', now())->count(),
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
        ];

        if ($request->routeIs('admin.reports.trainings-pdf')) {
            return $this->downloadSimplePdf(
                title: 'Trainings Report',
                filename: 'trainings_report_' . now()->format('Ymd_His') . '.pdf',
                columns: ['Driver', 'Carrier', 'Training', 'Assigned', 'Due', 'Status'],
                rows: collect($assignments->items())->map(fn (array $row) => [
                    $row['driver_name'],
                    $row['carrier_name'] ?: 'N/A',
                    $row['training_title'],
                    $row['assigned_date'] ?: 'N/A',
                    $row['due_date'] ?: 'N/A',
                    str($row['status'])->replace('_', ' ')->title()->toString(),
                ])->all(),
                filters: $this->filterSummary([
                    'Carrier' => $this->carrierNameById($filters['carrier_id']),
                    'Search' => $filters['search'],
                    'Status' => $filters['status'],
                    'From' => $filters['date_from'],
                    'To' => $filters['date_to'],
                ]),
                stats: [
                    'Total Assignments' => $stats['total'],
                    'Completed' => $stats['completed'],
                    'Assigned' => $stats['assigned'],
                    'In Progress' => $stats['in_progress'],
                    'Overdue' => $stats['overdue'],
                    'Completion Rate' => $stats['completion_rate'] . '%',
                ],
            );
        }

        return Inertia::render('admin/reports/Trainings', [
            'filters' => $filters,
            'assignments' => $assignments,
            'stats' => $stats,
            'carriers' => $this->carrierOptions($scope),
            'canFilterCarriers' => $scope['is_superadmin'],
            'statusOptions' => [
                ['value' => '', 'label' => 'All statuses'],
                ['value' => 'assigned', 'label' => 'Assigned'],
                ['value' => 'in_progress', 'label' => 'In Progress'],
                ['value' => 'completed', 'label' => 'Completed'],
                ['value' => 'overdue', 'label' => 'Overdue'],
            ],
        ]);
    }

    public function trainingsPdf(Request $request): \Illuminate\Http\Response
    {
        return $this->trainings($request);
    }

    public function trips(Request $request): InertiaResponse
    {
        $scope = $this->scopeContext();
        $filters = [
            'carrier_id' => $scope['is_superadmin'] ? (string) $request->input('carrier_id', '') : (string) $scope['carrier_id'],
            'driver_id' => (string) $request->input('driver_id', ''),
            'status' => (string) $request->input('status', ''),
            'date_from' => (string) $request->input('date_from', ''),
            'date_to' => (string) $request->input('date_to', ''),
            'per_page' => max((int) $request->input('per_page', 15), 10),
        ];

        $report = $this->hosReportService->getTripReport($filters, $scope['is_superadmin'] ? null : $scope['carrier_id']);
        $report['trips']->through(function (Trip $trip) {
            return [
                'id' => $trip->id,
                'trip_number' => $trip->trip_number,
                'carrier_name' => $trip->carrier?->name,
                'driver_name' => $trip->driver?->full_name ?: 'Unknown Driver',
                'vehicle_label' => $this->vehicleDisplayLabel($trip->vehicle),
                'origin' => $trip->origin_address ?: 'N/A',
                'destination' => $trip->destination_address ?: ($trip->destination ?: 'N/A'),
                'scheduled_start' => $trip->scheduled_start_date?->format('n/j/Y g:i A'),
                'scheduled_end' => $trip->scheduled_end_date?->format('n/j/Y g:i A'),
                'status' => $trip->status,
                'violations_count' => $trip->violations->count(),
            ];
        });

        return Inertia::render('admin/reports/Trips', [
            'filters' => $filters,
            'trips' => $report['trips'],
            'stats' => $report['stats'],
            'carriers' => $this->carrierOptions($scope),
            'drivers' => $this->driverOptions($scope, $filters['carrier_id']),
            'statuses' => collect(Trip::STATUSES)->map(fn ($status) => [
                'value' => $status,
                'label' => str($status)->replace('_', ' ')->title()->toString(),
            ])->values(),
            'canFilterCarriers' => $scope['is_superadmin'],
        ]);
    }

    public function tripsPdf(Request $request)
    {
        $scope = $this->scopeContext();
        $filters = [
            'carrier_id' => $scope['is_superadmin'] ? $request->input('carrier_id') : $scope['carrier_id'],
            'driver_id' => $request->input('driver_id'),
            'status' => $request->input('status'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];

        return $this->hosReportService->exportTripReportPdf($filters, $scope['is_superadmin'] ? null : $scope['carrier_id']);
    }

    public function tripDetails(int $trip): InertiaResponse
    {
        $scope = $this->scopeContext();
        $tripModel = $this->hosReportService->getTripDetails($trip, $scope['is_superadmin'] ? null : $scope['carrier_id']);
        abort_unless($tripModel, 404);

        return Inertia::render('admin/reports/TripDetails', [
            'trip' => [
                'id' => $tripModel->id,
                'trip_number' => $tripModel->trip_number,
                'status' => $tripModel->status,
                'carrier_name' => $tripModel->carrier?->name,
                'driver_name' => $tripModel->driver?->full_name ?: 'Unknown Driver',
                'vehicle_label' => $this->vehicleDisplayLabel($tripModel->vehicle),
                'origin_address' => $tripModel->origin_address,
                'destination_address' => $tripModel->destination_address ?: $tripModel->destination,
                'scheduled_start' => $tripModel->scheduled_start_date?->format('n/j/Y g:i A'),
                'scheduled_end' => $tripModel->scheduled_end_date?->format('n/j/Y g:i A'),
                'actual_start' => $tripModel->actual_start_time?->format('n/j/Y g:i A'),
                'actual_end' => $tripModel->actual_end_time?->format('n/j/Y g:i A'),
                'description' => $tripModel->description,
                'notes' => $tripModel->notes,
                'driver_notes' => $tripModel->driver_notes,
                'load_type' => $tripModel->load_type,
                'load_weight' => $tripModel->load_weight,
                'has_violations' => (bool) $tripModel->has_violations,
            ],
            'entries' => $tripModel->hosEntries->map(fn ($entry) => [
                'id' => $entry->id,
                'date' => $entry->date?->format('n/j/Y'),
                'status' => $entry->status,
                'start_time' => $entry->start_time?->format('n/j/Y g:i A'),
                'end_time' => $entry->end_time?->format('n/j/Y g:i A'),
                'duration_minutes' => $entry->duration_minutes,
                'location' => $entry->location,
            ])->values(),
            'violations' => $tripModel->violations->map(fn ($violation) => [
                'id' => $violation->id,
                'type' => $violation->violation_type_name,
                'severity' => $violation->severity_name,
                'date' => $violation->violation_date?->format('n/j/Y'),
                'hours_exceeded' => $violation->hours_exceeded,
                'acknowledged' => (bool) $violation->acknowledged,
            ])->values(),
            'gpsPoints' => $tripModel->gpsPoints->map(fn ($point) => [
                'id' => $point->id,
                'recorded_at' => $point->recorded_at?->format('n/j/Y g:i A'),
                'latitude' => $point->latitude,
                'longitude' => $point->longitude,
                'speed_mph' => $point->speed_mph,
            ])->values(),
        ]);
    }

    public function hos(Request $request): InertiaResponse
    {
        $scope = $this->scopeContext();
        $filters = [
            'carrier_id' => $scope['is_superadmin'] ? (string) $request->input('carrier_id', '') : (string) $scope['carrier_id'],
            'driver_id' => (string) $request->input('driver_id', ''),
            'date_from' => (string) $request->input('date_from', ''),
            'date_to' => (string) $request->input('date_to', ''),
            'has_violations' => (string) $request->input('has_violations', ''),
            'per_page' => max((int) $request->input('per_page', 15), 10),
        ];

        $report = $this->hosReportService->getHosReport($filters, $scope['is_superadmin'] ? null : $scope['carrier_id']);
        $report['driverSummaries']->through(function ($summary) {
            return [
                'driver_id' => $summary->user_driver_detail_id,
                'driver_name' => $summary->driver?->full_name ?: 'Unknown Driver',
                'carrier_name' => $summary->carrier?->name,
                'total_days' => (int) $summary->total_days,
                'driving_hours' => round(((int) $summary->total_driving_minutes) / 60, 2),
                'on_duty_hours' => round(((int) $summary->total_on_duty_minutes) / 60, 2),
                'off_duty_hours' => round(((int) $summary->total_off_duty_minutes) / 60, 2),
                'days_with_violations' => (int) $summary->days_with_violations,
                'first_log_date' => $summary->first_log_date ? Carbon::parse($summary->first_log_date)->format('n/j/Y') : null,
                'last_log_date' => $summary->last_log_date ? Carbon::parse($summary->last_log_date)->format('n/j/Y') : null,
            ];
        });

        return Inertia::render('admin/reports/Hos', [
            'filters' => $filters,
            'driverSummaries' => $report['driverSummaries'],
            'stats' => $report['stats'],
            'dateRangeLabel' => $report['dateRangeLabel'],
            'carriers' => $this->carrierOptions($scope),
            'drivers' => $this->driverOptions($scope, $filters['carrier_id']),
            'canFilterCarriers' => $scope['is_superadmin'],
        ]);
    }

    public function hosPdf(Request $request)
    {
        $scope = $this->scopeContext();
        $filters = [
            'carrier_id' => $scope['is_superadmin'] ? $request->input('carrier_id') : $scope['carrier_id'],
            'driver_id' => $request->input('driver_id'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'has_violations' => $request->input('has_violations'),
        ];

        return $this->hosReportService->exportHosReportPdf($filters, $scope['is_superadmin'] ? null : $scope['carrier_id']);
    }

    public function hosDetails(Request $request, int $driver): InertiaResponse
    {
        $scope = $this->scopeContext();
        $driverModel = UserDriverDetail::query()->with(['user', 'carrier'])->findOrFail($driver);
        $this->ensureAllowedCarrier((int) $driverModel->carrier_id, $scope);

        $filters = [
            'date_from' => (string) $request->input('date_from', ''),
            'date_to' => (string) $request->input('date_to', ''),
        ];

        $logsQuery = HosDailyLog::query()->where('user_driver_detail_id', $driverModel->id);
        if ($dateFrom = $this->parseUsDate($filters['date_from'])) {
            $logsQuery->whereDate('date', '>=', $dateFrom->format('Y-m-d'));
        }
        if ($dateTo = $this->parseUsDate($filters['date_to'])) {
            $logsQuery->whereDate('date', '<=', $dateTo->format('Y-m-d'));
        }

        $dailyLogs = $logsQuery->orderByDesc('date')->paginate(15)->withQueryString();
        $dailyLogs->through(fn (HosDailyLog $log) => [
            'id' => $log->id,
            'date' => $log->date?->format('n/j/Y'),
            'driving_hours' => $log->total_driving_hours,
            'on_duty_hours' => $log->total_on_duty_hours,
            'off_duty_hours' => $log->total_off_duty_hours,
            'has_violations' => (bool) $log->has_violations,
            'signed_at' => $log->signed_at?->format('n/j/Y g:i A'),
            'duty_period_start' => $log->duty_period_start?->format('n/j/Y g:i A'),
            'duty_period_end' => $log->duty_period_end?->format('n/j/Y g:i A'),
        ]);

        $violationsQuery = HosViolation::query()->where('user_driver_detail_id', $driverModel->id);
        if ($dateFrom = $this->parseUsDate($filters['date_from'])) {
            $violationsQuery->whereDate('violation_date', '>=', $dateFrom->format('Y-m-d'));
        }
        if ($dateTo = $this->parseUsDate($filters['date_to'])) {
            $violationsQuery->whereDate('violation_date', '<=', $dateTo->format('Y-m-d'));
        }
        $totalViolations = $violationsQuery->count();

        $statsLogs = HosDailyLog::query()
            ->where('user_driver_detail_id', $driverModel->id)
            ->when($filters['date_from'] !== '', fn (Builder $builder) => $builder->whereDate('date', '>=', $this->parseUsDate($filters['date_from'])?->format('Y-m-d')))
            ->when($filters['date_to'] !== '', fn (Builder $builder) => $builder->whereDate('date', '<=', $this->parseUsDate($filters['date_to'])?->format('Y-m-d')))
            ->get();

        return Inertia::render('admin/reports/HosDetails', [
            'driver' => [
                'id' => $driverModel->id,
                'name' => $driverModel->full_name,
                'carrier_name' => $driverModel->carrier?->name,
                'email' => $driverModel->user?->email,
            ],
            'filters' => $filters,
            'dailyLogs' => $dailyLogs,
            'stats' => [
                'total_days' => $statsLogs->count(),
                'total_driving_hours' => round(($statsLogs->sum('total_driving_minutes')) / 60, 2),
                'total_on_duty_hours' => round(($statsLogs->sum('total_on_duty_minutes')) / 60, 2),
                'total_off_duty_hours' => round(($statsLogs->sum('total_off_duty_minutes')) / 60, 2),
                'avg_driving_hours' => round(($statsLogs->avg('total_driving_minutes') ?? 0) / 60, 2),
                'days_with_violations' => $statsLogs->where('has_violations', true)->count(),
                'total_violations' => $totalViolations,
            ],
        ]);
    }

    public function violations(Request $request): InertiaResponse
    {
        $scope = $this->scopeContext();
        $filters = [
            'carrier_id' => $scope['is_superadmin'] ? (string) $request->input('carrier_id', '') : (string) $scope['carrier_id'],
            'driver_id' => (string) $request->input('driver_id', ''),
            'violation_type' => (string) $request->input('violation_type', ''),
            'severity' => (string) $request->input('severity', ''),
            'date_from' => (string) $request->input('date_from', ''),
            'date_to' => (string) $request->input('date_to', ''),
            'acknowledged' => (string) $request->input('acknowledged', ''),
            'per_page' => max((int) $request->input('per_page', 15), 10),
        ];

        $report = $this->hosReportService->getViolationsReport($filters, $scope['is_superadmin'] ? null : $scope['carrier_id']);
        $report['violations']->through(function (HosViolation $violation) {
            return [
                'id' => $violation->id,
                'driver_name' => $violation->driver?->full_name ?: 'Unknown Driver',
                'carrier_name' => $violation->carrier?->name,
                'trip_number' => $violation->trip?->trip_number,
                'violation_type' => $violation->violation_type_name,
                'severity' => $violation->severity_name,
                'date' => $violation->violation_date?->format('n/j/Y'),
                'hours_exceeded' => $violation->hours_exceeded,
                'acknowledged' => (bool) $violation->acknowledged,
            ];
        });

        return Inertia::render('admin/reports/Violations', [
            'filters' => $filters,
            'violations' => $report['violations'],
            'stats' => $report['stats'],
            'carriers' => $this->carrierOptions($scope),
            'drivers' => $this->driverOptions($scope, $filters['carrier_id']),
            'violationTypes' => collect($report['violationTypes'])->map(fn ($type) => [
                'value' => $type,
                'label' => str($type)->replace('_', ' ')->title()->toString(),
            ])->values(),
            'severities' => collect($report['severities'])->map(fn ($severity) => [
                'value' => $severity,
                'label' => str($severity)->title()->toString(),
            ])->values(),
            'canFilterCarriers' => $scope['is_superadmin'],
        ]);
    }

    public function violationsPdf(Request $request)
    {
        $scope = $this->scopeContext();
        $filters = [
            'carrier_id' => $scope['is_superadmin'] ? $request->input('carrier_id') : $scope['carrier_id'],
            'driver_id' => $request->input('driver_id'),
            'violation_type' => $request->input('violation_type'),
            'severity' => $request->input('severity'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'acknowledged' => $request->input('acknowledged'),
        ];

        return $this->hosReportService->exportViolationsReportPdf($filters, $scope['is_superadmin'] ? null : $scope['carrier_id']);
    }

    public function violationDetails(int $violation): InertiaResponse
    {
        $scope = $this->scopeContext();
        $violationModel = $this->hosReportService->getViolationDetails($violation, $scope['is_superadmin'] ? null : $scope['carrier_id']);
        abort_unless($violationModel, 404);

        return Inertia::render('admin/reports/ViolationDetails', [
            'violation' => [
                'id' => $violationModel->id,
                'driver_name' => $violationModel->driver?->full_name ?: 'Unknown Driver',
                'driver_email' => $violationModel->driver?->user?->email,
                'carrier_name' => $violationModel->carrier?->name,
                'vehicle_label' => $this->vehicleDisplayLabel($violationModel->vehicle),
                'trip_number' => $violationModel->trip?->trip_number,
                'type' => $violationModel->violation_type_name,
                'severity' => $violationModel->severity_name,
                'date' => $violationModel->violation_date?->format('n/j/Y'),
                'hours_exceeded' => $violationModel->hours_exceeded,
                'fmcsa_rule_reference' => $violationModel->fmcsa_rule_reference,
                'acknowledged' => (bool) $violationModel->acknowledged,
                'acknowledged_at' => $violationModel->acknowledged_at?->format('n/j/Y g:i A'),
                'acknowledged_by' => $violationModel->acknowledgedByUser?->name,
                'has_penalty' => (bool) $violationModel->has_penalty,
                'penalty_type' => $violationModel->penalty_type,
                'penalty_start' => $violationModel->penalty_start?->format('n/j/Y g:i A'),
                'penalty_end' => $violationModel->penalty_end?->format('n/j/Y g:i A'),
                'penalty_notes' => $violationModel->penalty_notes,
                'is_forgiven' => (bool) $violationModel->is_forgiven,
                'forgiven_at' => $violationModel->forgiven_at?->format('n/j/Y g:i A'),
                'forgiveness_reason' => $violationModel->forgiveness_reason,
            ],
        ]);
    }

    public function migrations(Request $request): InertiaResponse
    {
        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'date_from' => (string) $request->input('date_from', ''),
            'date_to' => (string) $request->input('date_to', ''),
            'source_carrier_id' => (string) $request->input('source_carrier_id', ''),
            'target_carrier_id' => (string) $request->input('target_carrier_id', ''),
            'driver_user_id' => (string) $request->input('driver_user_id', ''),
            'status' => (string) $request->input('status', ''),
        ];

        $migrations = $this->migrationReportService->getMigrations($filters, 15);
        $migrations->through(fn (MigrationRecord $record) => [
            'id' => $record->id,
            'driver_name' => $record->driverUser?->name ?: 'Unknown Driver',
            'source_carrier' => $record->sourceCarrier?->name,
            'target_carrier' => $record->targetCarrier?->name,
            'migrated_at' => $record->migrated_at?->format('n/j/Y g:i A'),
            'migrated_by' => $record->migratedByUser?->name,
            'status' => $record->status,
            'reason' => $record->reason ?: 'N/A',
            'rolled_back_at' => $record->rolled_back_at?->format('n/j/Y g:i A'),
            'can_rollback' => $record->canRollback(),
        ]);

        $stats = $this->migrationReportService->getMigrationStatistics($filters);
        $reasons = $this->migrationReportService->getMigrationReasons($filters);

        return Inertia::render('admin/reports/Migrations', [
            'filters' => $filters,
            'migrations' => $migrations,
            'stats' => $stats,
            'reasons' => $reasons->values(),
            'carriers' => Carrier::query()->orderBy('name')->get(['id', 'name'])->map(fn (Carrier $carrier) => [
                'id' => $carrier->id,
                'name' => $carrier->name,
            ])->values(),
            'drivers' => UserDriverDetail::query()->with('user')->orderBy('last_name')->get()->map(fn (UserDriverDetail $driver) => [
                'id' => $driver->user_id,
                'name' => $driver->full_name ?: ($driver->user?->name ?: 'Unknown Driver'),
            ])->values(),
            'statusOptions' => [
                ['value' => '', 'label' => 'All statuses'],
                ['value' => MigrationRecord::STATUS_COMPLETED, 'label' => 'Completed'],
                ['value' => MigrationRecord::STATUS_ROLLED_BACK, 'label' => 'Rolled Back'],
            ],
        ]);
    }

    protected function scopeContext(): array
    {
        $user = auth()->user();

        return [
            'is_superadmin' => (bool) ($user?->hasRole('superadmin') ?? false),
            'carrier_id' => $user?->carrierDetails?->carrier_id ? (int) $user->carrierDetails->carrier_id : null,
        ];
    }

    protected function carrierFilterValue(Request $request, string $key): string
    {
        return (string) $request->input($key, $request->input('carrier', ''));
    }

    protected function applyDriverScope(Builder $query, array $scope, string $carrierId = ''): void
    {
        if ($carrierId !== '') {
            $query->where('carrier_id', (int) $carrierId);
            return;
        }

        if (! $scope['is_superadmin']) {
            $query->where('carrier_id', $scope['carrier_id'] ?: 0);
        }
    }

    protected function applyVehicleScope(Builder $query, array $scope, string $carrierId = ''): void
    {
        if ($carrierId !== '') {
            $query->where('carrier_id', (int) $carrierId);
            return;
        }

        if (! $scope['is_superadmin']) {
            $query->where('carrier_id', $scope['carrier_id'] ?: 0);
        }
    }

    protected function applyCarrierScope(Builder $query, array $scope, string $carrierId = ''): void
    {
        if ($carrierId !== '') {
            $query->where('id', (int) $carrierId);
            return;
        }

        if (! $scope['is_superadmin']) {
            $query->where('id', $scope['carrier_id'] ?: 0);
        }
    }

    protected function applyCarrierDocumentScope(Builder $query, array $scope, string $carrierId = ''): void
    {
        if ($carrierId !== '') {
            $query->where('carrier_id', (int) $carrierId);
            return;
        }

        if (! $scope['is_superadmin']) {
            $query->where('carrier_id', $scope['carrier_id'] ?: 0);
        }
    }

    protected function applyVehicleRelationScope(Builder $query, array $scope, string $carrierId = ''): void
    {
        $query->whereHas('vehicle', function (Builder $vehicleQuery) use ($scope, $carrierId) {
            $this->applyVehicleScope($vehicleQuery, $scope, $carrierId);
        });
    }

    protected function applyAccidentScope(Builder $query, array $scope, string $carrierId = ''): void
    {
        $query->whereHas('userDriverDetail', function (Builder $driverQuery) use ($scope, $carrierId) {
            $this->applyDriverScope($driverQuery, $scope, $carrierId);
        });
    }

    protected function ensureAllowedCarrier(int $carrierId, array $scope): void
    {
        if (! $scope['is_superadmin'] && (int) $scope['carrier_id'] !== $carrierId) {
            abort(403);
        }
    }

    protected function carrierOptions(array $scope): array
    {
        return Carrier::query()
            ->when(! $scope['is_superadmin'], fn (Builder $builder) => $builder->where('id', $scope['carrier_id'] ?: 0))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Carrier $carrier) => [
                'id' => $carrier->id,
                'name' => $carrier->name,
            ])
            ->values()
            ->all();
    }

    protected function driverOptions(array $scope, string $carrierId = ''): array
    {
        $query = UserDriverDetail::query()->with('user')->where('status', UserDriverDetail::STATUS_ACTIVE);
        $this->applyDriverScope($query, $scope, $carrierId);

        return $query->orderBy('last_name')
            ->get()
            ->map(fn (UserDriverDetail $driver) => [
                'id' => $driver->id,
                'name' => $driver->full_name ?: ($driver->user?->name ?: 'Unknown Driver'),
            ])
            ->values()
            ->all();
    }

    protected function driverRow(UserDriverDetail $driver): array
    {
        return [
            'id' => $driver->id,
            'driver_name' => $driver->full_name ?: ($driver->user?->name ?: 'Unknown Driver'),
            'email' => $driver->user?->email,
            'carrier_name' => $driver->carrier?->name,
            'registered_at' => $driver->created_at?->format('n/j/Y'),
            'updated_at' => $driver->updated_at?->format('n/j/Y'),
            'termination_date' => $driver->termination_date?->format('n/j/Y'),
            'license_number' => $driver->primaryLicense?->license_number,
            'status_label' => $driver->status_name,
        ];
    }

    protected function accidentRow(DriverAccident $accident): array
    {
        return [
            'id' => $accident->id,
            'driver_name' => $accident->userDriverDetail?->full_name,
            'carrier_name' => $accident->userDriverDetail?->carrier?->name,
            'accident_date' => $accident->accident_date?->format('n/j/Y'),
            'nature' => $accident->nature_of_accident,
            'injuries_label' => $accident->had_injuries ? ((int) ($accident->number_of_injuries ?? 0) . ' injuries') : 'No injuries',
            'fatalities_label' => $accident->had_fatalities ? ((int) ($accident->number_of_fatalities ?? 0) . ' fatalities') : 'No fatalities',
            'comments' => $accident->comments,
        ];
    }

    protected function carrierDocumentRow(Carrier $carrier): array
    {
        $documents = $carrier->documents;
        $approved = $documents->where('status', CarrierDocument::STATUS_APPROVED)->count();
        $pending = $documents->where('status', CarrierDocument::STATUS_PENDING)->count();
        $inProcess = $documents->where('status', CarrierDocument::STATUS_IN_PROCESS)->count();
        $total = $documents->count();

        return [
            'id' => $carrier->id,
            'slug' => $carrier->slug,
            'carrier_name' => $carrier->name,
            'total_documents' => $total,
            'approved_documents' => $approved,
            'pending_documents' => $pending,
            'in_process_documents' => $inProcess,
            'completion_rate' => $total > 0 ? round(($approved / $total) * 100, 1) : 0,
            'document_types' => $documents->map(fn (CarrierDocument $document) => [
                'id' => $document->id,
                'type' => $document->documentType?->name ?: 'Document',
                'status' => $document->status_name,
                'date' => $document->date?->format('n/j/Y'),
                'url' => $document->getFirstMediaUrl('carrier_documents'),
            ])->values(),
        ];
    }

    protected function vehicleDisplayLabel(?Vehicle $vehicle): string
    {
        if (! $vehicle) {
            return 'Vehicle';
        }

        return trim(collect([
            $vehicle->company_unit_number ? 'Unit #' . $vehicle->company_unit_number : null,
            trim(($vehicle->year ?: '') . ' ' . ($vehicle->make ?: '') . ' ' . ($vehicle->model ?: '')),
        ])->filter()->implode(' - ')) ?: 'Vehicle';
    }

    protected function parseUsDate(?string $value, ?Carbon $default = null): ?Carbon
    {
        if (! $value) {
            return $default?->copy();
        }

        foreach (['n/j/Y', 'm/d/Y', 'Y-m-d'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value);
            } catch (\Throwable) {
            }
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return $default?->copy();
        }
    }

    protected function downloadSimplePdf(
        string $title,
        string $filename,
        array $columns,
        array $rows,
        array $filters = [],
        array $stats = [],
    ): \Illuminate\Http\Response {
        $pdf = Pdf::loadView('admin.reports.pdf.generic-table', [
            'title' => $title,
            'columns' => $columns,
            'rows' => $rows,
            'filters' => $filters,
            'stats' => $stats,
            'generatedAt' => now()->format('m/d/Y H:i:s'),
        ]);

        $pdf->setPaper('a4', 'landscape');
        $pdf->setOptions([
            'isRemoteEnabled' => true,
            'isHtml5ParserEnabled' => true,
        ]);

        return $pdf->download($filename);
    }

    protected function filterSummary(array $pairs): array
    {
        return collect($pairs)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value, $label) => $label . ': ' . $value)
            ->values()
            ->all();
    }

    protected function carrierNameById(string $carrierId): ?string
    {
        if ($carrierId === '') {
            return null;
        }

        return Carrier::query()->whereKey((int) $carrierId)->value('name');
    }

    protected function driverNameById(string $driverId): ?string
    {
        if ($driverId === '') {
            return null;
        }

        $driver = UserDriverDetail::query()->with('user')->find((int) $driverId);
        return $driver?->full_name ?: $driver?->user?->name;
    }

    protected function carrierDocumentStatusLabel(string $status): ?string
    {
        if ($status === '') {
            return null;
        }

        return match ((int) $status) {
            CarrierDocument::STATUS_PENDING => 'Pending',
            CarrierDocument::STATUS_APPROVED => 'Approved',
            CarrierDocument::STATUS_REJECTED => 'Rejected',
            CarrierDocument::STATUS_IN_PROCESS => 'In Process',
            default => null,
        };
    }
}
