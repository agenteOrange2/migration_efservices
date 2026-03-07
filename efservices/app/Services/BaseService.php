<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Base Service Class
 * 
 * Clase base para todos los servicios del sistema.
 * Proporciona funcionalidad común y manejo de transacciones.
 */
abstract class BaseService
{
    /**
     * Ejecutar operación dentro de una transacción de base de datos
     *
     * @param callable $callback
     * @return mixed
     * @throws \Exception
     */
    protected function executeInTransaction(callable $callback)
    {
        try {
            return DB::transaction($callback);
        } catch (\Exception $e) {
            Log::error('Transaction failed in ' . static::class, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Log de acción del servicio
     *
     * @param string $action
     * @param array $context
     * @return void
     */
    protected function logAction(string $action, array $context = []): void
    {
        Log::info(static::class . ': ' . $action, $context);
    }

    /**
     * Log de error del servicio
     *
     * @param string $action
     * @param \Exception $exception
     * @param array $context
     * @return void
     */
    protected function logError(string $action, \Exception $exception, array $context = []): void
    {
        Log::error(static::class . ': ' . $action . ' failed', array_merge($context, [
            'error' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]));
    }

    /**
     * Validar que un modelo existe
     *
     * @param mixed $model
     * @param string $modelName
     * @throws \Exception
     */
    protected function ensureModelExists($model, string $modelName): void
    {
        if (!$model) {
            throw new \Exception("{$modelName} not found");
        }
    }
}
