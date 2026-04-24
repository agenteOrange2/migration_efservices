<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Controller;
use App\Models\Admin\Driver\DriverAccident;
use App\Models\Admin\Driver\DriverLicense;
use App\Models\Admin\Driver\DriverMedicalQualification;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Carrier;
use App\Models\EmergencyRepair;
use App\Models\Hos\HosDailyLog;
use App\Models\Hos\HosViolation;
use App\Models\Trip;
use App\Models\UserDriverDetail;
use App\Services\Carrier\CarrierReportService;
use App\Services\HosReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Admin\Vehicle\VehicleMaintenance;

class CarrierReportsController extends Controller
{
    public function __construct(
        protected CarrierReportService $carrierReportService,
        protected HosReportService $hosReportService,
    ) {
    }

    public function index(): Response
    {
        $carrierId = $this->carrierId();
        $carrier = $this->carrier();
        $stats = $this->carrierReportService->getDashboardMetrics($carrierId);

        $stats['trips'] = [
            'total' => Trip::query()->where('carrier_id', $carrierId)->count(),
        ];
        $stats['hos'] = [
            'total_logs' => HosDailyLog::query()->where('carrier_id', $carrierId)->count(),
        ];
        $stats['violations'] = [
            'total' => HosViolation::query()->where('carrier_id', $carrierId)->count(),
        ];

        return Inertia::render('carrier/reports/Index', [
            'carrier' => [
                'id' => $carrier?->id,
                'name' => $carrier?->name,
            ],
            'stats' => $stats,
            'quickLinks' => [
                ['title' => 'Driver Reports', 'route' => route('carrier.reports.drivers'), 'icon' => 'Users', 'meta' => ($stats['drivers']['total'] ?? 0) . ' drivers tracked'],
                ['title' => 'Vehicle Reports', 'route' => route('carrier.reports.vehicles'), 'icon' => 'Truck', 'meta' => ($stats['vehicles']['total'] ?? 0) . ' units in fleet'],
                ['title' => 'Accident Reports', 'route' => route('carrier.reports.accidents'), 'icon' => 'AlertTriangle', 'meta' => ($stats['accidents']['total'] ?? 0) . ' accidents recorded'],
                ['title' => 'Medical Reports', 'route' => route('carrier.reports.medical-records'), 'icon' => 'FileHeart', 'meta' => ($stats['medical_records']['expiring_soon'] ?? 0) . ' expiring soon'],
                ['title' => 'License Reports', 'route' => route('carrier.reports.licenses'), 'icon' => 'CreditCard', 'meta' => ($stats['licenses']['expiring_soon'] ?? 0) . ' expiring soon'],
                ['title' => 'Maintenance Reports', 'route' => route('carrier.reports.maintenance'), 'icon' => 'Wrench', 'meta' => ($stats['maintenance']['total'] ?? 0) . ' maintenance records'],
                ['title' => 'Repair Reports', 'route' => route('carrier.reports.repairs'), 'icon' => 'Wrench', 'meta' => ($stats['repairs']['total'] ?? 0) . ' emergency repairs'],
                ['title' => 'Monthly Summary', 'route' => route('carrier.reports.monthly'), 'icon' => 'CalendarDays', 'meta' => '12-month operational summary'],
                ['title' => 'Trip Reports', 'route' => route('carrier.reports.trips'), 'icon' => 'MapPin', 'meta' => ($stats['trips']['total'] ?? 0) . ' trips available'],
                ['title' => 'HOS Reports', 'route' => route('carrier.reports.hos'), 'icon' => 'Clock3', 'meta' => ($stats['hos']['total_logs'] ?? 0) . ' daily logs'],
                ['title' => 'Violations Reports', 'route' => route('carrier.reports.violations'), 'icon' => 'AlertOctagon', 'meta' => ($stats['violations']['total'] ?? 0) . ' violations found'],
            ],
            'generatedAt' => now()->format('n/j/Y g:i A'),
        ]);
    }

