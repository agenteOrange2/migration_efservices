<?php

namespace App\Services\Driver;

use App\Models\UserDriverDetail;
use App\Models\Admin\Driver\MasterCompany;
use App\Models\Admin\Driver\DriverEmploymentCompany;
use App\Models\Admin\Driver\DriverUnemploymentPeriod;
use App\Services\BaseService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

/**
 * Employment History Service
 * 
 * Maneja la lógica de negocio del historial de empleo de drivers.
 * Incluye búsqueda paginada de empresas y cálculo de años de historial.
 */
class EmploymentHistoryService extends BaseService
{
    private const CACHE_PREFIX = 'master_companies';
    private const CACHE_TTL = 300; // 5 minutos
    private const REQUIRED_YEARS = 10; // Años requeridos de historial

    /**
     * Buscar empresas con paginación y debounce-friendly
     *
     * @param string $term Término de búsqueda
     * @param int $page Número de página
     * @param int $perPage Resultados por página (máximo 20)
     * @return array
     */
    public function searchCompanies(string $term, int $page = 1, int $perPage = 20): array
    {
        // Limitar perPage a máximo 20
        $perPage = min($perPage, 20);
        
        // Sanitizar término de búsqueda
        $term = trim($term);
        
        if (strlen($term) < 2) {
            return [
                'data' => [],
                'meta' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => 0,
                    'last_page' => 1,
                ],
            ];
        }

        $cacheKey = self::CACHE_PREFIX . ":search:" . md5($term) . ":page:{$page}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($term, $page, $perPage) {
            $query = MasterCompany::query()
                ->where(function ($q) use ($term) {
                    $q->where('company_name', 'like', "%{$term}%")
                      ->orWhere('city', 'like', "%{$term}%")
                      ->orWhere('state', 'like', "%{$term}%");
                })
                ->orderBy('company_name');

            $total = $query->count();
            $lastPage = max(1, ceil($total / $perPage));
            
            $companies = $query
                ->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get()
                ->map(function ($company) {
                    return [
                        'id' => $company->id,
                        'company_name' => $company->company_name,
                        'address' => $company->address,
                        'city' => $company->city,
                        'state' => $company->state,
                        'zip' => $company->zip,
                        'phone' => $company->phone,
                        'email' => $company->email,
                        'display_name' => "{$company->company_name} - {$company->city}, {$company->state}",
                    ];
                })
                ->toArray();

