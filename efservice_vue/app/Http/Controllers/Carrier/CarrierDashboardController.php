<?php

namespace App\Http\Controllers\Carrier;

use App\Http\Controllers\Controller;
use App\Models\CarrierDocument;
use App\Models\Admin\Driver\DriverLicense;
use App\Models\Admin\Driver\DriverMedicalQualification;
use App\Models\Admin\Vehicle\VehicleMaintenance;
use Carbon\Carbon;
use Inertia\Inertia;

class CarrierDashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $carrierDetail = $user->carrierDetails;

        if (!$carrierDetail || !$carrierDetail->carrier) {
            abort(403, 'No carrier associated with this account.');
        }

        $carrier = $carrierDetail->carrier;
        $carrier->load('membership');

        $now = Carbon::now();
        $expiringThreshold = $now->copy()->addDays(30);
        $currentMonthStart = $now->copy()->startOfMonth();
        $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();

        // --- Counts principales ---
        $driversCount = $carrier->userDrivers()->count();
        $vehiclesCount = $carrier->vehicles()->count();

        // --- Documentos ---
        $docTotal    = $carrier->documents()->count();
        $docPending  = $carrier->documents()->where('status', CarrierDocument::STATUS_PENDING)->count();
        $docApproved = $carrier->documents()->where('status', CarrierDocument::STATUS_APPROVED)->count();
        $docRejected = $carrier->documents()->where('status', CarrierDocument::STATUS_REJECTED)->count();
        $docInProcess = $carrier->documents()->where('status', CarrierDocument::STATUS_IN_PROCESS)->count();

        $documentStats = [
            'total'      => $docTotal,
            'pending'    => $docPending,
            'approved'   => $docApproved,
            'rejected'   => $docRejected,
            'in_process' => $docInProcess,
        ];

        // Documentos por tipo
        $documentTypeCounts = $carrier->documents()
            ->join('document_types', 'carrier_documents.document_type_id', '=', 'document_types.id')
            ->selectRaw('document_types.name, count(*) as count')
            ->groupBy('document_types.name')
            ->get()
            ->map(fn($item) => ['name' => $item->name, 'count' => $item->count]);

        // --- Licencias ---
        $licenseStats = DriverLicense::query()
            ->join('user_driver_details', 'driver_licenses.user_driver_detail_id', '=', 'user_driver_details.id')
            ->where('user_driver_details.carrier_id', $carrier->id)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN expiration_date < ? THEN 1 ELSE 0 END) as expired,
                SUM(CASE WHEN expiration_date >= ? AND expiration_date <= ? THEN 1 ELSE 0 END) as expiring_soon
            ', [$now, $now, $expiringThreshold])
            ->first();

        $licTotal      = $licenseStats->total ?? 0;
        $licExpired    = $licenseStats->expired ?? 0;
        $licExpiring   = $licenseStats->expiring_soon ?? 0;
        $licenseData = [
            'total'         => $licTotal,
            'valid'         => $licTotal - $licExpired - $licExpiring,
            'expiring_soon' => $licExpiring,
            'expired'       => $licExpired,
        ];

        // --- Medical Records ---
        $medStats = DriverMedicalQualification::query()
            ->join('user_driver_details', 'driver_medical_qualifications.user_driver_detail_id', '=', 'user_driver_details.id')
            ->where('user_driver_details.carrier_id', $carrier->id)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN medical_card_expiration_date < ? THEN 1 ELSE 0 END) as expired,
                SUM(CASE WHEN medical_card_expiration_date >= ? AND medical_card_expiration_date <= ? THEN 1 ELSE 0 END) as expiring_soon,
                SUM(CASE WHEN medical_card_expiration_date > ? THEN 1 ELSE 0 END) as active
            ', [$now, $now, $expiringThreshold, $expiringThreshold])
            ->first();

        $medicalData = [
            'total'         => $medStats->total ?? 0,
            'active'        => $medStats->active ?? 0,
            'expiring_soon' => $medStats->expiring_soon ?? 0,
            'expired'       => $medStats->expired ?? 0,
        ];

        // --- Mantenimiento de vehículos ---
        $maintStats = VehicleMaintenance::query()
            ->join('vehicles', 'vehicle_maintenances.vehicle_id', '=', 'vehicles.id')
            ->where('vehicles.carrier_id', $carrier->id)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN vehicle_maintenances.status = 0 AND next_service_date < ? THEN 1 ELSE 0 END) as overdue,
                SUM(CASE WHEN vehicle_maintenances.status = 0 AND next_service_date >= ? AND next_service_date <= ? THEN 1 ELSE 0 END) as expiring_soon,
                SUM(CASE WHEN vehicle_maintenances.status = 1 THEN 1 ELSE 0 END) as completed
            ', [$now, $now, $expiringThreshold])
            ->first();

        $maintenanceData = [
            'total'         => $maintStats->total ?? 0,
            'overdue'       => $maintStats->overdue ?? 0,
            'expiring_soon' => $maintStats->expiring_soon ?? 0,
            'completed'     => $maintStats->completed ?? 0,
        ];

        // --- Métricas avanzadas ---
        $docMetrics = $carrier->documents()
            ->selectRaw('
                SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as this_month,
                SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as last_month,
                AVG(CASE WHEN status = ? AND updated_at IS NOT NULL
                    THEN DATEDIFF(updated_at, created_at) ELSE NULL END) as avg_days
            ', [
                $currentMonthStart,
                $lastMonthStart, $lastMonthEnd,
                CarrierDocument::STATUS_APPROVED,
            ])
            ->first();

        $activeDrivers = $carrier->userDrivers()->where('status', 1)->count();
        $docsThisMonth = $docMetrics->this_month ?? 0;
        $docsLastMonth = $docMetrics->last_month ?? 0;

        $advancedMetrics = [
            'documentsThisMonth' => $docsThisMonth,
            'documentsGrowth'    => $docsLastMonth > 0
                ? round((($docsThisMonth - $docsLastMonth) / $docsLastMonth) * 100, 1)
                : 0,
            'avgApprovalDays'    => $docMetrics->avg_days !== null ? round($docMetrics->avg_days, 1) : 0,
            'activeDrivers'      => $activeDrivers,
            'inactiveDrivers'    => $driversCount - $activeDrivers,
            'completionRate'     => $docTotal > 0 ? round(($docApproved / $docTotal) * 100, 1) : 0,
            'pendingRate'        => $docTotal > 0 ? round(($docPending / $docTotal) * 100, 1) : 0,
        ];

        // --- Membership limits ---
        $membership = $carrier->membership;
        $membershipLimits = [
            'maxDrivers'         => $membership?->max_drivers ?? 0,
            'maxVehicles'        => $membership?->max_vehicles ?? 0,
            'driversPercentage'  => ($membership?->max_drivers ?? 0) > 0
                ? round(($driversCount / $membership->max_drivers) * 100)
                : 0,
            'vehiclesPercentage' => ($membership?->max_vehicles ?? 0) > 0
                ? round(($vehiclesCount / $membership->max_vehicles) * 100)
                : 0,
        ];

        // --- Trending últimos 6 meses ---
        $monthsCases = [];
        $monthsLabels = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);
            $start = $month->copy()->startOfMonth()->toDateTimeString();
            $end   = $month->copy()->endOfMonth()->toDateTimeString();
            $key   = "month_{$i}";
            $monthsCases[] = "SUM(CASE WHEN created_at BETWEEN '{$start}' AND '{$end}' THEN 1 ELSE 0 END) as {$key}";
            $monthsLabels[$key] = $month->format('M Y');
        }

        $docCounts    = $carrier->documents()->selectRaw(implode(', ', $monthsCases))->first();
        $driverCounts = $carrier->userDrivers()->selectRaw(implode(', ', $monthsCases))->first();

        $trendsData = [];
        foreach ($monthsLabels as $key => $label) {
            $trendsData[] = [
                'month'   => $label,
                'documents' => $docCounts?->$key ?? 0,
                'drivers'   => $driverCounts?->$key ?? 0,
            ];
        }

        // --- Conductores recientes ---
        $recentDrivers = $carrier->userDrivers()
            ->with('user:id,name,email')
            ->orderByDesc('created_at')
            ->take(5)
            ->get()
            ->map(fn($d) => [
                'id'         => $d->id,
                'name'       => trim(($d->user->name ?? '') . ' ' . ($d->last_name ?? '')),
                'email'      => $d->user->email ?? '',
                'status'     => $d->status,
                'created_at' => $d->created_at?->format('M d, Y'),
            ]);

        // --- Documentos recientes ---
        $recentDocuments = $carrier->documents()
            ->with('documentType:id,name')
            ->orderByDesc('created_at')
            ->take(5)
            ->get()
            ->map(fn($doc) => [
                'id'          => $doc->id,
                'name'        => $doc->documentType?->name ?? 'Document',
                'status'      => $doc->status,
                'status_name' => $doc->status_name,
                'created_at'  => $doc->created_at?->format('M d, Y'),
            ]);

        // --- Alertas ---
        $alerts = [];

        if ($carrier->documents_completed && $docPending > 0) {
            $alerts[] = ['type' => 'warning', 'icon' => 'AlertTriangle', 'title' => 'Pending Documents',
                'message' => "You have {$docPending} pending documents to review."];
        }
        if ($docRejected > 0) {
            $alerts[] = ['type' => 'danger', 'icon' => 'XCircle', 'title' => 'Rejected Documents',
                'message' => "You have {$docRejected} rejected documents that require attention."];
        }
        if ($membership && $driversCount >= $membership->max_drivers * 0.9) {
            $alerts[] = ['type' => 'info', 'icon' => 'Users', 'title' => 'Driver Limit Warning',
                'message' => "Approaching driver limit ({$driversCount}/{$membership->max_drivers})."];
        }
        if ($licenseData['expired'] > 0) {
            $alerts[] = ['type' => 'danger', 'icon' => 'XCircle', 'title' => 'Expired Licenses',
                'message' => "You have {$licenseData['expired']} expired driver licenses."];
        }
        if ($licenseData['expiring_soon'] > 0) {
            $alerts[] = ['type' => 'warning', 'icon' => 'Clock', 'title' => 'Licenses Expiring Soon',
                'message' => "{$licenseData['expiring_soon']} driver licenses expire within 30 days."];
        }
        if ($medicalData['expired'] > 0) {
            $alerts[] = ['type' => 'danger', 'icon' => 'AlertCircle', 'title' => 'Expired Medical Records',
                'message' => "You have {$medicalData['expired']} expired medical records."];
        }
        if ($medicalData['expiring_soon'] > 0) {
            $alerts[] = ['type' => 'warning', 'icon' => 'Clock', 'title' => 'Medical Records Expiring Soon',
                'message' => "{$medicalData['expiring_soon']} medical records expire within 30 days."];
        }
        if ($maintenanceData['overdue'] > 0) {
            $alerts[] = ['type' => 'danger', 'icon' => 'AlertCircle', 'title' => 'Overdue Maintenance',
                'message' => "You have {$maintenanceData['overdue']} overdue vehicle maintenance tasks."];
        }
        if ($maintenanceData['expiring_soon'] > 0) {
            $alerts[] = ['type' => 'warning', 'icon' => 'Clock', 'title' => 'Maintenance Due Soon',
                'message' => "{$maintenanceData['expiring_soon']} vehicle maintenance tasks due within 30 days."];
        }

        return Inertia::render('carrier/Dashboard', [
            'carrier' => [
                'id'                  => $carrier->id,
                'name'                => $carrier->name,
                'dot_number'          => $carrier->dot_number,
                'mc_number'           => $carrier->mc_number,
                'status'              => $carrier->status,
                'documents_completed' => $carrier->documents_completed,
                'documents_status'    => $docTotal === 0
                    ? 'none'
                    : ($carrier->documents_completed ? 'complete' : ($docRejected > 0 ? 'rejected' : 'pending')),
                'safety_url'          => $carrier->getSafetyDataSystemUrlAttribute(),
            ],
            'stats' => [
                'drivers'  => $driversCount,
                'vehicles' => $vehiclesCount,
                'documents' => $documentStats,
            ],
            'membershipLimits'   => $membershipLimits,
            'licenseStats'       => $licenseData,
            'medicalStats'       => $medicalData,
            'maintenanceStats'   => $maintenanceData,
            'advancedMetrics'    => $advancedMetrics,
            'documentTypeCounts' => $documentTypeCounts,
            'recentDrivers'      => $recentDrivers,
            'recentDocuments'    => $recentDocuments,
            'trendsData'         => $trendsData,
            'alerts'             => $alerts,
        ]);
    }
}
