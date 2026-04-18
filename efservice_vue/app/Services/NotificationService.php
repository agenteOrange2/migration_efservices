<?php

namespace App\Services;

use App\Models\User;
use App\Models\NotificationType;
use App\Models\Notification;
use App\Models\NotificationSetting;
use App\Models\NotificationLog;
use App\Models\NotificationRecipient;
use App\Models\UserNotificationPreference;
use App\Models\Carrier;
use App\Mail\NewCarrierAdminNotification;
use App\Mail\AdminNotificationMail;
use App\Notifications\CarrierNotification;
use App\Notifications\ChannelControlledNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Notifications\Notification as BaseNotification;

class NotificationService
{
    public function notifyAdminsOfNewCarrier(User $newUser, string $message)
    {
        // Obtener el tipo de notificación para nuevo carrier
        $notificationType = NotificationType::where('name', 'new_carrier_registration')->first();
        
        // Obtener todos los admins
        $admins = User::role('superadmin')->get();

        foreach ($admins as $admin) {
            // Crear la notificación en la base de datos
            Notification::create([
                'user_id' => $admin->id,
                'notification_type_id' => $notificationType->id,
                'message' => $message,
                'sent_at' => now(),
                'is_read' => false
            ]);
        }
    }

    public function createNotificationForMultipleUsers(Collection $users, string $type, string $message)
    {
        $notificationType = NotificationType::where('name', $type)->first();
        
        if (!$notificationType) {
            return null;
        }

        foreach ($users as $user) {
            Notification::create([
                'user_id' => $user->id,
                'notification_type_id' => $notificationType->id,
                'message' => $message,
                'sent_at' => now(),
                'is_read' => false
            ]);
        }
    }
    public function createNotification(User $user, string $type, string $message)
    {
        $notificationType = NotificationType::where('name', $type)->first();
        
        if (!$notificationType) {
            return null;
        }

        return Notification::create([
            'user_id' => $user->id,
            'notification_type_id' => $notificationType->id,
            'message' => $message,
            'sent_at' => now(),
            'is_read' => false
        ]);
    }

    public function markAsRead(int $notificationId): bool
    {
        return Notification::where('id', $notificationId)
            ->update(['is_read' => true]);
    }

    public function getUnreadNotifications(int $userId)
    {
        return Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->orderBy('sent_at', 'desc')
            ->get();
    }

    /**
     * Send notification when a carrier step is completed
     *
     * @param User $user
     * @param string $step
     * @param array $data
     * @return void
     */
    public function sendStepCompletedNotification(User $user, string $step, array $data = [])
    {
        // Get all recipients (superadmins + additional recipients)
        $recipients = $this->getAllRecipientsForNotification('user_carrier');
        
        if (empty($recipients)) {
            Log::info('No recipients found for step completed notification', [
                'user_id' => $user->id,
                'step' => $step
            ]);
            return;
        }

        $this->sendCarrierNotification($user, null, 'step_completed', $step, $recipients, $data);
    }

    /**
     * Send notification when carrier registration is completed
     *
     * @param User $user
     * @param Carrier $carrier
     * @param array $data
     * @return void
     */
    public function sendRegistrationCompletedNotification(User $user, Carrier $carrier, array $data = [])
    {
        // Get all recipients (superadmins + additional recipients)
        $recipients = $this->getAllRecipientsForNotification('carrier_registered');
        
        if (empty($recipients)) {
            Log::info('No recipients found for registration completed notification', [
                'user_id' => $user->id,
                'carrier_id' => $carrier->id
            ]);
            return;
        }

        $this->sendCarrierNotification($user, $carrier, 'registration_completed', null, $recipients, $data);
    }

