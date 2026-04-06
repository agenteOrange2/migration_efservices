<?php

namespace App\Http\Controllers\Admin\Vehicles;

use App\Http\Controllers\Controller;
use App\Models\Admin\Vehicle\Vehicle;
use App\Models\Admin\Vehicle\VehicleDocument;
use App\Models\Admin\Vehicle\VehicleMaintenance;
use App\Models\Carrier;
use App\Models\EmergencyRepair;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VehicleDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $isSuperadmin = (bool) auth()->user()?->hasRole('superadmin');
        $userCarrierId = auth()->user()?->carrierDetails?->carrier_id;

        $filters = [
            'carrier_id' => $isSuperadmin ? (string) $request->input('carrier_id', '') : (string) ($userCarrierId ?? ''),
            'start_date' => (string) $request->input('start_date', now()->startOfMonth()->format('n/j/Y')),
            'end_date' => (string) $request->input('end_date', now()->format('n/j/Y')),
        ];

        $startDate = $this->parseFilterDate($filters['start_date'], now()->startOfMonth())->startOfDay();
        $endDate = $this->parseFilterDate($filters['end_date'], now())->endOfDay();
        $carrierId = $filters['carrier_id'] !== '' ? (int) $filters['carrier_id'] : null;

        $vehicleQuery = Vehicle::query();
        $this->applyCarrierScope($vehicleQuery, $carrierId, $isSuperadmin, $userCarrierId);

        $stats = [
            'total_vehicles' => (clone $vehicleQuery)->count(),
            'active_vehicles' => (clone $vehicleQuery)->active()->count(),
            'out_of_service' => (clone $vehicleQuery)->outOfService()->count(),
            'suspended' => (clone $vehicleQuery)->suspended()->count(),
        ];

        $vehiclesByType = Vehicle::query()
            ->selectRaw('type, COUNT(*) as count')
            ->when(true, fn (Builder $query) => $this->applyCarrierScope($query, $carrierId, $isSuperadmin, $userCarrierId))
            ->groupBy('type')
            ->orderByDesc('count')
            ->get()
            ->map(fn (Vehicle $vehicle) => [
                'label' => $vehicle->type ?: 'Unknown',
                'count' => (int) $vehicle->count,
            ])
            ->values()
            ->all();

        $vehiclesByDriverType = Vehicle::query()
            ->selectRaw('driver_type, COUNT(*) as count')
            ->when(true, fn (Builder $query) => $this->applyCarrierScope($query, $carrierId, $isSuperadmin, $userCarrierId))
            ->whereNotNull('driver_type')
            ->where('driver_type', '!=', '')
            ->groupBy('driver_type')
            ->orderByDesc('count')
            ->get()
            ->map(fn (Vehicle $vehicle) => [
                'label' => str($vehicle->driver_type ?: 'unassigned')->replace('_', ' ')->title()->toString(),
                'count' => (int) $vehicle->count,
            ])
            ->values()
            ->all();

        $maintenanceQuery = VehicleMaintenance::query()
            ->whereBetween('created_at', [$startDate, $endDate]);
        $this->applyVehicleRelationCarrierScope($maintenanceQuery, $carrierId, $isSuperadmin, $userCarrierId);

        $maintenanceStats = [
            'total' => (clone $maintenanceQuery)->count(),
            'completed' => (clone $maintenanceQuery)->completed()->count(),
            'pending' => (clone $maintenanceQuery)->pending()->count(),
            'overdue' => (clone $maintenanceQuery)->overdue()->count(),
            'upcoming' => (clone $maintenanceQuery)->upcoming()->count(),
        ];

        $emergencyQuery = EmergencyRepair::query()
            ->whereBetween('created_at', [$startDate, $endDate]);
        $this->applyVehicleRelationCarrierScope($emergencyQuery, $carrierId, $isSuperadmin, $userCarrierId);

        $emergencyStats = [
            'total' => (clone $emergencyQuery)->count(),
            'completed' => (clone $emergencyQuery)->completed()->count(),
            'pending' => (clone $emergencyQuery)->whereIn('status', ['pending', 'in_progress'])->count(),
            'total_cost' => (float) ((clone $emergencyQuery)->sum('cost') ?: 0),
        ];

        $documentQuery = VehicleDocument::query();
        $this->applyVehicleRelationCarrierScope($documentQuery, $carrierId, $isSuperadmin, $userCarrierId);

        $documentStats = [
            'total' => (clone $documentQuery)->count(),
            'expiring_soon' => (clone $documentQuery)
                ->whereNotNull('expiration_date')
                ->whereDate('expiration_date', '>=', now()->toDateString())
                ->whereDate('expiration_date', '<=', now()->addDays(30)->toDateString())
                ->count(),
            'expired' => (clone $documentQuery)
                ->whereNotNull('expiration_date')
                ->whereDate('expiration_date', '<', now()->toDateString())
                ->count(),
        ];

        $expiringRegistrations = Vehicle::query()->whereNotNull('registration_expiration_date');
        $this->applyCarrierScope($expiringRegistrations, $carrierId, $isSuperadmin, $userCarrierId);
        $expiringRegistrations = $expiringRegistrations
            ->whereDate('registration_expiration_date', '>=', now()->toDateString())
            ->whereDate('registration_expiration_date', '<=', now()->addDays(30)->toDateString())
            ->count();

        $expiringInspections = Vehicle::query()->whereNotNull('annual_inspection_expiration_date');
        $this->applyCarrierScope($expiringInspections, $carrierId, $isSuperadmin, $userCarrierId);
        $expiringInspections = $expiringInspections
            ->whereDate('annual_inspection_expiration_date', '>=', now()->toDateString())
            ->whereDate('annual_inspection_expiration_date', '<=', now()->addDays(30)->toDateString())
            ->count();

        $recentMaintenance = VehicleMaintenance::with(['vehicle.carrier'])->orderByDesc('created_at')->limit(5);
        $this->applyVehicleRelationCarrierScope($recentMaintenance, $carrierId, $isSuperadmin, $userCarrierId);
        $recentMaintenance = $recentMaintenance->get()->map(function (VehicleMaintenance $maintenance) {
            return [
                'id' => $maintenance->id,
                'title' => $maintenance->service_tasks ?: ($maintenance->description ?: 'Maintenance'),
                'vehicle_label' => trim(collect([
                    $maintenance->vehicle?->company_unit_number ? 'Unit #' . $maintenance->vehicle->company_unit_number : null,
                    trim(($maintenance->vehicle?->make ?? '') . ' ' . ($maintenance->vehicle?->model ?? '')) ?: null,
                ])->filter()->implode(' - ')) ?: 'Vehicle',
                'status' => $maintenance->status ? 'completed' : ($maintenance->isOverdue() ? 'overdue' : 'pending'),
                'created_at' => $maintenance->created_at?->format('n/j/Y'),
            ];
        })->values()->all();

        $recentEmergencyRepairs = EmergencyRepair::with(['vehicle.carrier'])->orderByDesc('created_at')->limit(5);
        $this->applyVehicleRelationCarrierScope($recentEmergencyRepairs, $carrierId, $isSuperadmin, $userCarrierId);
        $recentEmergencyRepairs = $recentEmergencyRepairs->get()->map(function (EmergencyRepair $repair) {
            return [
                'id' => $repair->id,
                'title' => $repair->repair_name ?: ($repair->description ?: 'Emergency Repair'),
                'vehicle_label' => trim(collect([
                    $repair->vehicle?->company_unit_number ? 'Unit #' . $repair->vehicle->company_unit_number : null,
                    $repair->vehicle?->carrier?->name,
                ])->filter()->implode(' - ')) ?: 'Vehicle',
                'status' => $repair->status ?: 'pending',
                'cost' => (float) ($repair->cost ?: 0),
                'created_at' => $repair->created_at?->format('n/j/Y'),
            ];
        })->values()->all();

        $maintenanceTrend = collect(range(5, 1, -1))
            ->map(function (int $monthsAgo) use ($carrierId, $isSuperadmin, $userCarrierId) {
                $month = now()->subMonths($monthsAgo);
                $query = VehicleMaintenance::query()
                    ->whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month);
                $this->applyVehicleRelationCarrierScope($query, $carrierId, $isSuperadmin, $userCarrierId);

                return [
                    'label' => $month->format('M Y'),
                    'count' => $query->count(),
                ];
            })
            ->push((function () use ($carrierId, $isSuperadmin, $userCarrierId) {
                $month = now();
                $query = VehicleMaintenance::query()
                    ->whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month);
                $this->applyVehicleRelationCarrierScope($query, $carrierId, $isSuperadmin, $userCarrierId);

                return [
                    'label' => $month->format('M Y'),
                    'count' => $query->count(),
                ];
            })())
            ->values()
            ->all();

        $carriers = Carrier::query()
            ->when(! $isSuperadmin, fn (Builder $query) => $query->where('id', $userCarrierId ?: 0))
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Carrier $carrier) => ['id' => $carrier->id, 'name' => $carrier->name])
            ->values()
            ->all();

        return Inertia::render('admin/vehicles/dashboard/Index', [
            'filters' => $filters,
            'carriers' => $carriers,
            'isSuperadmin' => $isSuperadmin,
            'stats' => $stats,
            'maintenanceStats' => $maintenanceStats,
            'emergencyStats' => $emergencyStats,
            'documentStats' => $documentStats,
            'expiringRegistrations' => $expiringRegistrations,
            'expiringInspections' => $expiringInspections,
            'vehiclesByType' => $vehiclesByType,
            'vehiclesByDriverType' => $vehiclesByDriverType,
            'maintenanceTrend' => $maintenanceTrend,
            'recentMaintenance' => $recentMaintenance,
            'recentEmergencyRepairs' => $recentEmergencyRepairs,
        ]);
    }

    protected function parseFilterDate(?string $value, Carbon $default): Carbon
    {
        if (! $value) {
            return $default->copy();
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return $default->copy();
        }
    }

    protected function applyCarrierScope(Builder $query, ?int $carrierId, bool $isSuperadmin, mixed $userCarrierId): void
    {
        if ($isSuperadmin) {
            if ($carrierId) {
                $query->where('carrier_id', $carrierId);
            }

            return;
        }

        $query->where('carrier_id', $userCarrierId ?: 0);
    }

    protected function applyVehicleRelationCarrierScope(Builder $query, ?int $carrierId, bool $isSuperadmin, mixed $userCarrierId): void
    {
        $query->whereHas('vehicle', function (Builder $vehicleQuery) use ($carrierId, $isSuperadmin, $userCarrierId) {
            $this->applyCarrierScope($vehicleQuery, $carrierId, $isSuperadmin, $userCarrierId);
        });
    }
}
