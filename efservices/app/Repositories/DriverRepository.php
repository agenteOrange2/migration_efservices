<?php

namespace App\Repositories;

use App\Models\UserDriverDetail;
use App\Repositories\Contracts\DriverRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Driver Repository
 */
class DriverRepository extends BaseRepository implements DriverRepositoryInterface
{
    /**
     * Crear instancia del modelo
     */
    protected function makeModel(): Model
    {
        return new UserDriverDetail();
    }

    /**
     * Encontrar drivers activos por carrier
     */
    public function findActiveByCarrier(int $carrierId): Collection
    {
        return $this->model
            ->where('carrier_id', $carrierId)
            ->where('status', UserDriverDetail::STATUS_ACTIVE)
            ->with(['user', 'licenses', 'medicalQualification'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Encontrar drivers con aplicación completa
     */
    public function findWithCompletedApplication(): Collection
    {
        return $this->model
            ->where('application_completed', true)
            ->with(['user', 'carrier', 'application'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Encontrar drivers pendientes de aprobación
     */
    public function findPendingApproval(int $carrierId): Collection
    {
        return $this->model
            ->where('carrier_id', $carrierId)
            ->where('application_completed', true)
            ->where('status', UserDriverDetail::STATUS_PENDING)
            ->with(['user', 'application', 'licenses'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Encontrar drivers con documentos pendientes
     */
    public function findWithPendingDocuments(): Collection
    {
        return $this->model
            ->where('application_completed', false)
            ->where('completion_percentage', '<', 100)
            ->with(['user', 'carrier'])
            ->orderBy('completion_percentage', 'desc')
            ->get();
    }

    /**
     * Encontrar driver por email
     */
    public function findByEmail(string $email): ?UserDriverDetail
    {
        return $this->model
            ->whereHas('user', function ($query) use ($email) {
                $query->where('email', $email);
            })
            ->with(['user', 'carrier'])
            ->first();
    }

    /**
     * Obtener progreso de aplicación del driver
     */
    public function getApplicationProgress(int $driverId): array
    {
        $driver = $this->model
            ->with([
                'application',
                'licenses',
                'experiences',
                'workHistories',
                'trainingSchools',
                'trafficConvictions',
                'accidents',
                'medicalQualification',
                'companyPolicy',
                'criminalHistory',
                'certification'
            ])
            ->find($driverId);

        if (!$driver) {
            return [];
        }

        return [
            'completion_percentage' => $driver->completion_percentage,
            'application_completed' => $driver->application_completed,
            'sections' => [
                'personal_info' => $driver->phone && $driver->date_of_birth,
                'addresses' => $driver->application && $driver->application->addresses()->count() > 0,
                'licenses' => $driver->licenses()->count() > 0,
                'experience' => $driver->experiences()->count() > 0,
                'work_history' => $driver->workHistories()->count() > 0,
                'training_schools' => $driver->trainingSchools()->count() > 0,
                'traffic_convictions' => true, // Puede ser 0
                'accidents' => true, // Puede ser 0
                'medical' => $driver->medicalQualification !== null,
                'company_policy' => $driver->companyPolicy !== null,
                'criminal_history' => $driver->criminalHistory !== null,
                'certification' => $driver->certification !== null,
            ],
        ];
    }

    /**
     * Buscar drivers por nombre o email
     */
    public function search(string $search, ?int $carrierId = null): Collection
    {
        $query = $this->model
            ->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })
            ->orWhere('last_name', 'like', "%{$search}%")
            ->orWhere('phone', 'like', "%{$search}%");

        if ($carrierId) {
            $query->where('carrier_id', $carrierId);
        }

        return $query
            ->with(['user', 'carrier'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Encontrar drivers sin asignación de vehículo
     */
    public function findUnassigned(int $carrierId): Collection
    {
        return $this->model
            ->where('carrier_id', $carrierId)
            ->where('status', UserDriverDetail::STATUS_ACTIVE)
            ->whereDoesntHave('vehicleAssignments', function ($query) {
                $query->where('status', 'active');
            })
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
