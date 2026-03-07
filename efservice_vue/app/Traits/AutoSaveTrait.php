<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

/**
 * Auto Save Trait
 * 
 * Proporciona funcionalidad de auto-guardado con debounce para componentes Livewire.
 * Implementa guardado automático después de 2 segundos de inactividad.
 */
trait AutoSaveTrait
{
    /**
     * Delay en milisegundos para el debounce del auto-guardado
     */
    protected int $autoSaveDelay = 2000;

    /**
     * Indica si el auto-guardado está habilitado
     */
    public bool $autoSaveEnabled = true;

    /**
     * Indica si hay cambios pendientes de guardar
     */
    public bool $hasPendingChanges = false;

    /**
     * Timestamp del último guardado
     */
    public ?string $lastSavedAt = null;

    /**
     * Mensaje de estado del auto-guardado
     */
    public string $autoSaveStatus = '';

    /**
     * Inicializar el auto-guardado
     * Llamar en el método mount() del componente
     *
     * @return void
     */
    public function initializeAutoSave(): void
    {
        $this->autoSaveEnabled = true;
        $this->hasPendingChanges = false;
        $this->autoSaveStatus = '';
    }

    /**
     * Disparar el auto-guardado (llamado desde el frontend con debounce)
     * Este método es llamado por Livewire después del debounce en JS
     *
     * @return void
     */
    public function triggerAutoSave(): void
    {
        if (!$this->autoSaveEnabled) {
            return;
        }

        if (!$this->hasPendingChanges) {
            return;
        }

        try {
            $this->autoSaveStatus = 'saving';
            $this->performAutoSave();
            $this->hasPendingChanges = false;
            $this->lastSavedAt = now()->toIso8601String();
            $this->autoSaveStatus = 'saved';

            // Emitir evento para notificar al frontend
            $this->dispatch('auto-saved', [
                'timestamp' => $this->lastSavedAt,
                'message' => 'Changes saved automatically',
            ]);

        } catch (\Exception $e) {
            $this->autoSaveStatus = 'error';
            Log::error('AutoSave failed', [
                'component' => static::class,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('auto-save-error', [
                'message' => 'Failed to save changes. Please try again.',
            ]);
        }
    }

    /**
     * Marcar que hay cambios pendientes
     * Llamar cuando un campo es modificado
     *
     * @return void
     */
    public function markAsChanged(): void
    {
        $this->hasPendingChanges = true;
        $this->autoSaveStatus = 'pending';
    }

    /**
     * Deshabilitar el auto-guardado temporalmente
     *
     * @return void
     */
    public function disableAutoSave(): void
    {
        $this->autoSaveEnabled = false;
    }

    /**
     * Habilitar el auto-guardado
     *
     * @return void
     */
    public function enableAutoSave(): void
    {
        $this->autoSaveEnabled = true;
    }

    /**
     * Verificar si el auto-guardado está habilitado
     *
     * @return bool
     */
    public function isAutoSaveEnabled(): bool
    {
        return $this->autoSaveEnabled;
    }

    /**
     * Obtener el delay del auto-guardado en milisegundos
     *
     * @return int
     */
    public function getAutoSaveDelay(): int
    {
        return $this->autoSaveDelay;
    }

    /**
     * Establecer el delay del auto-guardado
     *
     * @param int $delay Delay en milisegundos
     * @return void
     */
    public function setAutoSaveDelay(int $delay): void
    {
        $this->autoSaveDelay = max(500, $delay); // Mínimo 500ms
    }

    /**
     * Realizar el guardado automático
     * Este método debe ser implementado por el componente que use el trait
     *
     * @return void
     */
    abstract protected function performAutoSave(): void;

    /**
     * Obtener el estado actual del auto-guardado para mostrar en UI
     *
     * @return array
     */
    public function getAutoSaveState(): array
    {
        return [
            'enabled' => $this->autoSaveEnabled,
            'hasPendingChanges' => $this->hasPendingChanges,
            'lastSavedAt' => $this->lastSavedAt,
            'status' => $this->autoSaveStatus,
            'delay' => $this->autoSaveDelay,
        ];
    }

    /**
     * Forzar guardado inmediato (sin esperar debounce)
     *
     * @return bool
     */
    public function forceSave(): bool
    {
        if (!$this->hasPendingChanges) {
            return true;
        }

        try {
            $this->performAutoSave();
            $this->hasPendingChanges = false;
            $this->lastSavedAt = now()->toIso8601String();
            $this->autoSaveStatus = 'saved';
            return true;
        } catch (\Exception $e) {
            Log::error('Force save failed', [
                'component' => static::class,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Guardar antes de navegar a otro step
     * Útil para asegurar que los datos se guarden antes de cambiar de step
     *
     * @return bool
     */
    public function saveBeforeNavigation(): bool
    {
        return $this->forceSave();
    }
}
