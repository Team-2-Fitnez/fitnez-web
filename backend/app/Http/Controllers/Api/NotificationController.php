<?php

namespace App\Http\Controllers\Api;

use App\Features\Notifications\Controllers\Admin\AdminNotificationController;
use App\Features\Notifications\Controllers\NotificationController as FeatureNotificationController;
use App\Features\Notifications\Controllers\PushSubscriptionController;
use Illuminate\Http\Request;

class NotificationController extends FeatureNotificationController
{
    public function adminIndex(Request $request)
    {
        return app(AdminNotificationController::class)->index($request);
    }

    public function approve(Request $request, string $id)
    {
        return app(AdminNotificationController::class)->approve($request, $id);
    }

    public function reject(Request $request, string $id)
    {
        return app(AdminNotificationController::class)->reject($request, $id);
    }

    public function subscribe(Request $request)
    {
        return app(PushSubscriptionController::class)->store($request);
    }

    public function unsubscribe(Request $request)
    {
        return app(PushSubscriptionController::class)->destroy($request);
    }
}
