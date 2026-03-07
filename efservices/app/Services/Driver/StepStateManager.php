<?php

namespace App\Services\Driver;

use App\Services\BaseService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

/**
 * Step State Manager Service
 * 
 * Maneja el estado de todos los steps del registro de drivers
 * y la navegación entre ellos.
 */
class StepStateManager extends BaseService
{
    private const TOTAL_STEPS = 14;
    private const CACHE_PREFIX = 'driver_registration';
    private const CACHE_TTL = 3600; // 1 hora

    /**
     * Nombres de los steps
     */
    private array $stepNames = [
        1 => 'general_info',
        2 => 'address',
        3 => 'application',
        4 => 'license',
        5 => 'medical',
        6 => 'training',
        7 => 'traffic',
        8 => 'accident',
        9 => 'fmcsr',
        10 => 'employment',
        11 => 'policy',
        12 => 'criminal',
        13 => 'certification',
        14 => 'confirmation',
    ];

    /**
     * Obtener el estado de un step específico
     *
     * @param int $driverId
     * @param int $step
     * @return array
     */
    public function getStepState(int $driverId, int $step): array
    {
        $cacheKey = $this->getStepCacheKey($driverId, $step);
        
        return Cache::get($cacheKey, [
            'step' => $step,
            'name' => $this->stepNames[$step] ?? 'unknown',
            'data' => [],
            'is_dirty' => false,
            'last_saved_at' => null,
        ]);
    }

    /**
     * Establecer el estado de un step
     *
     * @param int $driverId
     * @param int $step
     * @param array $data
     * @return void
     */
    public function setStepState(int $driverId, int $step, array $data): void
    {
        $cacheKey = $this->getStepCacheKey($driverId, $step);
        
        $state = [
            'step' => $step,
            'name' => $this->stepNames[$step] ?? 'unknown',
            'data' => $data,
            'is_dirty' => true,
            'last_modified_at' => now()->toIso8601String(),
        ];

        Cache::put($cacheKey, $state, self::CACHE_TTL);

        $this->logAction('Step state updated', [
            'driver_id' => $driverId,
            'step' => $step,
        ]);
    }

    /**
     * Verificar si un step tiene cambios sin guardar
     *
     * @param int $driverId
     * @param int $step
     * @return bool
     */
    public function hasUnsavedChanges(int $driverId, int $step): bool
    {
        $state = $this->getStepState($driverId, $step);
        return $state['is_dirty'] ?? false;
    }

    /**
     * Marcar un step como guardado
     *
     * @param int $driverId
     * @param int $step
     * @return void
     */
    public function markAsSaved(int $driverId, int $step): void
    {
        $cacheKey = $this->getStepCacheKey($driverId, $step);
        $state = $this->getStepState($driverId, $step);
        
        $state['is_dirty'] = false;
        $state['last_saved_at'] = now()->toIso8601String();

        Cache::put($cacheKey, $state, self::CACHE_TTL);
    }

    /**
     * Obtener el estado de completitud de un step
     *
     * @param int $driverId
     * @param int $step
     * @param array $requiredFields
     * @param array $filledFields
     * @return string 'complete', 'partial', 'empty'
     */
    public function getCompletionStatus(int $driverId, int $step, array $requiredFields, array $filledFields): string
    {
        if (empty($requiredFields)) {
            return 'complete';
        }

        $filledRequired = array_intersect($requiredFields, $filledFields);
        
        if (count($filledRequired) === count($requiredFields)) {
            return 'complete';
        }
        
        if (count($filledRequired) > 0 || count($filledFields) > 0) {
            return 'partial';
        }
        
        return 'empty';
    }


    /**
     * Verificar si se puede navegar a un step específico
     *
     * @param int $driverId
     * @param int $targetStep
     * @param int $currentStep
     * @param bool $allowSkip Permitir saltar steps
     * @return bool
     */
    public function canNavigateTo(int $driverId, int $targetStep, int $currentStep, bool $allowSkip = true): bool
    {
        // Validar rango de steps
        if ($targetStep < 1 || $targetStep > self::TOTAL_STEPS) {
            return false;
        }

        // Siempre se puede ir hacia atrás
        if ($targetStep < $currentStep) {
            return true;
        }

        // Si se permite saltar, se puede ir a cualquier step
        if ($allowSkip) {
            return true;
        }

        // Solo se puede avanzar un step a la vez
        return $targetStep === $currentStep + 1;
    }

    /**
     * Guardar el step actual antes de navegar
     *
     * @param int $driverId
     * @param int $step
     * @param array $data
     * @return bool
     */
    public function saveCurrentStep(int $driverId, int $step, array $data): bool
    {
        try {
            $this->setStepState($driverId, $step, $data);
            $this->markAsSaved($driverId, $step);
            
            // Guardar el último step visitado
            $this->setLastVisitedStep($driverId, $step);
            
            return true;
        } catch (\Exception $e) {
            $this->logError('Failed to save step', $e, [
                'driver_id' => $driverId,
                'step' => $step,
            ]);
            return false;
        }
    }

    /**
     * Obtener el último step visitado por el driver
     *
     * @param int $driverId
     * @return int
     */
    public function getLastVisitedStep(int $driverId): int
    {
        $cacheKey = self::CACHE_PREFIX . ":{$driverId}:last_step";
        return Cache::get($cacheKey, 1);
    }

    /**
     * Establecer el último step visitado
     *
     * @param int $driverId
     * @param int $step
     * @return void
     */
    public function setLastVisitedStep(int $driverId, int $step): void
    {
        $cacheKey = self::CACHE_PREFIX . ":{$driverId}:last_step";
        Cache::put($cacheKey, $step, self::CACHE_TTL);
    }

    /**
     * Obtener todos los estados de steps para un driver
     *
     * @param int $driverId
     * @return array
     */
    public function getAllStepStates(int $driverId): array
    {
        $states = [];
        
        for ($step = 1; $step <= self::TOTAL_STEPS; $step++) {
            $states[$step] = $this->getStepState($driverId, $step);
        }
        
        return $states;
    }

    /**
     * Limpiar todos los estados de steps para un driver
     *
     * @param int $driverId
     * @return void
     */
    public function clearAllStepStates(int $driverId): void
    {
        for ($step = 1; $step <= self::TOTAL_STEPS; $step++) {
            $cacheKey = $this->getStepCacheKey($driverId, $step);
            Cache::forget($cacheKey);
        }
        
        Cache::forget(self::CACHE_PREFIX . ":{$driverId}:last_step");
        
        $this->logAction('All step states cleared', ['driver_id' => $driverId]);
    }

    /**
     * Obtener el nombre de un step
     *
     * @param int $step
     * @return string
     */
    public function getStepName(int $step): string
    {
        return $this->stepNames[$step] ?? 'unknown';
    }

    /**
     * Obtener el total de steps
     *
     * @return int
     */
    public function getTotalSteps(): int
    {
        return self::TOTAL_STEPS;
    }

    /**
     * Generar la clave de cache para un step
     *
     * @param int $driverId
     * @param int $step
     * @return string
     */
    private function getStepCacheKey(int $driverId, int $step): string
    {
        return self::CACHE_PREFIX . ":{$driverId}:step:{$step}:state";
    }
}