    public function drivers(Request $request): Response
    {
        $carrierId = $this->carrierId();
        $filters = $this->inputFilters($request, ['search', 'status', 'date_from', 'date_to', 'per_page'], [
            'search' => '',
            'status' => '',
            'date_from' => '',
            'date_to' => '',
            'per_page' => 10,
        ]);
        $report = $this->carrierReportService->getDriverReport($carrierId, $this->normalizedFilters($filters));
        $report['drivers']->through(function (UserDriverDetail $driver) {
            $license = $driver->primaryLicense;

            return [
                'id' => $driver->id,
                'name' => $this->driverName($driver),
                'email' => $driver->user?->email,
                'phone' => $driver->formatted_phone ?: $driver->phone,
                'license_number' => $license?->license_number,
                'license_state' => $license?->state_of_issue,
                'license_expiration' => $this->formatDate($license?->expiration_date),
                'status' => match((int) $driver->status) {
                    UserDriverDetail::STATUS_ACTIVE => 'active',
                    UserDriverDetail::STATUS_INACTIVE => 'inactive',
                    UserDriverDetail::STATUS_PENDING => 'pending',
                    default => 'unknown',
                },
                'registered_at' => $this->formatDate($driver->created_at),
                'has_expiring_license' => (bool) ($driver->has_expiring_license ?? false),
            ];
        });

        return Inertia::render('carrier/reports/Drivers', [
            'filters' => $filters,
            'drivers' => $report['drivers'],
            'stats' => $report['stats'],
            'statusOptions' => [
                ['value' => '', 'label' => 'All Statuses'],
                ['value' => 'active', 'label' => 'Active'],
                ['value' => 'inactive', 'label' => 'Inactive'],
                ['value' => 'pending', 'label' => 'Pending'],
            ],
        ]);
    }

    public function driversExportPdf(Request $request)
    {
        return $this->carrierReportService->exportDriverReportPdf(
            $this->carrierId(),
            $this->normalizedFilters($this->inputFilters($request, ['search', 'status', 'date_from', 'date_to']))
        );
    }

    public function vehicles(Request $request): Response
    {
        $carrierId = $this->carrierId();
        $filters = $this->inputFilters($request, ['search', 'status', 'date_from', 'date_to', 'per_page'], [
            'search' => '',
            'status' => '',
            'date_from' => '',
            'date_to' => '',
            'per_page' => 10,
        ]);
        $report = $this->carrierReportService->getVehicleReport($carrierId, $this->normalizedFilters($filters));
        $report['vehicles']->through(function (Vehicle $vehicle) {
            return [
                'id' => $vehicle->id,
                'unit_number' => $vehicle->company_unit_number,
                'make' => $vehicle->make,
                'model' => $vehicle->model,
                'vin' => $vehicle->vin,
                'registration_expiration' => $this->formatDate($vehicle->registration_expiration_date),
                'status' => $vehicle->out_of_service ? 'out_of_service' : ($vehicle->suspended ? 'suspended' : 'active'),
                'created_at' => $this->formatDate($vehicle->created_at),
                'has_expiring_registration' => (bool) ($vehicle->has_expiring_registration ?? false),
            ];
        });

        return Inertia::render('carrier/reports/Vehicles', [
            'filters' => $filters,
            'vehicles' => $report['vehicles'],
            'stats' => $report['stats'],
            'statusOptions' => [
                ['value' => '', 'label' => 'All Statuses'],
                ['value' => 'active', 'label' => 'Active'],
                ['value' => 'out_of_service', 'label' => 'Out of Service'],
                ['value' => 'suspended', 'label' => 'Suspended'],
            ],
        ]);
    }

    public function vehiclesExportPdf(Request $request)
    {
        return $this->carrierReportService->exportVehicleReportPdf(
            $this->carrierId(),
            $this->normalizedFilters($this->inputFilters($request, ['search', 'status', 'date_from', 'date_to']))
        );
    }

    public function accidents(Request $request): Response
    {
        $carrierId = $this->carrierId();
        $filters = $this->inputFilters($request, ['search', 'driver', 'date_from', 'date_to', 'per_page'], [
            'search' => '',
            'driver' => '',
            'date_from' => '',
            'date_to' => '',
            'per_page' => 10,
        ]);
        $report = $this->carrierReportService->getAccidentReport($carrierId, $this->normalizedFilters($filters));
        $report['accidents']->through(function (DriverAccident $accident) {
            $driver = $accident->userDriverDetail;

            return [
                'id' => $accident->id,
                'accident_date' => $this->formatDate($accident->accident_date),
                'driver_name' => $this->driverName($driver),
                'driver_email' => $driver?->user?->email,
                'nature' => $accident->nature_of_accident,
                'severity' => $accident->had_fatalities ? 'critical' : ($accident->had_injuries ? 'serious' : 'minor'),
                'fatalities' => $accident->had_fatalities ? (int) ($accident->number_of_fatalities ?? 0) : 0,
                'injuries' => $accident->had_injuries ? (int) ($accident->number_of_injuries ?? 0) : 0,
                'comments' => $accident->comments,
            ];
        });

        return Inertia::render('carrier/reports/Accidents', [
            'filters' => $filters,
            'accidents' => $report['accidents'],
            'stats' => $report['stats'],
            'drivers' => $this->driverOptions($carrierId),
        ]);
    }

