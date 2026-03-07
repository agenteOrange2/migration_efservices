<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'category',
        'in_app_enabled',
        'email_enabled',
    ];

    protected $casts = [
        'in_app_enabled' => 'boolean',
        'email_enabled' => 'boolean',
    ];

    // Categorías para Carrier
    const CARRIER_CATEGORIES = [
        'driver_registration' => 'Driver Registration',
        'driver_documents' => 'Driver Documents',
        'driver_compliance' => 'Driver Compliance (Licenses, Medical)',
        'driver_training' => 'Driver Training',
        'vehicle_documents' => 'Vehicle Documents',
        'vehicle_compliance' => 'Vehicle Compliance (Registration, Insurance)',
        'vehicle_maintenance' => 'Vehicle Maintenance',
        'vehicle_repairs' => 'Emergency Repairs',
        'hos_violations' => 'HOS Violations',
        'hos_limits' => 'HOS Limit Warnings',
        'trips' => 'Trip Updates',
        'accidents' => 'Accidents & Inspections',
        'messages' => 'Messages',
    ];

    // Categorías para Driver
    const DRIVER_CATEGORIES = [
        'personal_documents' => 'My Documents',
        'personal_compliance' => 'My Compliance (License, Medical)',
        'training' => 'Training Assignments',
        'vehicle_assignment' => 'Vehicle Assignments',
        'hos_limits' => 'HOS Limit Warnings',
        'hos_violations' => 'HOS Violations',
        'maintenance' => 'Vehicle Maintenance',
        'repairs' => 'Emergency Repairs',
        'messages' => 'Messages',
    ];

    // Categorías críticas que no se pueden deshabilitar
    const CRITICAL_CATEGORIES = [
        'hos_violations',
        'accidents',
    ];

    /**
     * Relación con el usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope para obtener preferencias de un usuario
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope para obtener preferencias de una categoría
     */
    public function scopeForCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope para obtener preferencias con in_app habilitado
     */
    public function scopeInAppEnabled($query)
    {
        return $query->where('in_app_enabled', true);
    }

    /**
     * Scope para obtener preferencias con email habilitado
     */
    public function scopeEmailEnabled($query)
    {
        return $query->where('email_enabled', true);
    }

    /**
     * Verificar si una categoría es crítica
     */
    public static function isCriticalCategory(string $category): bool
    {
        return in_array($category, self::CRITICAL_CATEGORIES);
    }

    /**
     * Obtener categorías según el rol
     */
    public static function getCategoriesForRole(string $role): array
    {
        return match ($role) {
            'carrier' => self::CARRIER_CATEGORIES,
            'driver' => self::DRIVER_CATEGORIES,
            default => [],
        };
    }

    /**
     * Crear preferencias por defecto para un usuario
     */
    public static function createDefaultsForUser(User $user): void
    {
        $role = ($user->hasRole('carrier') || $user->hasRole('user_carrier')) ? 'carrier' : 
               (($user->hasRole('driver') || $user->hasRole('user_driver')) ? 'driver' : null);
        
        if (!$role) {
            return;
        }

        $categories = self::getCategoriesForRole($role);
        
        foreach (array_keys($categories) as $category) {
            self::firstOrCreate(
                ['user_id' => $user->id, 'category' => $category],
                ['in_app_enabled' => true, 'email_enabled' => true]
            );
        }
    }
}
