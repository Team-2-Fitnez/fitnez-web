<?php

namespace App\Features\Notifications\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use App\Support\ActionConfirmation;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'endpoint' => 'required|string',
            'auth_key' => 'required|string',
            'p256dh_key' => 'required|string',
        ]);

        $sub = PushSubscription::updateOrCreate(
            ['user_id' => $request->user()->id, 'endpoint' => $data['endpoint']],
            $data
        );

        return ApiResponse::success('Push subscription saved.', $sub);
    }

    public function destroy(Request $request)
    {
        $request->validate(['endpoint' => 'required|string']);

        if ($response = ActionConfirmation::require($request, 'delete push subscription', 'current device subscription')) {
            return $response;
        }

        $deleted = PushSubscription::where('user_id', $request->user()->id)
            ->where('endpoint', $request->endpoint)
            ->delete();

        return ApiResponse::success('Push subscription removed.', ['deleted_count' => $deleted]);
    }
}
