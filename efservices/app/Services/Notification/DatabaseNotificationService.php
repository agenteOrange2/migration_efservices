<?php

namespace App\Services\Notification;

use App\Models\User;
use App\Models\Notification;
use App\Models\NotificationType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DatabaseNotificationService
{
    /**
     * Create notification for single user
     */
    public function createNotification(User $user, string $type, string $message): ?Notification
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

    /**
     * Create notifications for multiple users
     */
    public function createNotificationForMultipleUsers(Collection $users, string $type, string $message): int
    {
        $notificationType = NotificationType::where('name', $type)->first();

        if (!$notificationType) {
            return 0;
        }

        $created = 0;
        foreach ($users as $user) {
            Notification::create([
                'user_id' => $user->id,
                'notification_type_id' => $notificationType->id,
                'message' => $message,
                'sent_at' => now(),
                'is_read' => false
            ]);
            $created++;
        }

        return $created;
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(int $notificationId): bool
    {
        return Notification::where('id', $notificationId)
            ->update(['is_read' => true]) > 0;
    }

    /**
     * Mark notification as unread
     */
    public function markAsUnread(User $user, string $notificationId): bool
    {
        $notification = DB::table('notifications')
            ->where('id', $notificationId)
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', User::class)
            ->first();

        if (!$notification) {
            return false;
        }

        return DB::table('notifications')
            ->where('id', $notificationId)
            ->update(['read_at' => null]) > 0;
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead(User $user): int
    {
        return DB::table('notifications')
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', User::class)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Get unread notifications for user
     */
    public function getUnreadNotifications(int $userId): Collection
    {
        return Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->orderBy('sent_at', 'desc')
            ->get();
    }

    /**
     * Get unread count for user
     */
    public function getUnreadCount(User $user): int
    {
        return DB::table('notifications')
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', User::class)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Get paginated notifications for user with filters
     */
    public function getNotificationsForUser(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = DB::table('notifications')
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', User::class)
            ->orderBy('created_at', 'desc');

        // Apply filters
        if (isset($filters['unread_only']) && $filters['unread_only']) {
            $query->whereNull('read_at');
        }

        if (isset($filters['type']) && $filters['type']) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['from_date']) && $filters['from_date']) {
            $query->where('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date']) && $filters['to_date']) {
            $query->where('created_at', '<=', $filters['to_date']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Delete notification
     */
    public function deleteNotification(User $user, string $notificationId): bool
    {
        $notification = DB::table('notifications')
            ->where('id', $notificationId)
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', User::class)
            ->first();

        if (!$notification) {
            return false;
        }

        return DB::table('notifications')
            ->where('id', $notificationId)
            ->delete() > 0;
    }

    /**
     * Delete all notifications for user
     */
    public function deleteAllNotifications(User $user, array $filters = []): int
    {
        $query = DB::table('notifications')
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', User::class);

        // Apply filters
        if (isset($filters['read_only']) && $filters['read_only']) {
            $query->whereNotNull('read_at');
        }

        if (isset($filters['type']) && $filters['type']) {
            $query->where('type', $filters['type']);
        }

        return $query->delete();
    }
}