    public function accidentsExportPdf(Request $request)
    {
        return $this->carrierReportService->exportAccidentReportPdf(
            $this->carrierId(),
            $this->normalizedFilters($this->inputFilters($request, ['search', 'driver', 'date_from', 'date_to']))
        );
    }

    public function medicalRecords(Request $request): Response
    {
        $carrierId = $this->carrierId();
        $filters = $this->inputFilters($request, ['search', 'driver', 'expiration_status', 'date_from', 'date_to', 'per_page'], [
            'search' => '',
            'driver' => '',
            'expiration_status' => '',
            'date_from' => '',
            'date_to' => '',
            'per_page' => 10,
        ]);
        $report = $this->carrierReportService->getMedicalRecordsReport($carrierId, $this->normalizedFilters($filters));
        $report['medicalRecords']->through(function (DriverMedicalQualification $record) {
            $driver = $record->userDriverDetail;

            return [
                'id' => $record->id,
                'driver_name' => $this->driverName($driver),
                'driver_phone' => $driver?->formatted_phone ?: $driver?->phone,
                'examiner' => $record->medical_examiner_name,
                'registry_number' => $record->medical_examiner_registry_number,
                'expiration_date' => $this->formatDate($record->medical_card_expiration_date),
                'created_at' => $this->formatDate($record->created_at),
                'status' => $record->is_expired ? 'expired' : ($record->is_expiring ? 'expiring_soon' : 'valid'),
            ];
        });

        return Inertia::render('carrier/reports/MedicalRecords', [
            'filters' => $filters,
            'medicalRecords' => $report['medicalRecords'],
            'stats' => $report['stats'],
            'drivers' => $this->driverOptions($carrierId),
            'statusOptions' => [
                ['value' => '', 'label' => 'All Statuses'],
                ['value' => 'valid', 'label' => 'Valid'],
                ['value' => 'expiring_soon', 'label' => 'Expiring Soon'],
                ['value' => 'expired', 'label' => 'Expired'],
            ],
        ]);
    }

    public function medicalRecordsExportPdf(Request $request)
    {
        return $this->carrierReportService->exportMedicalRecordsReportPdf(
            $this->carrierId(),
            $this->normalizedFilters($this->inputFilters($request, ['search', 'driver', 'expiration_status', 'date_from', 'date_to']))
        );
    }

    public function licenses(Request $request): Response
    {
        $carrierId = $this->carrierId();
        $filters = $this->inputFilters($request, ['search', 'driver', 'license_type', 'expiration_status', 'date_from', 'date_to', 'per_page'], [
            'search' => '',
            'driver' => '',
            'license_type' => '',
            'expiration_status' => '',
            'date_from' => '',
            'date_to' => '',
            'per_page' => 10,
        ]);
        $report = $this->carrierReportService->getLicenseReport($carrierId, $this->normalizedFilters($filters));
        $report['licenses']->through(function (DriverLicense $license) {
            $driver = $license->driverDetail;

            return [
                'id' => $license->id,
                'driver_name' => $this->driverName($driver),
                'is_primary' => (bool) $license->is_primary,
                'license_number' => $license->license_number,
                'license_type' => $license->is_cdl ? 'CDL' : 'Non-CDL',
                'license_class' => $license->license_class,
                'state' => $license->state_of_issue,
                'issue_date' => $this->formatDate($license->issue_date ?: $license->created_at),
                'expiration_date' => $this->formatDate($license->expiration_date),
                'status' => $license->is_expired ? 'expired' : ($license->is_expiring ? 'expiring_soon' : 'valid'),
            ];
        });

        return Inertia::render('carrier/reports/Licenses', [
            'filters' => $filters,
            'licenses' => $report['licenses'],
            'stats' => $report['stats'],
            'drivers' => $this->driverOptions($carrierId),
            'licenseTypeOptions' => [
                ['value' => '', 'label' => 'All Types'],
                ['value' => 'cdl', 'label' => 'CDL'],
                ['value' => 'non_cdl', 'label' => 'Non-CDL'],
                ['value' => 'primary', 'label' => 'Primary Only'],
            ],
            'statusOptions' => [
                ['value' => '', 'label' => 'All Statuses'],
                ['value' => 'valid', 'label' => 'Valid'],
                ['value' => 'expiring_soon', 'label' => 'Expiring Soon'],
                ['value' => 'expired', 'label' => 'Expired'],
            ],
        ]);
    }

