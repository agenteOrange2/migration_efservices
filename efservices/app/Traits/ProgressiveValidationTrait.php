<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

/**
 * Progressive Validation Trait
 * 
 * Proporciona validación progresiva para componentes Livewire de registro de drivers.
 * Permite avanzar con campos opcionales vacíos mientras valida solo los requeridos.
 * 
 * Implementa Requirements 3.1 y 3.2:
 * - 3.1: Validar solo campos requeridos al avanzar
 * - 3.2: Permitir avanzar con campos opcionales vacíos mostrando indicador visual
 */
trait ProgressiveValidationTrait
{
    /**
     * Campos opcionales que tienen datos incompletos
     */
    public array $incompleteOptionalFields = [];

    /**
     * Indica si hay campos opcionales incompletos
     */
    public bool $hasIncompleteOptionalFields = false;

    /**
     * Obtener reglas de validación solo para campos requeridos
     * Los componentes deben implementar este método
     *
     * @return array
     */
    abstract protected function getRequiredRules(): array;

    /**
     * Obtener reglas de validación para campos opcionales
     * Los componentes deben implementar este método
     *
     * @return array
     */
    abstract protected function getOptionalRules(): array;

    /**
     * Obtener nombres legibles de los campos para mensajes
     *
     * @return array
     */
    protected function getFieldNames(): array
    {
        return [];
    }

    /**
     * Validar solo campos requeridos para permitir avanzar
     * Requirement 3.1: Validar solo campos requeridos al avanzar
     *
     * @return bool True si la validación pasa
     */
    public function validateRequiredFields(): bool
    {
        try {
            $this->validate($this->getRequiredRules());
            return true;
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        }
    }

    /**
     * Validar campos opcionales sin bloquear el avance
     * Requirement 3.2: Permitir avanzar con campos opcionales vacíos
     *
     * @return array Lista de campos opcionales incompletos
     */
    public function checkOptionalFields(): array
    {
        $this->incompleteOptionalFields = [];
        $optionalRules = $this->getOptionalRules();
        $fieldNames = $this->getFieldNames();

        foreach ($optionalRules as $field => $rules) {
            $value = $this->getFieldValue($field);
            
            if ($this->isFieldEmpty($value)) {
                $displayName = $fieldNames[$field] ?? $this->humanizeFieldName($field);
                $this->incompleteOptionalFields[] = [
                    'field' => $field,
                    'name' => $displayName,
                ];
            }
        }

        $this->hasIncompleteOptionalFields = !empty($this->incompleteOptionalFields);

        Log::info('Optional fields check completed', [
            'component' => static::class,
            'incomplete_count' => count($this->incompleteOptionalFields),
            'fields' => array_column($this->incompleteOptionalFields, 'field'),
        ]);

        return $this->incompleteOptionalFields;
    }

    /**
     * Validación progresiva completa para avanzar al siguiente step
     * Valida requeridos (bloquea si falla) y opcionales (solo informa)
     *
     * @return bool True si puede avanzar (campos requeridos válidos)
     */
    public function validateProgressively(): bool
    {
        // Primero validar campos requeridos (esto puede lanzar excepción)
        $this->validateRequiredFields();

        // Luego verificar campos opcionales (solo informativo)
        $this->checkOptionalFields();

        // Si hay campos opcionales incompletos, mostrar notificación
        if ($this->hasIncompleteOptionalFields) {
            $this->notifyIncompleteOptionalFields();
        }

        return true;
    }

    /**
     * Notificar al usuario sobre campos opcionales incompletos
     */
    protected function notifyIncompleteOptionalFields(): void
    {
        $count = count($this->incompleteOptionalFields);
        $fieldsList = array_column($this->incompleteOptionalFields, 'name');
        
        $message = $count === 1
            ? "Optional field incomplete: {$fieldsList[0]}. You can complete it later."
            : "There are {$count} optional fields incomplete. You can complete them later.";

        $this->dispatch('notify', [
            'type' => 'info',
            'message' => $message,
        ]);
    }

    /**
     * Obtener el valor de un campo, soportando notación de punto
     *
     * @param string $field
     * @return mixed
     */
    protected function getFieldValue(string $field): mixed
    {
        // Soportar notación de punto para arrays (ej: 'work_histories.*.company')
        if (str_contains($field, '.')) {
            $parts = explode('.', $field);
            $value = $this;
            
            foreach ($parts as $part) {
                if ($part === '*') {
                    // Para wildcards, verificar si el array padre tiene elementos
                    return $value;
                }
                
                if (is_array($value)) {
                    $value = $value[$part] ?? null;
                } elseif (is_object($value)) {
                    $value = $value->{$part} ?? null;
                } else {
                    return null;
                }
            }
            
            return $value;
        }

        return $this->{$field} ?? null;
    }

    /**
     * Verificar si un valor está vacío
     *
     * @param mixed $value
     * @return bool
     */
    protected function isFieldEmpty(mixed $value): bool
    {
        if (is_null($value)) {
            return true;
        }

        if (is_string($value) && trim($value) === '') {
            return true;
        }

        if (is_array($value) && empty($value)) {
            return true;
        }

        return false;
    }

    /**
     * Convertir nombre de campo a formato legible
     *
     * @param string $field
     * @return string
     */
    protected function humanizeFieldName(string $field): string
    {
        // Remover prefijos comunes
        $field = preg_replace('/^(owner_|third_party_|company_|vehicle_)/', '', $field);
        
        // Convertir snake_case a Title Case
        return ucwords(str_replace('_', ' ', $field));
    }

    /**
     * Obtener resumen de validación para mostrar en UI
     *
     * @return array
     */
    public function getValidationSummary(): array
    {
        return [
            'hasIncompleteOptional' => $this->hasIncompleteOptionalFields,
            'incompleteOptionalFields' => $this->incompleteOptionalFields,
            'incompleteCount' => count($this->incompleteOptionalFields),
        ];
    }

    /**
     * Limpiar estado de validación
     */
    public function clearValidationState(): void
    {
        $this->incompleteOptionalFields = [];
        $this->hasIncompleteOptionalFields = false;
    }
}
