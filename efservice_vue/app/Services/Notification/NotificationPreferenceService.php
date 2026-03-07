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

        foreach ($defaultCategories as $category) {
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
                'carrier_registration',
                'driver_application',
                'document_uploads',
                'system_alerts',
            ],
            'user_carrier' => [
                'driver_assignments',
                'document_status',
                'trip_updates',
                'maintenance_alerts',
                'hos_violations',
            ],
            'user_driver' => [
                'trip_assignments',
                'document_expiry',
                'license_expiry',
                'medical_expiry',
                'hos_alerts',
            ],
            default => [
                'general',
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