    public function licensesExportPdf(Request $request)
    {
        return $this->carrierReportService->exportLicenseReportPdf(
            $this->carrierId(),
            $this->normalizedFilters($this->inputFilters($request, ['search', 'driver', 'license_type', 'expiration_status', 'date_from', 'date_to']))
        );
    }

    public function maintenance(Request $request): Response
    {
        $carrierId = $this->carrierId();
        $filters = $this->inputFilters($request, ['search', 'vehicle', 'type', 'status', 'date_from', 'date_to', 'per_page'], [
            'search' => '',
            'vehicle' => '',
            'type' => '',
            'status' => '',
            'date_from' => '',
            'date_to' => '',
            'per_page' => 10,
        ]);
        $report = $this->carrierReportService->getMaintenanceReport($carrierId, $this->normalizedFilters($filters));
        $report['maintenanceRecords']->through(function (VehicleMaintenance $maintenance) {
            return [
                'id' => $maintenance->id,
                'vehicle_label' => trim(implode(' ', array_filter([$maintenance->vehicle?->company_unit_number, $maintenance->vehicle?->make, $maintenance->vehicle?->model]))),
                'service_tasks' => $maintenance->service_tasks,
                'description' => $maintenance->description,
                'service_date' => $this->formatDate($maintenance->service_date),
                'next_service_date' => $this->formatDate($maintenance->next_service_date),
                'vendor_mechanic' => $maintenance->vendor_mechanic,
                'cost' => (float) ($maintenance->cost ?? 0),
                'status' => $maintenance->status ? 'completed' : 'pending',
            ];
        });

        return Inertia::render('carrier/reports/Maintenance', [
            'filters' => $filters,
            'maintenanceRecords' => $report['maintenanceRecords'],
            'stats' => $report['stats'],
            'vehicles' => $this->vehicleOptions($carrierId),
            'statusOptions' => [
                ['value' => '', 'label' => 'All Statuses'],
                ['value' => 'completed', 'label' => 'Completed'],
                ['value' => 'pending', 'label' => 'Pending'],
            ],
        ]);
    }

    public function maintenanceExportPdf(Request $request)
    {
        return $this->carrierReportService->exportMaintenanceReportPdf(
            $this->carrierId(),
            $this->normalizedFilters($this->inputFilters($request, ['search', 'vehicle', 'type', 'status', 'date_from', 'date_to']))
        );
    }

    public function repairs(Request $request): Response
    {
        $carrierId = $this->carrierId();
        $filters = $this->inputFilters($request, ['search', 'vehicle', 'repair_type', 'status', 'date_from', 'date_to', 'per_page'], [
            'search' => '',
            'vehicle' => '',
            'repair_type' => '',
            'status' => '',
            'date_from' => '',
            'date_to' => '',
            'per_page' => 10,
        ]);
        $report = $this->carrierReportService->getRepairReport($carrierId, $this->normalizedFilters($filters));
        $report['repairRecords']->through(function (EmergencyRepair $repair) {
            return [
                'id' => $repair->id,
                'vehicle_label' => trim(implode(' ', array_filter([$repair->vehicle?->company_unit_number, $repair->vehicle?->make, $repair->vehicle?->model]))),
                'repair_name' => $repair->repair_name,
                'description' => $repair->description,
                'repair_date' => $this->formatDate($repair->repair_date),
                'cost' => (float) ($repair->cost ?? 0),
                'status' => (string) ($repair->status ?: 'pending'),
            ];
        });

        return Inertia::render('carrier/reports/Repairs', [
            'filters' => $filters,
            'repairRecords' => $report['repairRecords'],
            'stats' => $report['stats'],
            'vehicles' => $this->vehicleOptions($carrierId),
            'statusOptions' => [
                ['value' => '', 'label' => 'All Statuses'],
                ['value' => 'completed', 'label' => 'Completed'],
                ['value' => 'in_progress', 'label' => 'In Progress'],
                ['value' => 'pending', 'label' => 'Pending'],
            ],
        ]);
    }

