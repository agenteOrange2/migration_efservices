<?php

namespace App\Services\Notification;

use App\Models\User;
use App\Models\UserNotificationPreference;
use Illuminate\Database\Eloquent\Collection;

class NotificationPreferenceService
{
    /**
     * Check if notification is enabled for user
     */
    public function isNotificationEnabled(User $user, string $category, string $channel = 'database'): bool
    {
        $preference = UserNotificationPreference::where('user_id', $user->id)
            ->where('category', $category)
            ->first();

        if (!$preference) {
            // Default: notifications enabled
            return true;
        }

        return match ($channel) {
            'database' => $preference->in_app_enabled ?? true,
            'email' => $preference->email_enabled ?? true,
            default => true,
        };
    }

    /**
     * Get preferences for user
     */
    public function getPreferencesForUser(User $user): Collection
    {
        return UserNotificationPreference::where('user_id', $user->id)
            ->orderBy('category')
            ->get();
    }

    /**
     * Update preference for user
     */
    public function updatePreference(User $user, string $category, bool $inAppEnabled, bool $emailEnabled): UserNotificationPreference
    {
        return UserNotificationPreference::updateOrCreate(
            [
                'user_id' => $user->id,
                'category' => $category,
            ],
            [
                'in_app_enabled' => $inAppEnabled,
                'email_enabled' => $emailEnabled,
            ]
        );
    }

    /**
     * Create default preferences for new user
     */
    public function createDefaultPreferences(User $user): void
    {
        $defaultCategories = $this->getDefaultCategoriesForRole($user->getRoleNames()->first() ?? 'user');

        foreach (array_keys($defaultCategories) as $category) {
            UserNotificationPreference::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'category' => $category,
                ],
                [
                    'in_app_enabled' => true,
                    'email_enabled' => true,
                ]
            );
        }
    }

    /**
     * Get categories for role
     */
    public function getCategoriesForRole(string $role): array
    {
        return match ($role) {
            'superadmin', 'admin' => [
                'carrier_registration' => 'Carrier Registration',
                'driver_registration' => 'Driver Registration',
                'driver_documents' => 'Driver Documents',
                'driver_compliance' => 'Driver Compliance',
                'vehicle_documents' => 'Vehicle Documents',
                'vehicle_compliance' => 'Vehicle Compliance',
                'driver_training' => 'Driver Training',
                'system_alerts' => 'System Alerts',
            ],
            'user_carrier' => [
                'driver_registration' => 'Driver Registration',
                'driver_documents' => 'Driver Documents',
                'driver_compliance' => 'Driver Compliance',
                'driver_training' => 'Driver Training',
                'vehicle_documents' => 'Vehicle Documents',
                'vehicle_compliance' => 'Vehicle Compliance',
                'vehicle_maintenance' => 'Vehicle Maintenance',
                'vehicle_repairs' => 'Emergency Repairs',
                'hos_violations' => 'HOS Violations',
                'trips' => 'Trip Updates',
                'messages' => 'Messages',
            ],
            'user_driver' => [
                'personal_documents' => 'My Documents',
                'personal_compliance' => 'My Compliance',
                'training' => 'Training Assignments',
                'vehicle_assignment' => 'Vehicle Assignments',
                'hos_limits' => 'HOS Limit Warnings',
                'hos_violations' => 'HOS Violations',
                'maintenance' => 'Vehicle Maintenance',
                'repairs' => 'Emergency Repairs',
                'messages' => 'Messages',
            ],
            default => [
                'general' => 'General',
            ],
        };
    }

    /**
     * Get notification types for user based on role
     */
    public function getNotificationTypesForUser(User $user): array
    {
        $role = $user->getRoleNames()->first() ?? 'user';
        return $this->getCategoriesForRole($role);
    }

    /**
     * Enable all notifications for user
     */
    public function enableAllNotifications(User $user): int
    {
        return UserNotificationPreference::where('user_id', $user->id)
            ->update([
                'in_app_enabled' => true,
                'email_enabled' => true,
            ]);
    }

    /**
     * Disable all notifications for user
     */
    public function disableAllNotifications(User $user): int
    {
        return UserNotificationPreference::where('user_id', $user->id)
            ->update([
                'in_app_enabled' => false,
                'email_enabled' => false,
            ]);
    }

    /**
     * Get default categories for role
     */
    private function getDefaultCategoriesForRole(string $role): array
    {
        return $this->getCategoriesForRole($role);
    }
}
