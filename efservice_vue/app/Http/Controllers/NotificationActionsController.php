<?php

namespace App\Http\Controllers;

use App\Services\NotificationCenterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationActionsController extends Controller
{
    public function __construct(
        private readonly NotificationCenterService $notificationCenter,
    ) {}

    public function markAsRead(Request $request, string $notification): JsonResponse
    {
        $updated = $this->notificationCenter->markAsRead($request->user(), $notification);

        return response()->json([
            'success' => $updated,
            'unread_count' => $this->notificationCenter->getUnreadCount($request->user()),
        ], $updated ? 200 : 404);
    }

    public function markAsUnread(Request $request, string $notification): JsonResponse
    {
        $updated = $this->notificationCenter->markAsUnread($request->user(), $notification);

        return response()->json([
            'success' => $updated,
            'unread_count' => $this->notificationCenter->getUnreadCount($request->user()),
        ], $updated ? 200 : 404);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $count = $this->notificationCenter->markAllAsRead($request->user());

        return response()->json([
            'success' => true,
            'marked_count' => $count,
            'unread_count' => $this->notificationCenter->getUnreadCount($request->user()),
        ]);
    }

    public function destroy(Request $request, string $notification): JsonResponse
    {
        $deleted = $this->notificationCenter->deleteNotification($request->user(), $notification);

        return response()->json([
            'success' => $deleted,
            'unread_count' => $this->notificationCenter->getUnreadCount($request->user()),
        ], $deleted ? 200 : 404);
    }
}