    public function repairsExportPdf(Request $request)
    {
        return $this->carrierReportService->exportRepairReportPdf(
            $this->carrierId(),
            $this->normalizedFilters($this->inputFilters($request, ['search', 'vehicle', 'repair_type', 'status', 'date_from', 'date_to']))
        );
    }

    public function monthly(Request $request): Response
    {
        $filters = $this->inputFilters($request, ['date_from', 'date_to'], [
            'date_from' => now()->subMonths(11)->startOfMonth()->format('n/j/Y'),
            'date_to' => now()->format('n/j/Y'),
        ]);
        $report = $this->carrierReportService->getMonthlySummary($this->carrierId(), $this->normalizedFilters($filters));

        return Inertia::render('carrier/reports/Monthly', [
            'filters' => $filters,
            'monthlyData' => $report['monthlyData'],
            'startDate' => $report['startDate'],
            'endDate' => $report['endDate'],
        ]);
    }

    public function monthlyExportPdf(Request $request)
    {
        return $this->carrierReportService->exportMonthlySummaryPdf(
            $this->carrierId(),
            $this->normalizedFilters($this->inputFilters($request, ['date_from', 'date_to']))
        );
    }

    public function trips(Request $request): Response
    {
        $filters = $this->inputFilters($request, ['driver_id', 'status', 'date_from', 'date_to', 'per_page'], [
            'driver_id' => '',
            'status' => '',
            'date_from' => '',
            'date_to' => '',
            'per_page' => 15,
        ]);
        $report = $this->hosReportService->getTripReport($this->normalizedFilters($filters), $this->carrierId());
        $report['trips']->through(function (Trip $trip) {
            return [
                'id' => $trip->id,
                'trip_number' => $trip->trip_number,
                'driver_name' => $this->driverName($trip->driver),
                'vehicle_label' => $trip->vehicle?->company_unit_number,
                'origin' => $trip->origin_address,
                'destination' => $trip->destination_address,
                'status' => (string) $trip->status,
                'scheduled_start_date' => $this->formatDate($trip->scheduled_start_date),
                'violations_count' => (int) $trip->violations->count(),
                'has_violations' => (bool) $trip->has_violations,
            ];
        });

        return Inertia::render('carrier/reports/Trips', [
            'filters' => $filters,
            'trips' => $report['trips'],
            'stats' => $report['stats'],
            'drivers' => $report['drivers'],
            'statusOptions' => collect(Trip::STATUSES)
                ->map(fn (string $status) => ['value' => $status, 'label' => str($status)->replace('_', ' ')->title()->toString()])
                ->values(),
        ]);
    }

    public function tripsExportPdf(Request $request)
    {
        return $this->hosReportService->exportTripReportPdf(
            $this->normalizedFilters($this->inputFilters($request, ['driver_id', 'status', 'date_from', 'date_to'])),
            $this->carrierId()
        );
    }

    public function hos(Request $request): Response
    {
        $filters = $this->inputFilters($request, ['driver_id', 'has_violations', 'date_from', 'date_to', 'per_page'], [
            'driver_id' => '',
            'has_violations' => '',
            'date_from' => '',
            'date_to' => '',
            'per_page' => 15,
        ]);
        $report = $this->hosReportService->getHosReport($this->normalizedFilters($filters), $this->carrierId());
        $report['driverSummaries']->through(function ($summary) {
            return [
                'driver_id' => $summary->user_driver_detail_id,
                'driver_name' => $this->driverName($summary->driver),
                'driver_email' => $summary->driver?->user?->email,
                'total_days' => (int) $summary->total_days,
                'total_driving_minutes' => (int) $summary->total_driving_minutes,
                'avg_driving_minutes' => (float) $summary->avg_driving_minutes,
                'total_on_duty_minutes' => (int) $summary->total_on_duty_minutes,
                'total_off_duty_minutes' => (int) $summary->total_off_duty_minutes,
                'days_with_violations' => (int) $summary->days_with_violations,
                'first_log_date' => $this->formatDate($summary->first_log_date),
                'last_log_date' => $this->formatDate($summary->last_log_date),
            ];
        });

        return Inertia::render('carrier/reports/Hos', [
            'filters' => $filters,
            'driverSummaries' => $report['driverSummaries'],
            'stats' => $report['stats'],
            'drivers' => $report['drivers'],
            'dateRangeLabel' => $report['dateRangeLabel'],
        ]);
    }

