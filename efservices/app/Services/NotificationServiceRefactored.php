<?php

namespace App\Services;

use App\Models\User;
use App\Models\Carrier;
use App\Services\Notification\EmailNotificationService;
use App\Services\Notification\DatabaseNotificationService;
use App\Services\Notification\NotificationPreferenceService;
use App\Services\Notification\NotificationLogService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Orchestrator service for all notification-related operations
 *
 * This service coordinates between specialized notification services:
 * - EmailNotificationService: Handles email sending
 * - DatabaseNotificationService: Manages database notifications
 * - NotificationPreferenceService: Manages user preferences
 * - NotificationLogService: Handles logging and statistics
 */
class NotificationServiceRefactored
{
    public function __construct(
        private EmailNotificationService $emailService,
        private DatabaseNotificationService $databaseService,
        private NotificationPreferenceService $preferenceService,
        private NotificationLogService $logService,
    ) {}

    /**
     * Notify admins of new carrier registration
     */
    public function notifyAdminsOfNewCarrier(User $newUser, string $message): void
    {
        try {
            // Create database notifications for admins
            $admins = User::role('superadmin')->get();
            $this->databaseService->createNotificationForMultipleUsers(
                $admins,
                'new_carrier_registration',
                $message
            );

            // Send emails to admins (if they have email notifications enabled)
            foreach ($admins as $admin) {
                if ($this->preferenceService->isNotificationEnabled($admin, 'carrier_registration', 'email')) {
                    $this->emailService->sendNotificationEmail(
                        $admin,
                        'New Carrier Registration',
                        $message
                    );
                }
            }

            // Log the notification
            foreach ($admins as $admin) {
                $this->logService->logSuccess($admin, 'new_carrier_registration', 'database+email');
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify admins of new carrier', [
                'user_id' => $newUser->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send notification respecting user preferences
     */
    public function sendWithPreferences(User $user, BaseNotification $notification, string $category): bool
    {
        $sent = false;

        try {
            // Check database notification preference
            if ($this->preferenceService->isNotificationEnabled($user, $category, 'database')) {
                $user->notify($notification);
                $sent = true;

                $this->logService->logSuccess($user, $category, 'database', [
                    'notification_class' => get_class($notification),
                ]);
            }

            // Check email notification preference
            if ($this->preferenceService->isNotificationEnabled($user, $category, 'email')) {
                // The notification itself should handle email via mail channel
                $sent = true;
            }

            return $sent;
        } catch (\Exception $e) {
            $this->logService->logFailure($user, $category, 'database+email', $e->getMessage());
            Log::error('Failed to send notification with preferences', [
                'user_id' => $user->id,
                'category' => $category,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send notification to multiple users respecting preferences
     */
    public function sendToManyWithPreferences(Collection $users, BaseNotification $notification, string $category): void
    {
        foreach ($users as $user) {
            $this->sendWithPreferences($user, $notification, $category);
        }
    }

    /**
     * Send carrier step completed notification
     */
    public function sendStepCompletedNotification(User $user, string $step, array $data = []): void
    {
        $carrier = $user->carrierDetails?->carrier;

        if (!$carrier) {
            return;
        }

        try {
            // Send email notification
            if ($this->preferenceService->isNotificationEnabled($user, 'carrier_registration', 'email')) {
                $this->emailService->sendCarrierRegistrationEmail(
                    $carrier,
                    'step_completed',
                    $step,
                    $data
                );
            }

            // Create database notification
            if ($this->preferenceService->isNotificationEnabled($user, 'carrier_registration', 'database')) {
                $message = "Step {$step} completed successfully";
                $this->databaseService->createNotification($user, 'carrier_step_completed', $message);
            }

            $this->logService->logSuccess($user, 'step_completed', 'database+email', ['step' => $step]);
        } catch (\Exception $e) {
            $this->logService->logFailure($user, 'step_completed', 'database+email', $e->getMessage(), ['step' => $step]);
            Log::error('Failed to send step completed notification', [
                'user_id' => $user->id,
                'step' => $step,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send registration completed notification
     */
    public function sendRegistrationCompletedNotification(User $user, Carrier $carrier, array $data = []): void
    {
        try {
            // Send email
            if ($this->preferenceService->isNotificationEnabled($user, 'carrier_registration', 'email')) {
                $this->emailService->sendCarrierRegistrationEmail(
                    $carrier,
                    'registration_completed',
                    null,
                    $data
                );
            }

            // Create database notification
            if ($this->preferenceService->isNotificationEnabled($user, 'carrier_registration', 'database')) {
                $message = "Carrier registration completed successfully";
                $this->databaseService->createNotification($user, 'carrier_registration_completed', $message);
            }

            $this->logService->logSuccess($user, 'registration_completed', 'database+email');
        } catch (\Exception $e) {
            $this->logService->logFailure($user, 'registration_completed', 'database+email', $e->getMessage());
            Log::error('Failed to send registration completed notification', [
                'user_id' => $user->id,
                'carrier_id' => $carrier->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // Delegation methods to specialized services

    public function createNotification(User $user, string $type, string $message)
    {
        return $this->databaseService->createNotification($user, $type, $message);
    }

    public function createNotificationForMultipleUsers(Collection $users, string $type, string $message)
    {
        return $this->databaseService->createNotificationForMultipleUsers($users, $type, $message);
    }

    public function markAsRead(int $notificationId): bool
    {
        return $this->databaseService->markAsRead($notificationId);
    }

    public function markNotificationAsUnread(User $user, string $notificationId): bool
    {
        return $this->databaseService->markAsUnread($user, $notificationId);
    }

    public function markAllAsRead(User $user): int
    {
        return $this->databaseService->markAllAsRead($user);
    }

    public function getUnreadNotifications(int $userId)
    {
        return $this->databaseService->getUnreadNotifications($userId);
    }

    public function getUnreadCount(User $user): int
    {
        return $this->databaseService->getUnreadCount($user);
    }

    public function getNotificationsForUser(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->databaseService->getNotificationsForUser($user, $filters, $perPage);
    }

    public function deleteNotification(User $user, string $notificationId): bool
    {
        return $this->databaseService->deleteNotification($user, $notificationId);
    }

    public function deleteAllNotifications(User $user, array $filters = []): int
    {
        return $this->databaseService->deleteAllNotifications($user, $filters);
    }

    public function isNotificationEnabled(User $user, string $category, string $channel = 'database'): bool
    {
        return $this->preferenceService->isNotificationEnabled($user, $category, $channel);
    }

    public function getPreferencesForUser(User $user): Collection
    {
        return $this->preferenceService->getPreferencesForUser($user);
    }

    public function updatePreference(User $user, string $category, bool $inAppEnabled, bool $emailEnabled)
    {
        return $this->preferenceService->updatePreference($user, $category, $inAppEnabled, $emailEnabled);
    }

    public function createDefaultPreferences(User $user): void
    {
        $this->preferenceService->createDefaultPreferences($user);
    }

    public function getCategoriesForRole(string $role): array
    {
        return $this->preferenceService->getCategoriesForRole($role);
    }

    public function getNotificationTypesForUser(User $user): array
    {
        return $this->preferenceService->getNotificationTypesForUser($user);
    }

    public function getNotificationLogs(array $filters = [])
    {
        return $this->logService->getNotificationLogs($filters);
    }

    public function getNotificationLogDetails(int $logId)
    {
        return $this->logService->getNotificationLogDetails($logId);
    }

    public function getNotificationStats(): array
    {
        return $this->logService->getNotificationStats();
    }

    public function retryFailedNotification(int $logId): bool
    {
        return $this->logService->retryFailedNotification($logId);
    }
}
