<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use App\Models\Carrier;

/**
 * Carrier Repository Interface
 */
interface CarrierRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Encontrar carriers activos
     *
     * @return Collection
     */
    public function findActive(): Collection;

    /**
     * Encontrar carriers pendientes de validación
     *
     * @return Collection
     */
    public function findPendingValidation(): Collection;

    /**
     * Encontrar carrier por slug
     *
     * @param string $slug
     * @return Carrier|null
     */
    public function findBySlug(string $slug): ?Carrier;

    /**
     * Encontrar carrier por DOT number
     *
     * @param string $dotNumber
     * @return Carrier|null
     */
    public function findByDotNumber(string $dotNumber): ?Carrier;

    /**
     * Encontrar carriers por membresía
     *
     * @param int $membershipId
     * @return Collection
     */
    public function findByMembership(int $membershipId): Collection;

    /**
     * Obtener carriers con documentos completos
     *
     * @return Collection
     */
    public function findWithCompletedDocuments(): Collection;

    /**
     * Obtener carriers con límites disponibles
     *
     * @return Collection
     */
    public function findWithAvailableLimits(): Collection;

    /**
     * Buscar carriers por nombre o DOT
     *
     * @param string $search
     * @return Collection
     */
    public function search(string $search): Collection;
}