            return [
                'data' => $companies,
                'meta' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => $lastPage,
                ],
            ];
        });
    }

    /**
     * Guardar empresa de empleo para un driver
     *
     * @param int $driverId
     * @param array $data
     * @return DriverEmploymentCompany
     * @throws \Exception
     */
    public function saveEmploymentCompany(int $driverId, array $data): DriverEmploymentCompany
    {
        return $this->executeInTransaction(function () use ($driverId, $data) {
            // Buscar o crear la empresa maestra
            $masterCompany = $this->findOrCreateMasterCompany($data);

            // Crear registro de empleo
            $employment = DriverEmploymentCompany::create([
                'user_driver_detail_id' => $driverId,
                'master_company_id' => $masterCompany->id,
                'employed_from' => $data['employed_from'],
                'employed_to' => $data['employed_to'] ?? null,
                'positions_held' => $data['positions_held'] ?? null,
                'subject_to_fmcsr' => $data['subject_to_fmcsr'] ?? false,
                'safety_sensitive_function' => $data['safety_sensitive_function'] ?? false,
                'reason_for_leaving' => $data['reason_for_leaving'] ?? null,
                'other_reason_description' => $data['other_reason_description'] ?? null,
                'email' => $data['email'] ?? $masterCompany->email,
                'explanation' => $data['explanation'] ?? null,
            ]);

            $this->logAction('Employment company saved', [
                'driver_id' => $driverId,
                'employment_id' => $employment->id,
                'company_name' => $masterCompany->company_name,
            ]);

            // Actualizar flag de historial completado si aplica
            $this->updateEmploymentHistoryStatus($driverId);

            return $employment;
        });
    }

    /**
     * Guardar período de desempleo
     *
     * @param int $driverId
     * @param array $data
     * @return DriverUnemploymentPeriod
     * @throws \Exception
     */
    public function saveUnemploymentPeriod(int $driverId, array $data): DriverUnemploymentPeriod
    {
        return $this->executeInTransaction(function () use ($driverId, $data) {
            $period = DriverUnemploymentPeriod::create([
                'user_driver_detail_id' => $driverId,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'reason' => $data['reason'] ?? null,
                'explanation' => $data['explanation'] ?? null,
            ]);

            $this->logAction('Unemployment period saved', [
                'driver_id' => $driverId,
                'period_id' => $period->id,
            ]);

            // Actualizar flag de historial completado si aplica
            $this->updateEmploymentHistoryStatus($driverId);

            return $period;
        });
    }


    /**
     * Calcular años de historial de empleo
     *
     * @param int $driverId
     * @return float
     */
    public function calculateYearsOfHistory(int $driverId): float
    {
        $driver = UserDriverDetail::with(['employmentCompanies', 'unemploymentPeriods'])
            ->find($driverId);

        if (!$driver) {
            return 0.0;
        }

        $totalDays = 0;

        // Sumar días de empleo
        foreach ($driver->employmentCompanies as $employment) {
            $from = Carbon::parse($employment->employed_from);
            $to = $employment->employed_to 
                ? Carbon::parse($employment->employed_to) 
                : now();
            
            $totalDays += $from->diffInDays($to);
        }

        // Sumar días de desempleo documentado
        foreach ($driver->unemploymentPeriods as $period) {
            $from = Carbon::parse($period->start_date);
            $to = Carbon::parse($period->end_date);
            
            $totalDays += $from->diffInDays($to);
        }

        return round($totalDays / 365, 2);
    }

    /**
     * Obtener gaps (huecos) en el historial de empleo
     *
     * @param int $driverId
     * @return array
     */
    public function getEmploymentGaps(int $driverId): array
    {
        $driver = UserDriverDetail::with(['employmentCompanies', 'unemploymentPeriods'])
            ->find($driverId);

        if (!$driver) {
            return [];
        }

        // Combinar todos los períodos y ordenar por fecha
        $periods = collect();

        foreach ($driver->employmentCompanies as $employment) {
            $periods->push([
                'type' => 'employment',
                'start' => Carbon::parse($employment->employed_from),
                'end' => $employment->employed_to 
                    ? Carbon::parse($employment->employed_to) 
                    : now(),
                'company' => $employment->company?->company_name ?? 'Unknown',
            ]);
        }

        foreach ($driver->unemploymentPeriods as $period) {
            $periods->push([
                'type' => 'unemployment',
                'start' => Carbon::parse($period->start_date),
                'end' => Carbon::parse($period->end_date),
                'reason' => $period->reason,
            ]);
        }

        // Ordenar por fecha de inicio
        $periods = $periods->sortBy('start')->values();

        // Encontrar gaps
        $gaps = [];
        $requiredStartDate = now()->subYears(self::REQUIRED_YEARS);

        for ($i = 0; $i < $periods->count() - 1; $i++) {
            $currentEnd = $periods[$i]['end'];
            $nextStart = $periods[$i + 1]['start'];

            // Si hay más de 30 días entre períodos, es un gap
            if ($currentEnd->diffInDays($nextStart) > 30) {
                $gaps[] = [
                    'start' => $currentEnd->format('Y-m-d'),
                    'end' => $nextStart->format('Y-m-d'),
                    'days' => $currentEnd->diffInDays($nextStart),
                ];
            }
        }

        // Verificar gap al inicio (desde hace 10 años)
        if ($periods->isNotEmpty()) {
            $firstPeriodStart = $periods->first()['start'];
            if ($firstPeriodStart->gt($requiredStartDate)) {
                $gapDays = $requiredStartDate->diffInDays($firstPeriodStart);
                if ($gapDays > 30) {
                    array_unshift($gaps, [
                        'start' => $requiredStartDate->format('Y-m-d'),
                        'end' => $firstPeriodStart->format('Y-m-d'),
                        'days' => $gapDays,
                        'is_initial_gap' => true,
                    ]);
                }
            }
        }

        return $gaps;
    }

    /**
     * Verificar si el historial de empleo está completo
     *
     * @param int $driverId
     * @return bool
     */
    public function isEmploymentHistoryComplete(int $driverId): bool
    {
        $yearsOfHistory = $this->calculateYearsOfHistory($driverId);
        $gaps = $this->getEmploymentGaps($driverId);

        // Debe tener al menos 10 años de historial y no tener gaps significativos
        return $yearsOfHistory >= self::REQUIRED_YEARS && empty($gaps);
    }

    /**
     * Obtener resumen del historial de empleo
     *
     * @param int $driverId
     * @return array
     */
    public function getEmploymentSummary(int $driverId): array
    {
        $driver = UserDriverDetail::with(['employmentCompanies.company', 'unemploymentPeriods'])
            ->find($driverId);

        if (!$driver) {
            return [
                'years_of_history' => 0,
                'is_complete' => false,
                'employment_count' => 0,
                'unemployment_count' => 0,
                'gaps' => [],
                'employments' => [],
            ];
        }

        return [
            'years_of_history' => $this->calculateYearsOfHistory($driverId),
            'is_complete' => $this->isEmploymentHistoryComplete($driverId),
            'employment_count' => $driver->employmentCompanies->count(),
            'unemployment_count' => $driver->unemploymentPeriods->count(),
            'gaps' => $this->getEmploymentGaps($driverId),
            'employments' => $driver->employmentCompanies->map(function ($emp) {
                return [
                    'id' => $emp->id,
                    'company_name' => $emp->company?->company_name ?? 'Unknown',
                    'employed_from' => $emp->employed_from?->format('Y-m-d'),
                    'employed_to' => $emp->employed_to?->format('Y-m-d'),
                    'positions_held' => $emp->positions_held,
                    'verification_status' => $emp->verification_status,
                ];
            })->toArray(),
        ];
    }

    /**
     * Actualizar estado del historial de empleo del driver
     *
     * @param int $driverId
     * @return void
     */
    private function updateEmploymentHistoryStatus(int $driverId): void
    {
        $isComplete = $this->isEmploymentHistoryComplete($driverId);
        
        UserDriverDetail::where('id', $driverId)
            ->update(['has_completed_employment_history' => $isComplete]);
    }

    /**
     * Buscar o crear empresa maestra
     *
     * @param array $data
     * @return MasterCompany
     */
    private function findOrCreateMasterCompany(array $data): MasterCompany
    {
        // Si viene un ID de empresa existente
        if (!empty($data['master_company_id'])) {
            $company = MasterCompany::find($data['master_company_id']);
            if ($company) {
                return $company;
            }
        }

        // Buscar por nombre exacto
        $company = MasterCompany::where('company_name', $data['company_name'] ?? '')
            ->first();

        if ($company) {
            return $company;
        }

        // Crear nueva empresa
        return MasterCompany::create([
            'company_name' => $data['company_name'],
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'zip' => $data['zip'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'contact' => $data['contact'] ?? null,
        ]);
    }

    /**
     * Eliminar registro de empleo
     *
     * @param int $employmentId
     * @param int $driverId
     * @return bool
     */
    public function deleteEmploymentCompany(int $employmentId, int $driverId): bool
    {
        try {
            $deleted = DriverEmploymentCompany::where('id', $employmentId)
                ->where('user_driver_detail_id', $driverId)
                ->delete();

            if ($deleted) {
                $this->updateEmploymentHistoryStatus($driverId);
                $this->logAction('Employment company deleted', [
                    'employment_id' => $employmentId,
                    'driver_id' => $driverId,
                ]);
            }

            return $deleted > 0;
        } catch (\Exception $e) {
            $this->logError('Failed to delete employment', $e, [
                'employment_id' => $employmentId,
                'driver_id' => $driverId,
            ]);
            return false;
        }
    }

    /**
     * Eliminar período de desempleo
     *
     * @param int $periodId
     * @param int $driverId
     * @return bool
     */
    public function deleteUnemploymentPeriod(int $periodId, int $driverId): bool
    {
        try {
            $deleted = DriverUnemploymentPeriod::where('id', $periodId)
                ->where('user_driver_detail_id', $driverId)
                ->delete();

            if ($deleted) {
                $this->updateEmploymentHistoryStatus($driverId);
                $this->logAction('Unemployment period deleted', [
                    'period_id' => $periodId,
                    'driver_id' => $driverId,
                ]);
            }

            return $deleted > 0;
        } catch (\Exception $e) {
            $this->logError('Failed to delete unemployment period', $e, [
                'period_id' => $periodId,
                'driver_id' => $driverId,
            ]);
            return false;
        }
    }

    /**
     * Limpiar cache de búsqueda de empresas
     *
     * @return void
     */
    public function clearSearchCache(): void
    {
        // En producción, usar tags de cache o patrón de keys
        // Por ahora, el cache expira automáticamente
    }
}
