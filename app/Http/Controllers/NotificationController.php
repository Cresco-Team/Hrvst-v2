<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    /**
     * Display notifications page (Inertia)
     */
    public function index(Request $request): Response
    {
        $perPage = 20;
        $unreadOnly = $request->input('show') === 'unread';

        $query = $request->user()->notifications();

        if ($unreadOnly) {
            $query->whereNull('read_at');
        }

        $notifications = $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->through(fn($notification) => [
                'id' => $notification->id,
                'type' => class_basename($notification->type),
                'data' => $notification->data,
                'read_at' => $notification->read_at?->toISOString(),
                'created_at' => $notification->created_at->toISOString(),
                'created_at_human' => $notification->created_at->diffForHumans(),
            ]);

        return Inertia::render('notifications/Index', [
            'notificationsPaginated' => $notifications,
            'filters' => [
                'show' => $request->input('show', 'all'),
                'page' => $request->integer('page', 1),
            ],
        ]);
    }

    /**
     * Get paginated notifications via API
     */
    public function list(Request $request): JsonResponse
    {
        $perPage = $request->integer('per_page', 20);
        $unreadOnly = $request->boolean('unread_only', false);

        $query = $request->user()->notifications();

        if ($unreadOnly) {
            $query->whereNull('read_at');
        }

        $notifications = $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->through(fn($notification) => [
                'id' => $notification->id,
                'type' => class_basename($notification->type),
                'data' => $notification->data,
                'read_at' => $notification->read_at?->toISOString(),
                'created_at' => $notification->created_at->toISOString(),
                'created_at_human' => $notification->created_at->diffForHumans(),
            ]);

        return response()->json($notifications);
    }

    /**
     * Get unread notification count
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $request->user()->unreadNotifications()->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Mark single notification as read
     */
    public function markAsRead(Request $request, string $notificationId): JsonResponse
    {
        $notification = DatabaseNotification::where('id', $notificationId)
            ->where('notifiable_type', get_class($request->user()))
            ->where('notifiable_id', $request->user()->id)
            ->firstOrFail();

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        return response()->json([
            'message' => 'Notification marked as read',
            'notification' => [
                'id' => $notification->id,
                'read_at' => $notification->read_at?->toISOString(),
            ],
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $count = $request->user()
            ->unreadNotifications()
            ->update(['read_at' => now()]);

        return response()->json([
            'message' => 'All notifications marked as read',
            'count' => $count,
        ]);
    }

    /**
     * Mark specific notifications as read (bulk)
     */
    public function markMultipleAsRead(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'notification_ids' => ['required', 'array', 'min:1', 'max:50'],
            'notification_ids.*' => ['required', 'uuid'],
        ]);

        $count = DatabaseNotification::whereIn('id', $validated['notification_ids'])
            ->where('notifiable_type', get_class($request->user()))
            ->where('notifiable_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'message' => "{$count} notifications marked as read",
            'count' => $count,
        ]);
    }

    /**
     * Delete single notification
     */
    public function destroy(Request $request, string $notificationId): JsonResponse
    {
        $notification = DatabaseNotification::where('id', $notificationId)
            ->where('notifiable_type', get_class($request->user()))
            ->where('notifiable_id', $request->user()->id)
            ->firstOrFail();

        $notification->delete();

        return response()->json([
            'message' => 'Notification deleted',
        ]);
    }

    /**
     * Delete all read notifications
     */
    public function destroyRead(Request $request): JsonResponse
    {
        $count = $request->user()
            ->notifications()
            ->whereNotNull('read_at')
            ->delete();

        return response()->json([
            'message' => "{$count} notifications deleted",
            'count' => $count,
        ]);
    }
}
