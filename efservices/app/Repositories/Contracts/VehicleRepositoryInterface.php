<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use App\Models\Admin\Vehicle\Vehicle;

/**
 * Vehicle Repository Interface
 */
interface VehicleRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Encontrar vehículos por carrier
     *
     * @param int $carrierId
     * @return Collection
     */
    public function findByCarrier(int $carrierId): Collection;

    /**
     * Encontrar vehículos sin asignar
     *
     * @param int $carrierId
     * @return Collection
     */
    public function findUnassigned(int $carrierId): Collection;

    /**
     * Encontrar vehículos con asignación activa
     *
     * @param int $carrierId
     * @return Collection
     */
    public function findAssigned(int $carrierId): Collection;

    /**
     * Encontrar vehículo por VIN
     *
     * @param string $vin
     * @return Vehicle|null
     */
    public function findByVin(string $vin): ?Vehicle;

    /**
     * Encontrar vehículo por placa
     *
     * @param string $plate
     * @return Vehicle|null
     */
    public function findByPlate(string $plate): ?Vehicle;

    /**
     * Encontrar vehículos con mantenimiento próximo
     *
     * @param int $days
     * @return Collection
     */
    public function findWithUpcomingMaintenance(int $days = 30): Collection;

    /**
     * Encontrar vehículos con mantenimiento vencido
     *
     * @return Collection
     */
    public function findWithOverdueMaintenance(): Collection;

    /**
     * Buscar vehículos por VIN, placa o marca
     *
     * @param string $search
     * @param int|null $carrierId
     * @return Collection
     */
    public function search(string $search, ?int $carrierId = null): Collection;
}