    /**
     * Send carrier notification email and native Laravel notifications
     *
     * @param User $user
     * @param Carrier|null $carrier
     * @param string $eventType
     * @param string|null $step
     * @param array $recipients
     * @param array $data
     * @return void
     */
    private function sendCarrierNotification(User $user, ?Carrier $carrier, string $eventType, ?string $step, array $recipients, array $data = [])
    {
        // Cargar relaciones necesarias para el email
        if (!$user->relationLoaded('carrierDetails')) {
            $user->load('carrierDetails');
        }
        
        if ($carrier && !$carrier->relationLoaded('membership')) {
            $carrier->load('membership');
        }
        
        // Create notification log
        $log = NotificationLog::create([
            'user_id' => $user->id,
            'carrier_id' => $carrier ? $carrier->id : null,
            'event_type' => $eventType,
            'step' => $step,
            'recipients' => $recipients,
            'status' => 'pending',
            'data' => $data
        ]);

        try {
            // Send email to each recipient
            foreach ($recipients as $recipient) {
                Mail::to($recipient)->queue(new NewCarrierAdminNotification($user, $carrier, $eventType, $step, $data));
            }

            // Send native Laravel notifications to admin users (for the bell icon)
            $this->sendNativeNotifications($user, $carrier, $eventType, $step, $data);

            $log->markAsSent();
            
            Log::info('Carrier notification sent successfully', [
                'log_id' => $log->id,
                'user_id' => $user->id,
                'carrier_id' => $carrier ? $carrier->id : null,
                'event_type' => $eventType,
                'step' => $step,
                'recipients_count' => count($recipients)
            ]);
        } catch (\Exception $e) {
            $log->markAsFailed($e->getMessage());
            
            Log::error('Failed to send carrier notification', [
                'log_id' => $log->id,
                'user_id' => $user->id,
                'carrier_id' => $carrier ? $carrier->id : null,
                'event_type' => $eventType,
                'step' => $step,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Send native Laravel notifications to configured recipients
     *
     * @param User $user
     * @param Carrier|null $carrier
     * @param string $eventType
     * @param string|null $step
     * @param array $data
     * @return void
     */
    private function sendNativeNotifications(User $user, ?Carrier $carrier, string $eventType, ?string $step, array $data = [])
    {
        // Get recipients based on notification type instead of all superadmins
        $recipients = NotificationRecipient::active()
            ->forNotificationType($eventType)
            ->get();
        
        // If no specific recipients configured, fall back to superadmins
        if ($recipients->isEmpty()) {
            $admins = User::role('superadmin')->get();
        } else {
            // Get users from recipients configuration
            $admins = collect();
            foreach ($recipients as $recipient) {
                if ($recipient->user_id) {
                    $user_obj = User::find($recipient->user_id);
                    if ($user_obj) {
                        $admins->push($user_obj);
                    }
                } else {
                    // For email-only recipients, try to find user by email
                    $user_obj = User::where('email', $recipient->email)->first();
                    if ($user_obj) {
                        $admins->push($user_obj);
                    }
                }
            }
        }
        
        // Prepare notification content based on event type
        $title = '';
        $message = '';
        $category = 'system_alerts';
        $icon = 'Bell';
        $url = route('admin.users.edit', $user);

        switch ($eventType) {
            case 'step_completed':
                $title = match ($step) {
                    'step1' => 'Carrier User Registration Started',
                    'step2' => 'Carrier Company Information Completed',
                    'step3' => 'Carrier Membership Selected',
                    default => 'Carrier Registration Step Completed',
                };
                $message = match ($step) {
                    'step1' => "{$user->name} created a carrier user account and completed the initial registration step.",
                    'step2' => $carrier
                        ? "{$user->name} completed the company information step for {$carrier->name}."
                        : "{$user->name} completed the company information step.",
                    'step3' => $carrier
                        ? "{$user->name} selected a membership and completed step 3 for {$carrier->name}."
                        : "{$user->name} completed the membership selection step.",
                    default => "{$user->name} completed a carrier registration step.",
                };
                $category = 'carrier_registration';
                $icon = 'Building2';
                $url = $carrier
                    ? route('admin.carriers.show', $carrier)
                    : route('admin.users.edit', $user);
                break;
            case 'registration_completed':
                $title = 'Carrier Registration Completed';
                $message = $carrier
                    ? "{$carrier->name} completed the full carrier registration workflow."
                    : "{$user->name} completed the full carrier registration workflow.";
                $category = 'carrier_registration';
                $icon = 'CircleCheckBig';
                $url = $carrier
                    ? route('admin.carriers.show', $carrier)
                    : route('admin.users.edit', $user);
                break;
            case 'user_carrier':
                $title = 'New Carrier User Activity';
                $message = "New carrier activity was recorded for {$user->name}.";
                $category = 'carrier_registration';
                $icon = 'UserRoundPlus';
                $url = route('admin.users.edit', $user);
                break;
            default:
                $title = 'New System Notification';
                $message = "New activity was detected for {$user->name}.";
        }

        // Send notification to each configured recipient
        foreach ($admins as $admin) {
            if (! $admin->isNotificationInAppEnabled($category)) {
                continue;
            }

            $admin->notify(new CarrierNotification(
                $title,
                $message,
                'info',
                [
                    'category' => $category,
                    'icon' => $icon,
                    'url' => $url,
                    'user_id' => $user->id,
                    'carrier_id' => $carrier ? $carrier->id : null,
                    'event_type' => $eventType,
                    'step' => $step,
                    'data' => $data
                ]
            ));
        }
        
        // Send email notification to superadmin (frontend@kuiraweb.com)
        $this->sendEmailToSuperadmin($user, $carrier, $eventType, $step, $title, $message, $data);
    }
    
    /**
     * Send email notification to superadmin
     *
     * @param User $user
     * @param Carrier|null $carrier
     * @param string $eventType
     * @param string|null $step
     * @param string $title
     * @param string $message
     * @param array $data
     * @return void
     */
    private function sendEmailToSuperadmin(User $user, ?Carrier $carrier, string $eventType, ?string $step, string $title, string $message, array $data = [])
    {
        try {
            $adminEmail = config('app.admin_notification_email', env('ADMIN_NOTIFICATION_EMAIL', 'frontend@kuiraweb.com'));
            
            Mail::to($adminEmail)->queue(new AdminNotificationMail(
                $user,
                $carrier,
                $eventType,
                $step,
                $title,
                $message,
                $data
            ));
            
            Log::info('Admin email notification sent', [
                'admin_email' => $adminEmail,
                'event_type' => $eventType,
                'user_id' => $user->id,
                'title' => $title
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to send admin email notification', [
                'error' => $e->getMessage(),
                'event_type' => $eventType,
                'user_id' => $user->id
            ]);
        }
    }

    /**
     * Get all recipients for a notification type (superadmins + additional recipients)
     *
     * @param string $notificationType
     * @return array
     */
    private function getAllRecipientsForNotification(string $notificationType): array
    {
        $recipients = [];
        
        // Always include superadmin emails
        $superadmins = User::role('superadmin')->get();
        foreach ($superadmins as $admin) {
            $recipients[] = $admin->email;
        }
        
        // Get additional recipients from notification_recipients table
        $additionalRecipients = NotificationRecipient::active()
            ->forNotificationType($notificationType)
            ->get();
            
        foreach ($additionalRecipients as $recipient) {
            $email = $recipient->email;
            if ($email && !in_array($email, $recipients)) {
                $recipients[] = $email;
            }
        }
        
        return array_unique($recipients);
    }

    /**
     * Get notification settings for management
     *
     * @return Collection
     */
    public function getNotificationSettings()
    {
        return NotificationSetting::all();
    }

    /**
     * Update notification setting
     *
     * @param string $eventType
     * @param string|null $step
     * @param array $recipients
     * @param bool $isActive
     * @return NotificationSetting
     */
    public function updateNotificationSetting(string $eventType, ?string $step, array $recipients, bool $isActive = true)
    {
        return NotificationSetting::updateOrCreateSetting($eventType, $step, $recipients, $isActive);
    }

    /**
     * Get notification logs with filters
     *
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getNotificationLogs(array $filters = [])
    {
        $query = NotificationLog::with(['user', 'carrier']);

        if (isset($filters['event_type'])) {
            $query->where('event_type', $filters['event_type']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return $query->orderBy('created_at', 'desc')->paginate(20);
    }

    /**
     * Obtener estadísticas de notificaciones
     */
    public function getNotificationStats(): array
    {
        $total = NotificationLog::count();
        $sent = NotificationLog::where('status', 'sent')->count();
        $failed = NotificationLog::where('status', 'failed')->count();
        $pending = NotificationLog::where('status', 'pending')->count();
        $activeSettings = NotificationSetting::where('is_active', true)->count();

        return [
            'total' => $total,
            'sent' => $sent,
            'failed' => $failed,
            'pending' => $pending,
            'active_settings' => $activeSettings,
            'success_rate' => $total > 0 ? round(($sent / $total) * 100, 2) : 0
        ];
    }

    /**
     * Obtener detalles de un log específico
     */
    public function getNotificationLogDetails(int $logId): ?NotificationLog
    {
        return NotificationLog::find($logId);
    }

    /**
     * Reintentar una notificación fallida
     */
    public function retryFailedNotification(int $logId): bool
    {
        $log = NotificationLog::find($logId);
        
        if (!$log || $log->status !== 'failed') {
            return false;
        }

        try {
            // Decodificar los datos del log
            $data = json_decode($log->data, true) ?? [];
            $recipients = json_decode($log->recipients, true) ?? [];

            // Reenviar la notificación
            $this->sendCarrierNotification(
                $log->event_type,
                (object) ['id' => $log->user_id, 'name' => 'Usuario', 'email' => 'user@example.com'],
                (object) ['id' => $log->carrier_id, 'company_name' => 'Carrier'],
                $data,
                $recipients
            );

            return true;
        } catch (\Exception $e) {
            \Log::error('Error al reintentar notificación: ' . $e->getMessage());
            return false;
        }
    }

    // ============================================
    // NUEVOS MÉTODOS PARA SOPORTE DE PREFERENCIAS
    // ============================================

    /**
     * Send notification respecting user preferences
     *
     * @param User $user
     * @param BaseNotification $notification
     * @param string $category
     * @return bool
     */
    public function sendWithPreferences(User $user, BaseNotification $notification, string $category): bool
    {
        try {
            // Verificar si la categoría es crítica (siempre se envía)
            $isCritical = UserNotificationPreference::isCriticalCategory($category);
            
            // Verificar preferencias del usuario
            $inAppEnabled = $isCritical || $user->isNotificationInAppEnabled($category);
            $emailEnabled = $isCritical || $user->isNotificationEmailEnabled($category);
            
            if (!$inAppEnabled && !$emailEnabled) {
                Log::info('Notification skipped due to user preferences', [
                    'user_id' => $user->id,
                    'category' => $category,
                    'notification' => get_class($notification)
                ]);
                return false;
            }

            // Modificar los canales de la notificación según preferencias reales
            $channels = [];
            if ($inAppEnabled) {
                $channels[] = 'database';
            }
            if ($emailEnabled && method_exists($notification, 'toMail')) {
                $channels[] = 'mail';
            }

            if (empty($channels)) {
                Log::info('Notification skipped because no channels are enabled', [
                    'user_id' => $user->id,
                    'category' => $category,
                    'notification' => get_class($notification),
                ]);
                return false;
            }

            // Enviar notificación
            $user->notify(new ChannelControlledNotification($notification, $channels));

            Log::info('Notification sent with preferences', [
                'user_id' => $user->id,
                'category' => $category,
                'notification' => get_class($notification),
                'channels' => $channels
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send notification with preferences', [
                'user_id' => $user->id,
                'category' => $category,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send notification to multiple users with preferences
     *
     * @param Collection $users
     * @param BaseNotification $notification
     * @param string $category
     * @return void
     */
    public function sendToManyWithPreferences(Collection $users, BaseNotification $notification, string $category): void
    {
        foreach ($users as $user) {
            $this->sendWithPreferences($user, $notification, $category);
        }
    }

    /**
     * Check if user has category enabled for a specific channel
     *
     * @param User $user
     * @param string $category
     * @param string $channel 'database' or 'mail'
     * @return bool
     */
    public function isNotificationEnabled(User $user, string $category, string $channel = 'database'): bool
    {
        // Las categorías críticas siempre están habilitadas
        if (UserNotificationPreference::isCriticalCategory($category)) {
            return true;
        }

        $preference = $user->getNotificationPreference($category);
        
        if (!$preference) {
            // Por defecto, todas las notificaciones están habilitadas
            return true;
        }

        return $channel === 'mail' ? $preference->email_enabled : $preference->in_app_enabled;
    }

    /**
     * Get notifications for user with filters
     *
     * @param User $user
     * @param array $filters ['type' => string, 'status' => 'read'|'unread'|'all', 'category' => string]
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getNotificationsForUser(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $user->notifications();

        // Filtrar por estado de lectura
        if (isset($filters['status'])) {
            switch ($filters['status']) {
                case 'read':
                    $query->whereNotNull('read_at');
                    break;
                case 'unread':
                    $query->whereNull('read_at');
                    break;
                // 'all' no necesita filtro
            }
        }

        // Filtrar por tipo de notificación
        if (isset($filters['type']) && !empty($filters['type'])) {
            $query->where('type', 'like', '%' . $filters['type'] . '%');
        }

        // Filtrar por categoría (buscando en el JSON data)
        if (isset($filters['category']) && !empty($filters['category'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereJsonContains('data->category', $filters['category'])
                  ->orWhere('data->type', $filters['category']);
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get notification categories for a specific role
     *
     * @param string $role
     * @return array
     */
    public function getCategoriesForRole(string $role): array
    {
        return UserNotificationPreference::getCategoriesForRole($role);
    }

    /**
     * Get notification types for filtering (based on user's notifications)
     *
     * @param User $user
     * @return array
     */
    public function getNotificationTypesForUser(User $user): array
    {
        $types = $user->notifications()
            ->select('type')
            ->distinct()
            ->pluck('type')
            ->map(function ($type) {
                $className = class_basename($type);
                return [
                    'id' => $type,
                    'name' => $this->formatNotificationTypeName($className)
                ];
            })
            ->toArray();

        return $types;
    }

    /**
     * Format notification type name for display
     *
     * @param string $className
     * @return string
     */
    private function formatNotificationTypeName(string $className): string
    {
        // Remove 'Notification' suffix and convert to readable format
        $name = str_replace('Notification', '', $className);
        $name = preg_replace('/([a-z])([A-Z])/', '$1 $2', $name);
        return trim($name);
    }

    /**
     * Mark notification as read
     *
     * @param User $user
     * @param string $notificationId
     * @return bool
     */
    public function markNotificationAsRead(User $user, string $notificationId): bool
    {
        $notification = $user->notifications()->find($notificationId);
        
        if ($notification) {
            $notification->markAsRead();
            return true;
        }
        
        return false;
    }

    /**
     * Mark notification as unread
     *
     * @param User $user
     * @param string $notificationId
     * @return bool
     */
    public function markNotificationAsUnread(User $user, string $notificationId): bool
    {
        $notification = $user->notifications()->find($notificationId);
        
        if ($notification) {
            $notification->update(['read_at' => null]);
            return true;
        }
        
        return false;
    }

    /**
     * Mark all notifications as read for a user
     *
     * @param User $user
     * @return int Number of notifications marked as read
     */
    public function markAllAsRead(User $user): int
    {
        return $user->unreadNotifications()->update(['read_at' => now()]);
    }

    /**
     * Delete a notification
     *
     * @param User $user
     * @param string $notificationId
     * @return bool
     */
    public function deleteNotification(User $user, string $notificationId): bool
    {
        $notification = $user->notifications()->find($notificationId);
        
        if ($notification) {
            $notification->delete();
            return true;
        }
        
        return false;
    }

    /**
     * Delete all notifications for a user (optionally filtered)
     *
     * @param User $user
     * @param array $filters
     * @return int Number of notifications deleted
     */
    public function deleteAllNotifications(User $user, array $filters = []): int
    {
        $query = $user->notifications();

        if (isset($filters['status'])) {
            switch ($filters['status']) {
                case 'read':
                    $query->whereNotNull('read_at');
                    break;
                case 'unread':
                    $query->whereNull('read_at');
                    break;
            }
        }

        if (isset($filters['type']) && !empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return $query->delete();
    }

    /**
     * Get unread notification count for a user
     *
     * @param User $user
     * @return int
     */
    public function getUnreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    /**
     * Create default notification preferences for a user
     *
     * @param User $user
     * @return void
     */
    public function createDefaultPreferences(User $user): void
    {
        UserNotificationPreference::createDefaultsForUser($user);
    }

    /**
     * Update notification preference for a user
     *
     * @param User $user
     * @param string $category
     * @param bool $inAppEnabled
     * @param bool $emailEnabled
     * @return UserNotificationPreference
     */
    public function updatePreference(User $user, string $category, bool $inAppEnabled, bool $emailEnabled): UserNotificationPreference
    {
        // No permitir deshabilitar categorías críticas
        if (UserNotificationPreference::isCriticalCategory($category)) {
            $inAppEnabled = true;
            $emailEnabled = true;
        }

        return UserNotificationPreference::updateOrCreate(
            ['user_id' => $user->id, 'category' => $category],
            ['in_app_enabled' => $inAppEnabled, 'email_enabled' => $emailEnabled]
        );
    }

    /**
     * Get all preferences for a user
     *
     * @param User $user
     * @return Collection
     */
    public function getPreferencesForUser(User $user): Collection
    {
        // Determinar el rol del usuario
        $role = $user->hasRole('carrier') || $user->hasRole('user_carrier') ? 'carrier' : 
               (($user->hasRole('driver') || $user->hasRole('user_driver')) ? 'driver' : null);
        
        if (!$role) {
            return collect();
        }

        $categories = $this->getCategoriesForRole($role);
        $existingPreferences = $user->notificationPreferences()->get()->keyBy('category');
        
        $preferences = collect();
        
        foreach ($categories as $key => $label) {
            $existing = $existingPreferences->get($key);
            
            $preferences->push([
                'category' => $key,
                'label' => $label,
                'in_app_enabled' => $existing ? $existing->in_app_enabled : true,
                'email_enabled' => $existing ? $existing->email_enabled : true,
                'is_critical' => UserNotificationPreference::isCriticalCategory($key),
            ]);
        }
        
        return $preferences;
    }
}
