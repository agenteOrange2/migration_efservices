<?php

namespace App\Services\Driver;

use App\Models\UserDriverDetail;
use App\Services\BaseService;
use Illuminate\Support\Facades\Cache;

/**
 * Step Completion Calculator Service
 * 
 * Calcula el progreso y completitud de cada step del registro de drivers.
 */
class StepCompletionCalculator extends BaseService
{
    private const TOTAL_STEPS = 15;
    private const CACHE_PREFIX = 'driver_completion';
    private const CACHE_TTL = 300; // 5 minutos

    /**
     * Campos requeridos por step
     */
    private array $requiredFieldsByStep = [
        1 => [ // General Info
            'user.name',
            'user.email',
            'last_name',
            'phone',
            'date_of_birth',
        ],
        2 => [ // Address
            'application.addresses',
        ],
        3 => [ // Application
            'application_detail',
        ],
        4 => [ // License
            'licenses',
        ],
        5 => [ // Medical
            'medicalQualification',
        ],
        6 => [ // Training
            // Opcional - puede no tener escuelas de entrenamiento
        ],
        7 => [ // Traffic
            // Opcional - puede no tener infracciones
        ],
        8 => [ // Accident
            // Opcional - puede no tener accidentes
        ],
        9 => [ // FMCSR
            'fmcsrData',
        ],
        10 => [ // Employment
            'has_completed_employment_history',
        ],
        11 => [ // Policy
            'companyPolicy',
        ],
        12 => [ // Criminal
            'criminalHistory',
        ],
        13 => [ // W-9
            'w9Form',
        ],
        14 => [ // Certification
            'certification',
        ],
        15 => [ // Confirmation
            // Solo revisión
        ],
    ];

    /**
     * Calcular la completitud de un step específico
     *
     * @param int $driverId
     * @param int $step
     * @return array
     */
    public function calculateStepCompletion(int $driverId, int $step): array
    {
        $driver = UserDriverDetail::with([
            'user',
            'application.addresses',
            'application.details.vehicleDriverAssignment',
            'licenses',
            'medicalQualification',
            'trainingSchools',
            'trafficConvictions',
            'accidents',
            'fmcsrData',
            'employmentCompanies',
            'companyPolicy',
            'criminalHistory',
            'w9Form',
            'certification',
        ])->find($driverId);

        if (!$driver) {
            return $this->emptyCompletionResult($step);
        }

        $requiredFields = $this->requiredFieldsByStep[$step] ?? [];
        $completedFields = [];
        $missingFields = [];

        foreach ($requiredFields as $field) {
            if ($this->isFieldCompleted($driver, $field)) {
                $completedFields[] = $field;
            } else {
                $missingFields[] = $field;
            }
        }

        $status = $this->calculateStatus($requiredFields, $completedFields, $driver, $step);
        $percentage = $this->calculatePercentage($requiredFields, $completedFields);

        return [
            'step' => $step,
            'status' => $status,
            'required_fields' => $requiredFields,
            'completed_fields' => $completedFields,
            'missing_fields' => $missingFields,
            'percentage' => $percentage,
        ];
    }

