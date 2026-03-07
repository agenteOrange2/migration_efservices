<?php

namespace App\Services\Driver;

use App\Models\DriverArchive;
use App\Models\MigrationRecord;
use App\Models\User;
use App\Models\UserNotificationPreference;
use App\Notifications\DriverMigrationNotification;
use App\Notifications\DriverMigrationRollbackNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Service for sending migration-related notifications.
 */
class MigrationNotificationService
{
    /**
     * Send notifications for a completed migration.
     * Only notifies system admins - carriers should not be aware of driver migrations.
     */
    public function sendMigrationNotifications(
        MigrationRecord $migrationRecord,
        DriverArchive $archive
    ): void {
        // Get system admins only (not carrier admins)
        $systemAdmins = $this->getSystemAdmins();

        // Send to system admins only
        foreach ($systemAdmins as $admin) {
            $this->sendNotification($admin, $migrationRecord, 'admin');
        }

        Log::info('Migration notifications sent to system admins', [
            'migration_record_id' => $migrationRecord->id,
            'system_admins_count' => $systemAdmins->count(),
        ]);
    }

    /**
     * Send notifications for a rollback.
     * Only notifies system admins - carriers should not be aware of driver migrations.
     */
    public function sendRollbackNotifications(
        MigrationRecord $migrationRecord,
        User $rolledBackBy
    ): void {
        // Get system admins only (not carrier admins)
        $systemAdmins = $this->getSystemAdmins();

        // Send to system admins only
        foreach ($systemAdmins as $admin) {
            $this->sendRollbackNotification($admin, $migrationRecord, 'admin');
        }

        Log::info('Rollback notifications sent to system admins', [
            'migration_record_id' => $migrationRecord->id,
            'system_admins_count' => $systemAdmins->count(),
        ]);
    }

    /**
     * Get system admin users (users with superadmin role, not carrier admins).
     */
    protected function getSystemAdmins(): \Illuminate\Support\Collection
    {
        return User::role('superadmin')->get();
    }

    /**
     * Send migration notification to a user.
     */
    protected function sendNotification(
        User $user,
        MigrationRecord $migrationRecord,
        string $recipientType
    ): void {
        try {
            // Create in-app notification
            $this->createInAppNotification($user, $migrationRecord, $recipientType);

            // Send email if enabled
            if ($this->shouldSendEmail($user, 'driver_migration')) {
                $user->notify(new DriverMigrationNotification($migrationRecord, $recipientType));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send migration notification', [
                'user_id' => $user->id,
                'migration_record_id' => $migrationRecord->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send rollback notification to a user.
     */
    protected function sendRollbackNotification(
        User $user,
        MigrationRecord $migrationRecord,
        string $recipientType
    ): void {
        try {
            // Create in-app notification
            $this->createInAppRollbackNotification($user, $migrationRecord, $recipientType);

            // Send email if enabled
            if ($this->shouldSendEmail($user, 'driver_migration')) {
                $user->notify(new DriverMigrationRollbackNotification($migrationRecord, $recipientType));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send rollback notification', [
                'user_id' => $user->id,
                'migration_record_id' => $migrationRecord->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create in-app notification for migration.
     */
    protected function createInAppNotification(
        User $user,
        MigrationRecord $migrationRecord,
        string $recipientType
    ): void {
        $driverName = $migrationRecord->driver_snapshot['personal_info']['name'] ?? 'Driver';
        $lastName = $migrationRecord->driver_snapshot['personal_info']['last_name'] ?? '';
        $fullName = trim("{$driverName} {$lastName}");

        $message = match ($recipientType) {
            'admin' => "Driver {$fullName} has been migrated from {$migrationRecord->sourceCarrier->name} to {$migrationRecord->targetCarrier->name}.",
            default => "Driver migration completed for {$fullName}.",
        };

        // Use the existing notification system if available
        if (class_exists(\App\Models\Notification::class)) {
            \App\Models\Notification::create([
                'user_id' => $user->id,
                'type' => 'driver_migration',
                'title' => 'Driver Migration',
                'message' => $message,
                'data' => [
                    'migration_record_id' => $migrationRecord->id,
                    'recipient_type' => $recipientType,
                ],
            ]);
        }
    }

    /**
     * Create in-app notification for rollback.
     */
    protected function createInAppRollbackNotification(
        User $user,
        MigrationRecord $migrationRecord,
        string $recipientType
    ): void {
        $driverName = $migrationRecord->driver_snapshot['personal_info']['name'] ?? 'Driver';
        $lastName = $migrationRecord->driver_snapshot['personal_info']['last_name'] ?? '';
        $fullName = trim("{$driverName} {$lastName}");

        $message = match ($recipientType) {
            'admin' => "Driver {$fullName} migration has been rolled back. Driver returned from {$migrationRecord->targetCarrier->name} to {$migrationRecord->sourceCarrier->name}.",
            default => "Driver migration rolled back for {$fullName}.",
        };

        if (class_exists(\App\Models\Notification::class)) {
            \App\Models\Notification::create([
                'user_id' => $user->id,
                'type' => 'driver_migration_rollback',
                'title' => 'Driver Migration Rolled Back',
                'message' => $message,
                'data' => [
                    'migration_record_id' => $migrationRecord->id,
                    'recipient_type' => $recipientType,
                ],
            ]);
        }
    }

    /**
     * Check if email should be sent based on user preferences.
     */
    protected function shouldSendEmail(User $user, string $category): bool
    {
        return $user->isNotificationEmailEnabled($category);
    }
}
