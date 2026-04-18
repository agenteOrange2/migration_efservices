<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Services\NotificationCenterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class NotificationsController extends Controller
{
    public function __construct(
        private readonly NotificationCenterService $notificationCenter,
    ) {}

    public function index(Request $request): InertiaResponse
    {
        $user = $request->user();
        $filters = $this->validatedFilters($request);
        $status = $filters['status'] ?? 'all';

        return Inertia::render('driver/notifications/Index', [
            'title' => 'Notifications',
            'notifications' => $this->notificationCenter->getNotificationsForUser($user, $filters, 15)->withQueryString(),
            'filters' => [
                'search' => (string) ($filters['search'] ?? ''),
                'status' => $status,
                'type' => (string) ($filters['type'] ?? ''),
                'date_from' => (string) ($filters['date_from'] ?? ''),
                'date_to' => (string) ($filters['date_to'] ?? ''),
            ],
            'availableTypes' => $this->notificationCenter->getAvailableTypes($user),
            'stats' => $this->notificationCenter->getStats($user),
        ]);
    }

    public function markAsRead(Request $request, string $notification): RedirectResponse
    {
        $marked = $this->notificationCenter->markAsRead($request->user(), $notification);

        return back()->with($marked ? 'success' : 'error', $marked
            ? 'Notification marked as read.'
            : 'Notification not found.');
    }

    public function markAsUnread(Request $request, string $notification): RedirectResponse
    {
        $updated = $this->notificationCenter->markAsUnread($request->user(), $notification);

        return back()->with($updated ? 'success' : 'error', $updated
            ? 'Notification marked as unread.'
            : 'Notification not found.');
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $count = $this->notificationCenter->markAllAsRead($request->user());

        return back()->with('success', $count > 0
            ? "Marked {$count} notification(s) as read."
            : 'There were no unread notifications to update.');
    }

    public function destroy(Request $request, string $notification): RedirectResponse
    {
        $deleted = $this->notificationCenter->deleteNotification($request->user(), $notification);

        return back()->with($deleted ? 'success' : 'error', $deleted
            ? 'Notification deleted successfully.'
            : 'Notification not found.');
    }

    public function deleteAll(Request $request): RedirectResponse
    {
        $filters = $this->validatedFilters($request);
        $deleted = $this->notificationCenter->deleteAll($request->user(), $filters);

        return redirect()
            ->route('driver.notifications.index', $this->routeFilters($filters))
            ->with('success', $deleted > 0
                ? "Deleted {$deleted} notification(s)."
                : 'No notifications matched the current filters.');
    }

    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['all', 'read', 'unread'])],
            'type' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);
    }

    private function routeFilters(array $filters): array
    {
        return array_filter([
            'search' => $filters['search'] ?? null,
            'status' => $filters['status'] ?? null,
            'type' => $filters['type'] ?? null,
            'date_from' => $filters['date_from'] ?? null,
            'date_to' => $filters['date_to'] ?? null,
        ], fn ($value) => ! is_null($value) && $value !== '');
    }
}
