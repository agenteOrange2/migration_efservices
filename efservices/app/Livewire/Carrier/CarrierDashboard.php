<?php

namespace App\Livewire\Carrier;

use Livewire\Component;
use App\Models\Carrier;
use App\Models\CarrierDocument;
use App\Models\UserDriverDetail;
use App\Models\Admin\Driver\DriverLicense;
use App\Models\Admin\Driver\DriverMedicalQualification;
use App\Models\Admin\Vehicle\VehicleMaintenance;
use Carbon\Carbon;

class CarrierDashboard extends Component
{
    public Carrier $carrier;
    public $documentStats;
    public $driversCount;
    public $vehiclesCount;
    public $recentDrivers;
    public $recentDocuments;
    public $documentTypeCounts;
    public $documentStatusCounts;
    public $licenseStats;
    public $medicalRecordsStats;
    public $maintenanceStats;
    
    // Nuevas propiedades para métricas avanzadas
    public $advancedMetrics;
    public $alertsData;
    public $trendsData;
    public $chartData;
    public $quickActions;

    public function mount(Carrier $carrier = null)
    {
        // Si no se proporciona un carrier, obtenemos el carrier del usuario autenticado
        if (!$carrier) {
            $this->carrier = \Illuminate\Support\Facades\Auth::user()->carrierDetails->carrier;
        } else {
            $this->carrier = $carrier;
        }

        // Eager load membership relationship to avoid N+1 queries
        $this->carrier->load('membership');

        $this->loadData();
    }

    protected function loadData()
    {
        // Cargar counts de drivers y vehicles
        $this->carrier->loadCount(['userDrivers', 'vehicles', 'documents']);

        // Asignar counts desde el modelo cargado
        $this->driversCount = $this->carrier->user_drivers_count ?? 0;
        $this->vehiclesCount = $this->carrier->vehicles_count ?? 0;

        // Estadísticas de documentos - consultas directas para evitar problemas con loadCount
        $this->documentStats = [
            'total' => $this->carrier->documents()->count(),
            'pending' => $this->carrier->documents()->where('status', CarrierDocument::STATUS_PENDING)->count(),
            'approved' => $this->carrier->documents()->where('status', CarrierDocument::STATUS_APPROVED)->count(),
            'rejected' => $this->carrier->documents()->where('status', CarrierDocument::STATUS_REJECTED)->count(),
        ];

        // Conteo de documentos por estado
        $this->documentStatusCounts = [
            'Pending' => $this->documentStats['pending'],
            'Approved' => $this->documentStats['approved'],
            'Rejected' => $this->documentStats['rejected'],
            'In Progress' => $this->carrier->documents()->where('status', CarrierDocument::STATUS_IN_PROCESS)->count(),
        ];

        // Conteo de documentos por tipo
        $this->documentTypeCounts = $this->carrier->documents()
            ->join('document_types', 'carrier_documents.document_type_id', '=', 'document_types.id')
            ->selectRaw('document_types.name, count(*) as count')
            ->groupBy('document_types.name')
            ->get()
            ->pluck('count', 'name')
            ->toArray();

        // Obtener conductores recientes con eager loading
        $this->recentDrivers = $this->carrier->userDrivers()
            ->with('user:id,name,email')  // Solo cargar campos necesarios
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Obtener documentos recientes con eager loading
        $this->recentDocuments = $this->carrier->documents()
            ->with('documentType:id,name')  // Solo cargar campos necesarios
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Cargar estadísticas optimizadas
        $this->loadLicenseStats();
        $this->loadMedicalRecordsStats();
        $this->loadMaintenanceStats();

        // Cargar métricas avanzadas
        $this->loadAdvancedMetrics();
        $this->loadAlertsData();
        $this->loadTrendsData();
        $this->loadChartData();
        $this->loadQuickActions();
    }

    // En Livewire 3, podemos usar directamente una propiedad computada con el prefijo "get"
    public function getMembershipLimitsProperty()
    {
        $membership = $this->carrier->membership;
        return [
            'maxDrivers' => $membership ? $membership->max_drivers : 0,
            'maxVehicles' => $membership ? $membership->max_vehicles : 0,
            'driversPercentage' => $membership && $membership->max_drivers > 0 
                ? round(($this->driversCount / $membership->max_drivers) * 100) 
                : 0,
            'vehiclesPercentage' => $membership && $membership->max_vehicles > 0 
                ? round(($this->vehiclesCount / $membership->max_vehicles) * 100) 
                : 0,
        ];
    }

