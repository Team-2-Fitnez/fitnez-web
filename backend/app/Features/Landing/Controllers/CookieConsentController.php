<?php

namespace App\Features\Landing\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CookieConsent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CookieConsentController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'anonymous_id' => ['required', 'uuid'],
            'consent_version' => ['required', 'string', 'max:40'],
            'categories' => ['required', 'array'],
            'categories.essential' => ['required', 'boolean'],
            'categories.analytics' => ['required', 'boolean'],
            'categories.marketing' => ['required', 'boolean'],
            'categories.preferences' => ['required', 'boolean'],
            'consented_at' => ['nullable', 'date'],
            'updated_at' => ['nullable', 'date'],
        ]);

        $categories = $validated['categories'];

        $consent = CookieConsent::create([
            'anonymous_id' => $validated['anonymous_id'],
            'consent_version' => $validated['consent_version'],
            'essential' => true,
            'analytics' => (bool) $categories['analytics'],
            'marketing' => (bool) $categories['marketing'],
            'preferences' => (bool) $categories['preferences'],
            'consented_at' => $validated['consented_at'] ?? now(),
            'last_updated_at' => $validated['updated_at'] ?? now(),
            'ip_hash' => $request->ip() ? hash('sha256', $request->ip() . config('app.key')) : null,
            'user_agent_hash' => $request->userAgent()
                ? hash('sha256', $request->userAgent() . config('app.key'))
                : null,
        ]);

        return response()->json([
            'message' => 'Cookie consent logged successfully.',
            'id' => $consent->id,
        ], 201);
    }
}