    /**
     * Calcular el porcentaje total de completitud
     *
     * @param int $driverId
     * @return float
     */
    public function calculateTotalCompletion(int $driverId): float
    {
        $cacheKey = self::CACHE_PREFIX . ":{$driverId}:total";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($driverId) {
            $totalPercentage = 0;
            $stepsWithRequirements = 0;

            for ($step = 1; $step <= self::TOTAL_STEPS; $step++) {
                $completion = $this->calculateStepCompletion($driverId, $step);
                
                // Solo contar steps que tienen campos requeridos
                if (!empty($this->requiredFieldsByStep[$step])) {
                    $totalPercentage += $completion['percentage'];
                    $stepsWithRequirements++;
                }
            }

            if ($stepsWithRequirements === 0) {
                return 0.0;
            }

            $result = round($totalPercentage / $stepsWithRequirements, 2);
            
            // Asegurar que esté entre 0 y 100
            return max(0, min(100, $result));
        });
    }


    /**
     * Obtener los campos requeridos para un step
     *
     * @param int $step
     * @return array
     */
    public function getRequiredFieldsForStep(int $step): array
    {
        return $this->requiredFieldsByStep[$step] ?? [];
    }

    /**
     * Obtener los campos requeridos faltantes para un step
     *
     * @param int $driverId
     * @param int $step
     * @return array
     */
    public function getMissingRequiredFields(int $driverId, int $step): array
    {
        $completion = $this->calculateStepCompletion($driverId, $step);
        return $completion['missing_fields'];
    }

    /**
     * Obtener todos los steps que necesitan atención
     *
     * @param int $driverId
     * @return array
     */
    public function getStepsNeedingAttention(int $driverId): array
    {
        $stepsNeedingAttention = [];

        for ($step = 1; $step <= self::TOTAL_STEPS; $step++) {
            $completion = $this->calculateStepCompletion($driverId, $step);
            
            if ($completion['status'] !== 'complete' && !empty($this->requiredFieldsByStep[$step])) {
                $stepsNeedingAttention[] = [
                    'step' => $step,
                    'status' => $completion['status'],
                    'missing_fields' => $completion['missing_fields'],
                    'percentage' => $completion['percentage'],
                ];
            }
        }

        return $stepsNeedingAttention;
    }

    /**
     * Obtener resumen de completitud de todos los steps
     *
     * @param int $driverId
     * @return array
     */
    public function getCompletionSummary(int $driverId): array
    {
        $summary = [];

        for ($step = 1; $step <= self::TOTAL_STEPS; $step++) {
            $completion = $this->calculateStepCompletion($driverId, $step);
            $summary[$step] = [
                'status' => $completion['status'],
                'percentage' => $completion['percentage'],
            ];
        }

        return [
            'steps' => $summary,
            'total_percentage' => $this->calculateTotalCompletion($driverId),
            'steps_needing_attention' => $this->getStepsNeedingAttention($driverId),
        ];
    }

    /**
     * Invalidar cache de completitud para un driver
     *
     * @param int $driverId
     * @return void
     */
    public function invalidateCache(int $driverId): void
    {
        Cache::forget(self::CACHE_PREFIX . ":{$driverId}:total");
    }

    /**
     * Verificar si un campo está completado
     *
     * @param UserDriverDetail $driver
     * @param string $field
     * @return bool
     */
    private function isFieldCompleted(UserDriverDetail $driver, string $field): bool
    {
        // Campos de relaciones
        if (str_contains($field, '.')) {
            return $this->isRelationFieldCompleted($driver, $field);
        }

        // Campos directos del modelo
        if ($field === 'licenses') {
            return $driver->licenses->count() > 0;
        }

        if ($field === 'medicalQualification') {
            return $driver->medicalQualification !== null;
        }

        if ($field === 'fmcsrData') {
            return $driver->fmcsrData !== null;
        }

        if ($field === 'companyPolicy') {
            return $driver->companyPolicy !== null;
        }

        if ($field === 'criminalHistory') {
            return $driver->criminalHistory !== null;
        }

        if ($field === 'w9Form') {
            return $driver->w9Form !== null;
        }

        if ($field === 'certification') {
            return $driver->certification !== null;
        }

        if ($field === 'application_detail') {
            $details = $driver->application?->details;
            if (!$details) return false;
            // Step 3 is complete if applying_position is set OR if vehicle assignment was saved
            return ($details->applying_position !== null && $details->applying_position !== '')
                || $details->vehicle_driver_assignment_id !== null;
        }

        if ($field === 'has_completed_employment_history') {
            return (bool) $driver->has_completed_employment_history;
        }

        // Campo simple
        $value = $driver->{$field};
        return $value !== null && $value !== '';
    }

    /**
     * Verificar si un campo de relación está completado
     *
     * @param UserDriverDetail $driver
     * @param string $field
     * @return bool
     */
    private function isRelationFieldCompleted(UserDriverDetail $driver, string $field): bool
    {
        $parts = explode('.', $field);
        $relation = $parts[0];
        $attribute = $parts[1] ?? null;

        $related = $driver->{$relation};

        if ($related === null) {
            return false;
        }

        // Si es una colección (hasMany)
        if ($related instanceof \Illuminate\Database\Eloquent\Collection) {
            if ($attribute === null) {
                return $related->count() > 0;
            }
            // Verificar que al menos un item tenga el atributo
            return $related->contains(function ($item) use ($attribute) {
                $value = $item->{$attribute};
                return $value !== null && $value !== '';
            });
        }

        // Si es un modelo (hasOne/belongsTo)
        if ($attribute === null) {
            return true;
        }

        $value = $related->{$attribute};
        return $value !== null && $value !== '';
    }

    /**
     * Calcular el estado de completitud
     *
     * @param array $requiredFields
     * @param array $completedFields
     * @param UserDriverDetail $driver
     * @param int $step
     * @return string
     */
    private function calculateStatus(array $requiredFields, array $completedFields, UserDriverDetail $driver, int $step): string
    {
        // Steps sin campos requeridos siempre están completos
        if (empty($requiredFields)) {
            return $this->hasAnyDataForStep($driver, $step) ? 'complete' : 'empty';
        }

        if (count($completedFields) === count($requiredFields)) {
            return 'complete';
        }

        if (count($completedFields) > 0) {
            return 'partial';
        }

        return 'empty';
    }

    /**
     * Verificar si hay algún dato para un step opcional
     *
     * @param UserDriverDetail $driver
     * @param int $step
     * @return bool
     */
    private function hasAnyDataForStep(UserDriverDetail $driver, int $step): bool
    {
        return match ($step) {
            6 => $driver->trainingSchools->count() > 0 || $driver->driverTrainings->count() > 0,
            7 => $driver->trafficConvictions->count() > 0,
            8 => $driver->accidents->count() > 0,
            15 => true, // Confirmation siempre está "completo" si llegó ahí
            default => false,
        };
    }

    /**
     * Calcular porcentaje de completitud
     *
     * @param array $requiredFields
     * @param array $completedFields
     * @return float
     */
    private function calculatePercentage(array $requiredFields, array $completedFields): float
    {
        if (empty($requiredFields)) {
            return 100.0;
        }

        return round((count($completedFields) / count($requiredFields)) * 100, 2);
    }

    /**
     * Resultado vacío de completitud
     *
     * @param int $step
     * @return array
     */
    private function emptyCompletionResult(int $step): array
    {
        return [
            'step' => $step,
            'status' => 'empty',
            'required_fields' => $this->requiredFieldsByStep[$step] ?? [],
            'completed_fields' => [],
            'missing_fields' => $this->requiredFieldsByStep[$step] ?? [],
            'percentage' => 0,
        ];
    }
}
