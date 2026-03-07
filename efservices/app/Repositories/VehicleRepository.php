<?php

namespace App\Repositories;

use App\Models\Admin\Vehicle\Vehicle;
use App\Repositories\Contracts\VehicleRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Vehicle Repository
 */
class VehicleRepository extends BaseRepository implements VehicleRepositoryInterface
{
    /**
     * Crear instancia del modelo
     */
    protected function makeModel(): Model
    {
        return new Vehicle();
    }

    /**
     * Encontrar vehículos por carrier
     */
    public function findByCarrier(int $carrierId): Collection
    {
        return $this->model
            ->where('carrier_id', $carrierId)
            ->with(['make', 'type', 'vehicleAssignments.driverDetail.user'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Encontrar vehículos sin asignar
     */
    public function findUnassigned(int $carrierId): Collection
    {
        return $this->model
            ->where('carrier_id', $carrierId)
            ->whereDoesntHave('vehicleAssignments', function ($query) {
                $query->where('status', 'active');
            })
            ->with(['make', 'type'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Encontrar vehículos con asignación activa
     */
    public function findAssigned(int $carrierId): Collection
    {
        return $this->model
            ->where('carrier_id', $carrierId)
            ->whereHas('vehicleAssignments', function ($query) {
                $query->where('status', 'active');
            })
            ->with([
                'make',
                'type',
                'vehicleAssignments' => function ($query) {
                    $query->where('status', 'active')
                          ->with(['driverDetail.user', 'ownerOperatorDetail', 'thirdPartyDetail']);
                }
            ])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Encontrar vehículo por VIN
     */
    public function findByVin(string $vin): ?Vehicle
    {
        return $this->model
            ->where('vin', $vin)
            ->with(['carrier', 'make', 'type'])
            ->first();
    }

    /**
     * Encontrar vehículo por placa
     */
    public function findByPlate(string $plate): ?Vehicle
    {
        return $this->model
            ->where('plate', $plate)
            ->with(['carrier', 'make', 'type'])
            ->first();
    }

    /**
     * Encontrar vehículos con mantenimiento próximo
     */
    public function findWithUpcomingMaintenance(int $days = 30): Collection
    {
        $futureDate = now()->addDays($days);

        return $this->model
            ->whereHas('maintenances', function ($query) use ($futureDate) {
                $query->where('next_maintenance_date', '<=', $futureDate)
                      ->where('next_maintenance_date', '>=', now())
                      ->where('status', 'pending');
            })
            ->with([
                'carrier',
                'make',
                'type',
                'maintenances' => function ($query) use ($futureDate) {
                    $query->where('next_maintenance_date', '<=', $futureDate)
                          ->where('next_maintenance_date', '>=', now())
                          ->where('status', 'pending')
                          ->orderBy('next_maintenance_date');
                }
            ])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Encontrar vehículos con mantenimiento vencido
     */
    public function findWithOverdueMaintenance(): Collection
    {
        return $this->model
            ->whereHas('maintenances', function ($query) {
                $query->where('next_maintenance_date', '<', now())
                      ->where('status', 'pending');
            })
            ->with([
                'carrier',
                'make',
                'type',
                'maintenances' => function ($query) {
                    $query->where('next_maintenance_date', '<', now())
                          ->where('status', 'pending')
                          ->orderBy('next_maintenance_date');
                }
            ])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Buscar vehículos por VIN, placa o marca
     */
    public function search(string $search, ?int $carrierId = null): Collection
    {
        $query = $this->model
            ->where(function ($q) use ($search) {
                $q->where('vin', 'like', "%{$search}%")
                  ->orWhere('plate', 'like', "%{$search}%")
                  ->orWhere('year', 'like', "%{$search}%")
                  ->orWhereHas('make', function ($query) use ($search) {
                      $query->where('name', 'like', "%{$search}%");
                  });
            });

        if ($carrierId) {
            $query->where('carrier_id', $carrierId);
        }

        return $query
            ->with(['carrier', 'make', 'type'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
