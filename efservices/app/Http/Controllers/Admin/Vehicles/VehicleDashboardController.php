<?php

namespace App\Http\Controllers\Admin\Vehicles;

use App\Http\Controllers\Controller;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Admin\Vehicle\VehicleMaintenance;
use App\Models\Admin\Vehicle\VehicleDocument;
use App\Models\Admin\Vehicle\VehicleType;
use App\Models\Admin\Vehicle\VehicleDriverAssignment;
use App\Models\EmergencyRepair;
use App\Models\Carrier;
use Illuminate\Http\Request;
use Carbon\Carbon;

class VehicleDashboardController extends Controller
{
    public function index(Request $request)
    {
        // Date range filter
        $startDate = $request->filled('start_date') 
            ? Carbon::parse($request->start_date)->startOfDay() 
            : Carbon::now()->startOfMonth();
        $endDate = $request->filled('end_date') 
            ? Carbon::parse($request->end_date)->endOfDay() 
            : Carbon::now()->endOfDay();

        // Carrier filter
        $carrierId = $request->carrier_id;

        // Base query for vehicles
        $vehicleQuery = Vehicle::query();
        if ($carrierId) {
            $vehicleQuery->where('carrier_id', $carrierId);
        }

        // Vehicle Statistics
        $stats = [
            'total_vehicles' => (clone $vehicleQuery)->count(),
            'active_vehicles' => (clone $vehicleQuery)->active()->count(),
            'out_of_service' => (clone $vehicleQuery)->outOfService()->count(),
            'suspended' => (clone $vehicleQuery)->suspended()->count(),
        ];

        // Vehicles by Type
        $vehiclesByType = Vehicle::query()
            ->when($carrierId, fn($q) => $q->where('carrier_id', $carrierId))
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->orderByDesc('count')
            ->get();

        // Vehicles by Driver Type
        $vehiclesByDriverType = Vehicle::query()
            ->when($carrierId, fn($q) => $q->where('carrier_id', $carrierId))
            ->selectRaw('driver_type, COUNT(*) as count')
            ->whereNotNull('driver_type')
            ->where('driver_type', '!=', '')
            ->groupBy('driver_type')
            ->get();

        // Maintenance Statistics
        $maintenanceQuery = VehicleMaintenance::query()
            ->when($carrierId, function($q) use ($carrierId) {
                $q->whereHas('vehicle', fn($vq) => $vq->where('carrier_id', $carrierId));
            });

        $maintenanceStats = [
            'total' => (clone $maintenanceQuery)->whereBetween('created_at', [$startDate, $endDate])->count(),
            'completed' => (clone $maintenanceQuery)->where('status', 'completed')
                ->whereBetween('created_at', [$startDate, $endDate])->count(),
            'pending' => (clone $maintenanceQuery)->where('status', 'pending')
                ->whereBetween('created_at', [$startDate, $endDate])->count(),
            'in_progress' => (clone $maintenanceQuery)->where('status', 'in_progress')
                ->whereBetween('created_at', [$startDate, $endDate])->count(),
            'overdue' => (clone $maintenanceQuery)->where('status', 'overdue')
                ->whereBetween('created_at', [$startDate, $endDate])->count(),
        ];

        // Emergency Repairs Statistics
        $emergencyQuery = EmergencyRepair::query()
            ->when($carrierId, function($q) use ($carrierId) {
                $q->whereHas('vehicle', fn($vq) => $vq->where('carrier_id', $carrierId));
            });

        $emergencyStats = [
            'total' => (clone $emergencyQuery)->whereBetween('created_at', [$startDate, $endDate])->count(),
            'completed' => (clone $emergencyQuery)->where('status', 'completed')
                ->whereBetween('created_at', [$startDate, $endDate])->count(),
            'pending' => (clone $emergencyQuery)->whereIn('status', ['pending', 'in_progress'])
                ->whereBetween('created_at', [$startDate, $endDate])->count(),
            'total_cost' => (clone $emergencyQuery)->whereBetween('created_at', [$startDate, $endDate])
                ->sum('cost'),
        ];

        // Documents Statistics
        $documentQuery = VehicleDocument::query()
            ->when($carrierId, function($q) use ($carrierId) {
                $q->whereHas('vehicle', fn($vq) => $vq->where('carrier_id', $carrierId));
            });

        $documentStats = [
            'total' => (clone $documentQuery)->count(),
            'expiring_soon' => (clone $documentQuery)
                ->whereNotNull('expiration_date')
                ->where('expiration_date', '<=', Carbon::now()->addDays(30))
                ->where('expiration_date', '>=', Carbon::now())
                ->count(),
            'expired' => (clone $documentQuery)
                ->whereNotNull('expiration_date')
                ->where('expiration_date', '<', Carbon::now())
                ->count(),
        ];

        // Vehicles with expiring registration
        $expiringRegistrations = Vehicle::query()
            ->when($carrierId, fn($q) => $q->where('carrier_id', $carrierId))
            ->whereNotNull('registration_expiration_date')
            ->where('registration_expiration_date', '<=', Carbon::now()->addDays(30))
            ->where('registration_expiration_date', '>=', Carbon::now())
            ->count();

        // Vehicles with expiring annual inspection
        $expiringInspections = Vehicle::query()
            ->when($carrierId, fn($q) => $q->where('carrier_id', $carrierId))
            ->whereNotNull('annual_inspection_expiration_date')
            ->where('annual_inspection_expiration_date', '<=', Carbon::now()->addDays(30))
            ->where('annual_inspection_expiration_date', '>=', Carbon::now())
            ->count();

        // Recent Maintenance (last 5)
        $recentMaintenance = VehicleMaintenance::with(['vehicle'])
            ->when($carrierId, function($q) use ($carrierId) {
                $q->whereHas('vehicle', fn($vq) => $vq->where('carrier_id', $carrierId));
            })
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // Recent Emergency Repairs (last 5)
        $recentEmergencyRepairs = EmergencyRepair::with(['vehicle.carrier'])
            ->when($carrierId, function($q) use ($carrierId) {
                $q->whereHas('vehicle', fn($vq) => $vq->where('carrier_id', $carrierId));
            })
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // Monthly Maintenance Trend (last 6 months)
        $maintenanceTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $count = VehicleMaintenance::query()
                ->when($carrierId, function($q) use ($carrierId) {
                    $q->whereHas('vehicle', fn($vq) => $vq->where('carrier_id', $carrierId));
                })
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
            $maintenanceTrend[] = [
                'month' => $month->format('M Y'),
                'count' => $count,
            ];
        }

        // Carriers for filter
        $carriers = Carrier::where('status', 1)->orderBy('name')->get();

        // Vehicle Types for stats
        $vehicleTypes = VehicleType::orderBy('name')->get();

        return view('admin.vehicles.dashboard', compact(
            'stats',
            'vehiclesByType',
            'vehiclesByDriverType',
            'maintenanceStats',
            'emergencyStats',
            'documentStats',
            'expiringRegistrations',
            'expiringInspections',
            'recentMaintenance',
            'recentEmergencyRepairs',
            'maintenanceTrend',
            'carriers',
            'vehicleTypes',
            'startDate',
            'endDate',
            'carrierId'
        ));
    }
}
