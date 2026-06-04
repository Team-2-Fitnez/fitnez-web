<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
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

        return response()->json(['success' => true, 'data' => $sub]);
    }

    public function destroy(Request $request)
    {
        $request->validate(['endpoint' => 'required|string']);

        PushSubscription::where('user_id', $request->user()->id)
            ->where('endpoint', $request->endpoint)
            ->delete();

        return response()->json(['success' => true]);
    }
}