    public function hosExportPdf(Request $request)
    {
        return $this->hosReportService->exportHosReportPdf(
            $this->normalizedFilters($this->inputFilters($request, ['driver_id', 'has_violations', 'date_from', 'date_to'])),
            $this->carrierId()
        );
    }

    public function violations(Request $request): Response
    {
        $filters = $this->inputFilters($request, ['driver_id', 'violation_type', 'severity', 'date_from', 'date_to', 'acknowledged', 'per_page'], [
            'driver_id' => '',
            'violation_type' => '',
            'severity' => '',
            'date_from' => '',
            'date_to' => '',
            'acknowledged' => '',
            'per_page' => 15,
        ]);
        $report = $this->hosReportService->getViolationsReport($this->normalizedFilters($filters), $this->carrierId());
        $report['violations']->through(function (HosViolation $violation) {
            return [
                'id' => $violation->id,
                'driver_name' => $this->driverName($violation->driver),
                'trip_number' => $violation->trip?->trip_number,
                'violation_type' => $violation->violation_type,
                'severity' => $violation->violation_severity,
                'violation_date' => $this->formatDate($violation->violation_date),
                'hours_exceeded' => $violation->hours_exceeded,
                'acknowledged' => (bool) $violation->acknowledged,
            ];
        });

        return Inertia::render('carrier/reports/Violations', [
            'filters' => $filters,
            'violations' => $report['violations'],
            'stats' => $report['stats'],
            'drivers' => $report['drivers'],
            'violationTypes' => $report['violationTypes'],
            'severities' => $report['severities'],
        ]);
    }

    public function violationsExportPdf(Request $request)
    {
        return $this->hosReportService->exportViolationsReportPdf(
            $this->normalizedFilters($this->inputFilters($request, ['driver_id', 'violation_type', 'severity', 'date_from', 'date_to', 'acknowledged'])),
            $this->carrierId()
        );
    }

    protected function carrierId(): int
    {
        return (int) Auth::user()->carrierDetails->carrier_id;
    }

    protected function carrier(): ?Carrier
    {
        return Auth::user()?->carrierDetails?->carrier;
    }

    protected function driverOptions(int $carrierId): array
    {
        return UserDriverDetail::query()
            ->with('user:id,name')
            ->where('carrier_id', $carrierId)
            ->orderBy('created_at')
            ->get()
            ->map(fn (UserDriverDetail $driver) => [
                'id' => $driver->id,
                'name' => $this->driverName($driver),
            ])
            ->values()
            ->all();
    }

    protected function vehicleOptions(int $carrierId): array
    {
        return Vehicle::query()
            ->where('carrier_id', $carrierId)
            ->orderBy('company_unit_number')
            ->get()
            ->map(fn (Vehicle $vehicle) => [
                'id' => $vehicle->id,
                'name' => trim(implode(' ', array_filter([$vehicle->company_unit_number, $vehicle->make, $vehicle->model]))),
            ])
            ->values()
            ->all();
    }

    protected function driverName($driver): string
    {
        if (!$driver) {
            return 'N/A';
        }

        $fullName = trim((string) ($driver->full_name ?? ''));
        if ($fullName !== '') {
            return $fullName;
        }

        return trim((string) ($driver->user?->name ?? 'N/A'));
    }

    protected function formatDate($value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('n/j/Y');
        } catch (\Throwable) {
            return null;
        }
    }

    protected function inputFilters(Request $request, array $keys, array $defaults = []): array
    {
        $filters = [];

        foreach ($keys as $key) {
            $filters[$key] = $request->input($key, $defaults[$key] ?? '');
        }

        return $filters;
    }

    protected function normalizedFilters(array $filters): array
    {
        foreach (['date_from', 'date_to'] as $key) {
            if (!empty($filters[$key])) {
                $filters[$key] = $this->normalizeDate($filters[$key]);
            }
        }

        return $filters;
    }

    protected function normalizeDate(string $value): string
    {
        $value = trim($value);

        foreach (['n/j/Y', 'm/d/Y', 'Y-m-d'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->format('Y-m-d');
            } catch (\Throwable) {
            }
        }

        return Carbon::parse($value)->format('Y-m-d');
    }
}
