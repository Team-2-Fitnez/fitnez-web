<?php

namespace App\Http\Controllers;

use App\Http\Requests\Common\NotificationIndexRequest;
use App\Models\Notification;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(NotificationIndexRequest $request)
    {
        $notifications = Notification::query()
            ->visibleTo($request->user())
            ->orderByDesc('id')
            ->paginate($request->perPage());

        return ApiResponse::success('Notifications loaded.', $notifications);
    }

    public function unreadCount(Request $request)
    {
        $count = Notification::query()
            ->visibleTo($request->user())
            ->unread()
            ->count();

        return ApiResponse::success('Unread notifications loaded.', ['count' => $count]);
    }

    public function markAsReadIndividual(Request $request, Notification $notification)
    {
        $belongsToCurrentUser = (int) $notification->user_id === (int) $request->user()->id;
        $isGlobalNotification = is_null($notification->user_id);

        if (! $belongsToCurrentUser && ! $isGlobalNotification) {
            return ApiResponse::error('You are not allowed to modify this notification.', [], 403);
        }

        $notification->update(['is_read' => true]);

        return ApiResponse::success('Notification marked as read.', $notification->fresh());
    }

    public function markAllRead(Request $request)
    {
        Notification::query()
            ->visibleTo($request->user())
            ->unread()
            ->update(['is_read' => true]);

        return ApiResponse::success('All notifications marked as read.');
    }

    public function trainerNotifications(Request $request)
    {
        $notifications = Notification::where('user_id', $request->user()->id)
            ->whereIn('notification_type', ['rent', 'hire', 'schedule', 'classes'])
            ->orderByDesc('id')
            ->get();

        return ApiResponse::success('Trainer notifications loaded.', $notifications);
    }
}
