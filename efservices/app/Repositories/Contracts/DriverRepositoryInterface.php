<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use App\Models\UserDriverDetail;

/**
 * Driver Repository Interface
 */
interface DriverRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Encontrar drivers activos por carrier
     *
     * @param int $carrierId
     * @return Collection
     */
    public function findActiveByCarrier(int $carrierId): Collection;

    /**
     * Encontrar drivers con aplicación completa
     *
     * @return Collection
     */
    public function findWithCompletedApplication(): Collection;

    /**
     * Encontrar drivers pendientes de aprobación
     *
     * @param int $carrierId
     * @return Collection
     */
    public function findPendingApproval(int $carrierId): Collection;

    /**
     * Encontrar drivers con documentos pendientes
     *
     * @return Collection
     */
    public function findWithPendingDocuments(): Collection;

    /**
     * Encontrar driver por email
     *
     * @param string $email
     * @return UserDriverDetail|null
     */
    public function findByEmail(string $email): ?UserDriverDetail;

    /**
     * Obtener progreso de aplicación del driver
     *
     * @param int $driverId
     * @return array
     */
    public function getApplicationProgress(int $driverId): array;

    /**
     * Buscar drivers por nombre o email
     *
     * @param string $search
     * @param int|null $carrierId
     * @return Collection
     */
    public function search(string $search, ?int $carrierId = null): Collection;

    /**
     * Encontrar drivers sin asignación de vehículo
     *
     * @param int $carrierId
     * @return Collection
     */
    public function findUnassigned(int $carrierId): Collection;
}
