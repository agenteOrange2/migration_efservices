<?php

namespace App\Services\Notification;

use App\Models\User;
use App\Models\NotificationLog;
use Illuminate\Support\Facades\Log;

class NotificationLogService
{
    /**
     * Log notification send attempt
     */
    public function log(User $user, string $type, string $channel, string $status, array $data = []): NotificationLog
    {
        return NotificationLog::create([
            'user_id' => $user->id,
            'notification_type' => $type,
            'channel' => $channel,
            'status' => $status,
            'metadata' => json_encode($data),
            'sent_at' => now(),
        ]);
    }

    /**
     * Log successful notification
     */
    public function logSuccess(User $user, string $type, string $channel, array $data = []): NotificationLog
    {
        return $this->log($user, $type, $channel, 'success', $data);
    }

    /**
     * Log failed notification
     */
    public function logFailure(User $user, string $type, string $channel, string $errorMessage, array $data = []): NotificationLog
    {
        $data['error'] = $errorMessage;
        return $this->log($user, $type, $channel, 'failed', $data);
    }

    /**
     * Get notification logs with filters
     */
    public function getNotificationLogs(array $filters = [])
    {
        $query = NotificationLog::query();

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['type'])) {
            $query->where('notification_type', $filters['type']);
        }

        if (isset($filters['channel'])) {
            $query->where('channel', $filters['channel']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['from_date'])) {
            $query->where('sent_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('sent_at', '<=', $filters['to_date']);
        }

        return $query->orderBy('sent_at', 'desc')->paginate(20);
    }

    /**
     * Get notification log details
     */
    public function getNotificationLogDetails(int $logId): ?NotificationLog
    {
        return NotificationLog::with(['user'])->find($logId);
    }

    /**
     * Get notification statistics
     */
    public function getNotificationStats(): array
    {
        $totalSent = NotificationLog::count();
        $successful = NotificationLog::where('status', 'success')->count();
        $failed = NotificationLog::where('status', 'failed')->count();

        $byChannel = NotificationLog::selectRaw('channel, COUNT(*) as count')
            ->groupBy('channel')
            ->pluck('count', 'channel')
            ->toArray();

        $byType = NotificationLog::selectRaw('notification_type, COUNT(*) as count')
            ->groupBy('notification_type')
            ->orderByDesc('count')
            ->limit(10)
            ->pluck('count', 'notification_type')
            ->toArray();

        return [
            'total_sent' => $totalSent,
            'successful' => $successful,
            'failed' => $failed,
            'success_rate' => $totalSent > 0 ? round(($successful / $totalSent) * 100, 2) : 0,
            'by_channel' => $byChannel,
            'top_types' => $byType,
        ];
    }

    /**
     * Retry failed notification
     */
    public function retryFailedNotification(int $logId): bool
    {
        $log = NotificationLog::find($logId);

        if (!$log || $log->status !== 'failed') {
            return false;
        }

        try {
            // Mark as retrying
            $log->update(['status' => 'retrying']);

            // Here you would implement the retry logic
            // This is a placeholder that marks it as successful
            $log->update([
                'status' => 'success',
                'sent_at' => now(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to retry notification', [
                'log_id' => $logId,
                'error' => $e->getMessage(),
            ]);

            $log->update(['status' => 'failed']);
            return false;
        }
    }

    /**
     * Clean old notification logs
     */
    public function cleanOldLogs(int $daysToKeep = 90): int
    {
        $threshold = now()->subDays($daysToKeep);

        return NotificationLog::where('sent_at', '<', $threshold)->delete();
    }
}
