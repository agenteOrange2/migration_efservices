<?php

namespace App\Repositories;

use App\Models\Carrier;
use App\Repositories\Contracts\CarrierRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Carrier Repository
 */
class CarrierRepository extends BaseRepository implements CarrierRepositoryInterface
{
    /**
     * Crear instancia del modelo
     */
    protected function makeModel(): Model
    {
        return new Carrier();
    }

    /**
     * Encontrar carriers activos
     */
    public function findActive(): Collection
    {
        return $this->model
            ->where('status', Carrier::STATUS_ACTIVE)
            ->with(['membership', 'userCarriers'])
            ->orderBy('name')
            ->get();
    }

    /**
     * Encontrar carriers pendientes de validación
     */
    public function findPendingValidation(): Collection
    {
        return $this->model
            ->where('status', Carrier::STATUS_PENDING_VALIDATION)
            ->with(['membership', 'userCarriers', 'documents'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Encontrar carrier por slug
     */
    public function findBySlug(string $slug): ?Carrier
    {
        return $this->model
            ->where('slug', $slug)
            ->with(['membership', 'userCarriers'])
            ->first();
    }

    /**
     * Encontrar carrier por DOT number
     */
    public function findByDotNumber(string $dotNumber): ?Carrier
    {
        return $this->model
            ->where('dot_number', $dotNumber)
            ->first();
    }

    /**
     * Encontrar carriers por membresía
     */
    public function findByMembership(int $membershipId): Collection
    {
        return $this->model
            ->where('id_plan', $membershipId)
            ->with(['membership'])
            ->orderBy('name')
            ->get();
    }

    /**
     * Obtener carriers con documentos completos
     */
    public function findWithCompletedDocuments(): Collection
    {
        return $this->model
            ->where('documents_completed', true)
            ->with(['membership', 'documents'])
            ->orderBy('documents_completed_at', 'desc')
            ->get();
    }

    /**
     * Obtener carriers con límites disponibles
     */
    public function findWithAvailableLimits(): Collection
    {
        return $this->model
            ->where('status', Carrier::STATUS_ACTIVE)
            ->with(['membership', 'userDrivers', 'vehicles'])
            ->get()
            ->filter(function ($carrier) {
                if (!$carrier->membership) {
                    return false;
                }
                
                $driversCount = $carrier->userDrivers()->count();
                $vehiclesCount = $carrier->vehicles()->count();
                
                return $driversCount < $carrier->membership->max_drivers ||
                       $vehiclesCount < $carrier->membership->max_vehicles;
            });
    }

    /**
     * Buscar carriers por nombre o DOT
     */
    public function search(string $search): Collection
    {
        return $this->model
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('dot_number', 'like', "%{$search}%")
                      ->orWhere('mc_number', 'like', "%{$search}%");
            })
            ->with(['membership'])
            ->orderBy('name')
            ->get();
    }
}