    protected function loadAdvancedMetrics()
    {
        $now = Carbon::now();
        $currentMonthStart = $now->copy()->startOfMonth();
        $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();

        // OPTIMIZACIÓN: Cargar todas las métricas de documentos en una sola query
        $driver = config('database.default');
        $connection = config("database.connections.{$driver}.driver");

        if ($connection === 'sqlite') {
            $documentMetrics = $this->carrier->documents()
                ->selectRaw('
                    SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as this_month,
                    SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as last_month,
                    SUM(CASE WHEN status = ? AND created_at <= ? THEN 1 ELSE 0 END) as expiring,
                    AVG(CASE WHEN status = ? AND updated_at IS NOT NULL
                        THEN julianday(updated_at) - julianday(created_at)
                        ELSE NULL END) as avg_days
                ', [
                    $currentMonthStart,
                    $lastMonthStart, $lastMonthEnd,
                    CarrierDocument::STATUS_APPROVED, $now->copy()->subMonths(11),
                    CarrierDocument::STATUS_APPROVED
                ])
                ->first();
        } else {
            $documentMetrics = $this->carrier->documents()
                ->selectRaw('
                    SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as this_month,
                    SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as last_month,
                    SUM(CASE WHEN status = ? AND created_at <= ? THEN 1 ELSE 0 END) as expiring,
                    AVG(CASE WHEN status = ? AND updated_at IS NOT NULL
                        THEN DATEDIFF(updated_at, created_at)
                        ELSE NULL END) as avg_days
                ', [
                    $currentMonthStart,
                    $lastMonthStart, $lastMonthEnd,
                    CarrierDocument::STATUS_APPROVED, $now->copy()->subMonths(11),
                    CarrierDocument::STATUS_APPROVED
                ])
                ->first();
        }

        // Conductores activos ya fueron cargados con loadCount
        $activeDrivers = $this->carrier->active_drivers_count ?? 0;
        $inactiveDrivers = $this->driversCount - $activeDrivers;

        $documentsThisMonth = $documentMetrics->this_month ?? 0;
        $documentsLastMonth = $documentMetrics->last_month ?? 0;

        $this->advancedMetrics = [
            'documentsThisMonth' => $documentsThisMonth,
            'documentsGrowth' => $documentsLastMonth > 0
                ? round((($documentsThisMonth - $documentsLastMonth) / $documentsLastMonth) * 100, 1)
                : 0,
            'avgApprovalDays' => $documentMetrics->avg_days !== null
                ? round($documentMetrics->avg_days, 1)
                : 0,
            'activeDrivers' => $activeDrivers,
            'inactiveDrivers' => $inactiveDrivers,
            'expiringDocuments' => $documentMetrics->expiring ?? 0,
            'completionRate' => $this->documentStats['total'] > 0
                ? round(($this->documentStats['approved'] / $this->documentStats['total']) * 100, 1)
                : 0,
            'pendingRate' => $this->documentStats['total'] > 0
                ? round(($this->documentStats['pending'] / $this->documentStats['total']) * 100, 1)
                : 0,
        ];
    }

    protected function loadAlertsData()
    {
        $alerts = [];

        // Alerta de documentos pendientes
        // Solo mostrar si documents_completed es true para evitar duplicar con la notificación principal
        if ($this->carrier->documents_completed && $this->documentStats['pending'] > 0) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'AlertTriangle',
                'title' => 'Pending Documents',
                'message' => "You have {$this->documentStats['pending']} pending documents to review.",
                'action' => 'View Documents',
                'url' => route('carrier.documents.index', $this->carrier)
            ];
        }

        // Alerta de límite de conductores
        $membership = $this->carrier->membership;
        if ($membership && $this->driversCount >= $membership->max_drivers * 0.9) {
            $alerts[] = [
                'type' => 'info',
                'icon' => 'Users',
                'title' => 'Driver Limit',
                'message' => "You are close to the driver limit ({$this->driversCount}/{$membership->max_drivers}).",
                'action' => 'Update Plan',
                'url' => '#'
            ];
        }

        // Alerta de documentos rechazados
        if ($this->documentStats['rejected'] > 0) {
            $alerts[] = [
                'type' => 'danger',
                'icon' => 'XCircle',
                'title' => 'Rejected Documents',
                'message' => "You have {$this->documentStats['rejected']} rejected documents that require attention.",
                'action' => 'Review',
                'url' => route('carrier.documents.index', $this->carrier)
            ];
        }

        // Alerta de documentos próximos a vencer
        if ($this->advancedMetrics['expiringDocuments'] > 0) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'Clock',
                'title' => 'Expiring Documents',
                'message' => "You have {$this->advancedMetrics['expiringDocuments']} documents that may need renewal.",
                'action' => 'Review',
                'url' => route('carrier.documents.index', $this->carrier)
            ];
        }

        // Alerta de licencias expiradas
        if ($this->licenseStats['expired'] > 0) {
            $alerts[] = [
                'type' => 'danger',
                'icon' => 'XCircle',
                'title' => 'Expired Licenses',
                'message' => "You have {$this->licenseStats['expired']} expired driver licenses that require immediate attention.",
                'action' => 'View Licenses',
                'url' => route('carrier.licenses.index')
            ];
        }

        // Alerta de licencias por vencer
        if ($this->licenseStats['expiring_soon'] > 0) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'Clock',
                'title' => 'Licenses Expiring Soon',
                'message' => "You have {$this->licenseStats['expiring_soon']} driver licenses expiring within 30 days.",
                'action' => 'View Licenses',
                'url' => route('carrier.licenses.index')
            ];
        }

        // Alerta de medical records vencidos
        if ($this->medicalRecordsStats['expired'] > 0) {
            $alerts[] = [
                'type' => 'danger',
                'icon' => 'AlertCircle',
                'title' => 'Expired Medical Records',
                'message' => "You have {$this->medicalRecordsStats['expired']} expired medical records that require immediate attention.",
                'action' => 'View Medical Records',
                'url' => route('carrier.medical-records.index', ['tab' => 'expired'])
            ];
        }

        // Alerta de medical records por vencer
        if ($this->medicalRecordsStats['expiring_soon'] > 0) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'Clock',
                'title' => 'Medical Records Expiring Soon',
                'message' => "You have {$this->medicalRecordsStats['expiring_soon']} medical records expiring within 30 days.",
                'action' => 'View Medical Records',
                'url' => route('carrier.medical-records.index', ['tab' => 'expiring'])
            ];
        }

        // Alerta de mantenimientos vencidos
        if ($this->maintenanceStats['overdue'] > 0) {
            $alerts[] = [
                'type' => 'danger',
                'icon' => 'AlertCircle',
                'title' => 'Overdue Vehicle Maintenance',
                'message' => "You have {$this->maintenanceStats['overdue']} overdue vehicle maintenance tasks that require immediate attention.",
                'action' => 'View Maintenance',
                'url' => route('carrier.maintenance.index')
            ];
        }

        // Alerta de mantenimientos por vencer
        if ($this->maintenanceStats['expiring_soon'] > 0) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'Clock',
                'title' => 'Vehicle Maintenance Due Soon',
                'message' => "You have {$this->maintenanceStats['expiring_soon']} vehicle maintenance tasks due within 30 days.",
                'action' => 'View Maintenance',
                'url' => route('carrier.maintenance.index')
            ];
        }

        $this->alertsData = $alerts;
    }

    protected function loadTrendsData()
    {
        $now = Carbon::now();

        // OPTIMIZACIÓN: Construir casos CASE WHEN para cada mes en una sola query
        $monthsCases = [];
        $monthsLabels = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();
            $monthKey = "month_{$i}";

            $monthsCases[] = "SUM(CASE WHEN created_at BETWEEN '{$monthStart}' AND '{$monthEnd}' THEN 1 ELSE 0 END) as {$monthKey}";
            $monthsLabels[$monthKey] = $month->format('M Y');
        }

        // Obtener todos los counts de documentos en una query
        $documentsCounts = $this->carrier->documents()
            ->selectRaw(implode(', ', $monthsCases))
            ->first();

        // Obtener todos los counts de drivers en una query
        $driversCounts = $this->carrier->userDrivers()
            ->selectRaw(implode(', ', $monthsCases))
            ->first();

        // Construir el array de resultados
        $last6Months = [];
        foreach ($monthsLabels as $key => $label) {
            $last6Months[] = [
                'month' => $label,
                'documents' => $documentsCounts->{$key} ?? 0,
                'drivers' => $driversCounts->{$key} ?? 0,
            ];
        }

        $this->trendsData = $last6Months;
    }

    protected function loadChartData()
    {
        // Datos para gráfico de dona de estados de documentos
        $this->chartData = [
            'documentStatus' => [
                'labels' => ['Approved', 'Pending', 'Rejected', 'In Progress'],
                'data' => [
                    $this->documentStats['approved'],
                    $this->documentStats['pending'],
                    $this->documentStats['rejected'],
                    $this->documentStatusCounts['In Progress']
                ],
                'colors' => ['#10B981', '#F59E0B', '#EF4444', '#3B82F6']
            ],
            'driversStatus' => [
                'labels' => ['Active', 'Inactive'],
                'data' => [
                    $this->advancedMetrics['activeDrivers'],
                    $this->advancedMetrics['inactiveDrivers']
                ],
                'colors' => ['#10B981', '#6B7280']
            ]
        ];
    }

    protected function loadLicenseStats()
    {
        $now = Carbon::now();
        $expiringThreshold = $now->copy()->addDays(30);

        // OPTIMIZACIÓN: Obtener todas las estadísticas en una sola query con JOIN y CASE WHEN
        $licenseStats = DriverLicense::query()
            ->join('user_driver_details', 'driver_licenses.user_driver_detail_id', '=', 'user_driver_details.id')
            ->where('user_driver_details.carrier_id', $this->carrier->id)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN expiration_date < ? THEN 1 ELSE 0 END) as expired,
                SUM(CASE WHEN expiration_date >= ? AND expiration_date <= ? THEN 1 ELSE 0 END) as expiring_soon
            ', [$now, $now, $expiringThreshold])
            ->first();

        $total = $licenseStats->total ?? 0;
        $expired = $licenseStats->expired ?? 0;
        $expiringSoon = $licenseStats->expiring_soon ?? 0;

        $this->licenseStats = [
            'total' => $total,
            'expired' => $expired,
            'expiring_soon' => $expiringSoon,
            'valid' => $total - $expired - $expiringSoon,
        ];
    }

    protected function loadMedicalRecordsStats()
    {
        $now = Carbon::now();
        $expiringThreshold = $now->copy()->addDays(30);

        // OPTIMIZACIÓN: Obtener todas las estadísticas en una sola query con JOIN
        $medicalStats = DriverMedicalQualification::query()
            ->join('user_driver_details', 'driver_medical_qualifications.user_driver_detail_id', '=', 'user_driver_details.id')
            ->where('user_driver_details.carrier_id', $this->carrier->id)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN medical_card_expiration_date < ? THEN 1 ELSE 0 END) as expired,
                SUM(CASE WHEN medical_card_expiration_date >= ? AND medical_card_expiration_date <= ? THEN 1 ELSE 0 END) as expiring_soon,
                SUM(CASE WHEN medical_card_expiration_date > ? THEN 1 ELSE 0 END) as active
            ', [$now, $now, $expiringThreshold, $expiringThreshold])
            ->first();

        $this->medicalRecordsStats = [
            'total' => $medicalStats->total ?? 0,
            'active' => $medicalStats->active ?? 0,
            'expiring_soon' => $medicalStats->expiring_soon ?? 0,
            'expired' => $medicalStats->expired ?? 0,
        ];
    }

    protected function loadMaintenanceStats()
    {
        $now = Carbon::now();
        $expiringThreshold = $now->copy()->addDays(30);

        // OPTIMIZACIÓN: Obtener todas las estadísticas en una sola query con JOIN
        $maintenanceStats = VehicleMaintenance::query()
            ->join('vehicles', 'vehicle_maintenances.vehicle_id', '=', 'vehicles.id')
            ->where('vehicles.carrier_id', $this->carrier->id)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN vehicle_maintenances.status = ? THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN vehicle_maintenances.status = ? AND next_service_date < ? THEN 1 ELSE 0 END) as overdue,
                SUM(CASE WHEN vehicle_maintenances.status = ? AND next_service_date >= ? AND next_service_date <= ? THEN 1 ELSE 0 END) as expiring_soon,
                SUM(CASE WHEN vehicle_maintenances.status = ? THEN 1 ELSE 0 END) as completed
            ', [false, false, $now, false, $now, $expiringThreshold, true])
            ->first();

        $this->maintenanceStats = [
            'total' => $maintenanceStats->total ?? 0,
            'pending' => $maintenanceStats->pending ?? 0,
            'overdue' => $maintenanceStats->overdue ?? 0,
            'expiring_soon' => $maintenanceStats->expiring_soon ?? 0,
            'completed' => $maintenanceStats->completed ?? 0,
        ];
    }

    protected function loadQuickActions()
    {
        $this->quickActions = [
            [
                'title' => 'Agregar Conductor',
                'icon' => 'UserPlus',
                'url' => route('carrier.driver-management.create'),
                'color' => 'primary'
            ],
            [
                'title' => 'Subir Documento',
                'icon' => 'Upload',
                'url' => route('carrier.documents.index', $this->carrier->slug),
                'color' => 'success'
            ],
            [
                'title' => 'Vehicle Maintenance',
                'icon' => 'Wrench',
                'url' => route('carrier.maintenance.index'),
                'color' => 'warning'
            ],
            [
                'title' => 'Medical Records',
                'icon' => 'FileText',
                'url' => route('carrier.medical-records.index'),
                'color' => 'info'
            ],
            [
                'title' => 'Configuración',
                'icon' => 'Settings',
                'url' => route('carrier.profile.edit'),
                'color' => 'secondary'
            ]
        ];
    }

    public function refreshData()
    {
        $this->loadData();
        $this->dispatch('dataRefreshed');
    }

    public function render()
    {
        return view('livewire.carrier.carrier-dashboard');
    }
}
