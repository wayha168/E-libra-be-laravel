<?php

namespace App\Http\Controllers\Api;

use App\Models\AppNotification;
use App\Support\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $notifications = AppNotification::where('user_id', $userId)
            ->latest()
            ->paginate(20);

        $unreadCount = AppNotification::where('user_id', $userId)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'message' => 'Notifications fetched successfully',
            'data' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    public function markRead(Request $request, AppNotification $notification): JsonResponse
    {
        if ($notification->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $notification->markRead();

        return response()->json([
            'message' => 'Notification marked as read',
            'unread_count' => NotificationService::unreadCount($request->user()),
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        AppNotification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'message' => 'All notifications marked as read',
            'unread_count' => 0,
        ]);
    }
}
