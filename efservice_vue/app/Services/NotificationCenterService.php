<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationCenterService
{
    public function getDropdownNotificationsForUser(User $user, int $limit = 10): array
    {
        return $this->queryForUser($user)
            ->whereNull('read_at')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (DatabaseNotification $notification) => $this->transformNotification($notification))
            ->all();
    }

    public function getNotificationsForUser(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->applyFilters($this->queryForUser($user), $filters)
            ->latest()
            ->paginate($perPage)
            ->through(fn (DatabaseNotification $notification) => $this->transformNotification($notification));
    }

    public function getAvailableTypes(User $user): array
    {
        return $this->queryForUser($user)
            ->select('type', DB::raw('count(*) as total'))
            ->groupBy('type')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'id' => (string) $row->type,
                'name' => $this->formatTypeLabel((string) $row->type),
                'count' => (int) $row->total,
            ])
            ->values()
            ->all();
    }

    public function getStats(User $user): array
    {
        $baseQuery = $this->queryForUser($user);

        return [
            'total' => (clone $baseQuery)->count(),
            'unread' => (clone $baseQuery)->whereNull('read_at')->count(),
            'read' => (clone $baseQuery)->whereNotNull('read_at')->count(),
            'today' => (clone $baseQuery)->whereDate('created_at', today())->count(),
            'types' => (clone $baseQuery)->select('type')->distinct()->count('type'),
        ];
    }

    public function getUnreadCount(User $user): int
    {
        return $this->queryForUser($user)->whereNull('read_at')->count();
    }

    public function markAsRead(User $user, string $notificationId): bool
    {
        $notification = $this->findForUser($user, $notificationId);

        if (! $notification) {
            return false;
        }

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        return true;
    }

    public function markAsUnread(User $user, string $notificationId): bool
    {
        $notification = $this->findForUser($user, $notificationId);

        if (! $notification) {
            return false;
        }

        return $notification->forceFill(['read_at' => null])->save();
    }

    public function markAllAsRead(User $user): int
    {
        return $this->queryForUser($user)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function deleteNotification(User $user, string $notificationId): bool
    {
        $notification = $this->findForUser($user, $notificationId);

        if (! $notification) {
            return false;
        }

        return (bool) $notification->delete();
    }

    public function deleteAll(User $user, array $filters = []): int
    {
        return $this->applyFilters($this->queryForUser($user), $filters)->delete();
    }

    public function transformNotification(DatabaseNotification $notification): array
    {
        $payload = $this->normalizePayload($notification->data ?? []);
        $title = $this->resolveTitle($notification, $payload);
        $message = $this->resolveMessage($payload);
        $url = $this->resolveUrl($payload);
        $category = $this->resolveCategory($notification, $payload);
        $level = $this->resolveLevel($payload);

        return [
            'id' => (string) $notification->id,
            'type' => (string) $notification->type,
            'type_label' => $this->formatTypeLabel((string) $notification->type),
            'title' => $title,
            'message' => $message,
            'icon' => $this->resolveIcon($notification, $payload, $category),
            'url' => $url,
            'category' => $category,
            'category_label' => Str::headline((string) $category),
            'level' => $level,
            'read_at' => $notification->read_at?->toISOString(),
            'created_at' => $notification->created_at?->toISOString(),
            'created_at_formatted' => $notification->created_at?->format('M j, Y g:i A'),
            'created_at_human' => $notification->created_at?->diffForHumans(),
            'is_unread' => $notification->read_at === null,
            'data' => $payload,
        ];
    }

    private function queryForUser(User $user): Builder
    {
        return DatabaseNotification::query()
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id);
    }

    private function applyFilters(Builder $query, array $filters): Builder
    {
        $status = $filters['status'] ?? 'all';
        $search = trim((string) ($filters['search'] ?? ''));
        $type = trim((string) ($filters['type'] ?? ''));
        $fromDate = $filters['date_from'] ?? null;
        $toDate = $filters['date_to'] ?? null;

        if ($status === 'unread') {
            $query->whereNull('read_at');
        } elseif ($status === 'read') {
            $query->whereNotNull('read_at');
        }

        if ($type !== '') {
            $query->where('type', $type);
        }

        if ($search !== '') {
            $like = '%' . $this->escapeLike($search) . '%';

            $query->where(function (Builder $innerQuery) use ($like) {
                $innerQuery
                    ->where('type', 'like', $like)
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.title')) LIKE ?", [$like])
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.message')) LIKE ?", [$like])
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.description')) LIKE ?", [$like])
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.category')) LIKE ?", [$like])
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.data.event_type')) LIKE ?", [$like])
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.data.step')) LIKE ?", [$like]);
            });
        }

        if ($fromDate) {
            $query->where('created_at', '>=', Carbon::parse($fromDate)->startOfDay());
        }

        if ($toDate) {
            $query->where('created_at', '<=', Carbon::parse($toDate)->endOfDay());
        }

        return $query;
    }

    private function findForUser(User $user, string $notificationId): ?DatabaseNotification
    {
        return $this->queryForUser($user)->find($notificationId);
    }

    private function normalizePayload(array $data): array
    {
        $nested = Arr::get($data, 'data');
        $nested = is_array($nested) ? $nested : [];

        return array_merge($nested, Arr::except($data, ['data']));
    }

    private function resolveTitle(DatabaseNotification $notification, array $payload): string
    {
        $title = trim((string) ($payload['title'] ?? $payload['subject'] ?? ''));

        return $title !== '' ? $title : $this->formatTypeLabel((string) $notification->type);
    }

    private function resolveMessage(array $payload): string
    {
        $message = trim((string) ($payload['message'] ?? $payload['description'] ?? $payload['body'] ?? ''));

        return $message !== '' ? $message : 'You have a new notification.';
    }

    private function resolveUrl(array $payload): ?string
    {
        $url = $payload['url'] ?? $payload['link'] ?? $payload['action_url'] ?? null;

        $resolved = $this->resolveLegacyUrl($url, $payload);

        if ($resolved) {
            return $resolved;
        }

        if (! is_string($url) || trim($url) === '') {
            return $this->resolveUrlFromPayload($payload);
        }

        return trim($url);
    }

    private function resolveCategory(DatabaseNotification $notification, array $payload): string
    {
        $category = $payload['category'] ?? null;

        if (is_string($category) && trim($category) !== '') {
            return trim($category);
        }

        $typeHint = $payload['type'] ?? null;
        if (is_string($typeHint) && ! in_array($typeHint, ['info', 'success', 'warning', 'error'], true)) {
            return trim($typeHint);
        }

        $type = strtolower(class_basename((string) $notification->type));

        return match (true) {
            str_contains($type, 'message') => 'messages',
            str_contains($type, 'carrier') => 'carriers',
            str_contains($type, 'driver') => 'drivers',
            str_contains($type, 'vehicle') => 'vehicles',
            str_contains($type, 'trip') => 'trips',
            str_contains($type, 'hos') => 'hos',
            str_contains($type, 'training') => 'trainings',
            str_contains($type, 'document') => 'documents',
            str_contains($type, 'inspection') => 'inspections',
            str_contains($type, 'accident') => 'accidents',
            str_contains($type, 'maintenance') || str_contains($type, 'repair') => 'maintenance',
            default => 'system',
        };
    }

    private function resolveLevel(array $payload): string
    {
        $type = strtolower((string) ($payload['type'] ?? ''));

        return in_array($type, ['info', 'success', 'warning', 'error'], true) ? $type : 'info';
    }

    private function resolveIcon(DatabaseNotification $notification, array $payload, string $category): string
    {
        $icon = $payload['icon'] ?? null;
        if (is_string($icon) && trim($icon) !== '') {
            return trim($icon);
        }

        $type = strtolower(class_basename((string) $notification->type));

        return match (true) {
            str_contains($type, 'message') || $category === 'messages' => 'Mail',
            str_contains($type, 'carrier') || $category === 'carriers' => 'Building2',
            str_contains($type, 'driver') || $category === 'drivers' => 'User',
            str_contains($type, 'vehicle') || $category === 'vehicles' => 'Truck',
            str_contains($type, 'trip') || $category === 'trips' => 'MapPin',
            str_contains($type, 'hos') || $category === 'hos' => 'Clock3',
            str_contains($type, 'training') || $category === 'trainings' => 'GraduationCap',
            str_contains($type, 'document') || $category === 'documents' => 'FileText',
            str_contains($type, 'inspection') || $category === 'inspections' => 'ClipboardCheck',
            str_contains($type, 'accident') || $category === 'accidents' => 'TriangleAlert',
            str_contains($type, 'traffic') => 'ShieldAlert',
            str_contains($type, 'maintenance') || str_contains($type, 'repair') || $category === 'maintenance' => 'Wrench',
            default => 'Bell',
        };
    }

    private function formatTypeLabel(string $type): string
    {
        return Str::of(class_basename($type))
            ->replace('Notification', '')
            ->headline()
            ->value();
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    private function resolveLegacyUrl(mixed $url, array $payload): ?string
    {
        if (! is_string($url) || trim($url) === '') {
            return null;
        }

        $url = trim($url);

        if (preg_match('#^/admin/carriers/[^/]+/users$#', $url) && ! empty($payload['carrier_id'])) {
            return route('admin.carriers.users.index', $payload['carrier_id']);
        }

        if (preg_match('#^/admin/carriers/[^/]+/drivers/(\d+)/edit$#', $url, $matches)) {
            return route('admin.drivers.show', (int) ($payload['driver_detail_id'] ?? $matches[1]));
        }

        if (preg_match('#^/admin/users/(\d+)$#', $url, $matches)) {
            return route('admin.users.edit', (int) ($payload['user_id'] ?? $matches[1]));
        }

        if (preg_match('#^/admin/vehicles/(\d+)$#', $url, $matches) && ! empty($payload['category']) && $payload['category'] === 'vehicles') {
            if (isset($payload['recipient_type']) && $payload['recipient_type'] === 'carrier') {
                return route('carrier.vehicles.show', (int) $matches[1]);
            }
        }

        return null;
    }

    private function resolveUrlFromPayload(array $payload): ?string
    {
        if (! empty($payload['carrier_id']) && ($payload['category'] ?? null) === 'carriers') {
            return route('admin.carriers.show', $payload['carrier_id']);
        }

        if (! empty($payload['driver_detail_id'])) {
            return route('admin.drivers.show', $payload['driver_detail_id']);
        }

        if (! empty($payload['driver_id']) && ($payload['category'] ?? null) === 'drivers') {
            return route('admin.drivers.show', $payload['driver_id']);
        }

        if (! empty($payload['user_id']) && ($payload['category'] ?? null) === 'users') {
            return route('admin.users.edit', $payload['user_id']);
        }

        return null;
    }
}
