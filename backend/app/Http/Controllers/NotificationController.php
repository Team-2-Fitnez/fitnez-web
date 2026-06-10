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

        $latest = Notification::query()
            ->visibleTo($request->user())
            ->orderByDesc('id')
            ->first();

        return ApiResponse::success('Unread notifications loaded.', [
            'count' => $count,
            'latest_id' => $latest ? $latest->id : null,
        ]);
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
        $perPage = min((int) $request->integer('per_page', 20), 100);

        $notifications = Notification::where('user_id', $request->user()->id)
            ->whereIn('notification_type', [
                'booking_request', 'payment_in', 'hire', 'trainer_application',
            ])
            ->orderByDesc('id')
            ->paginate($perPage);

        return ApiResponse::success('Trainer notifications loaded.', $notifications);
    }
}
