<?php

namespace App\Features\Analytics\Services;

use App\Models\LandingPageVisit;
use App\Models\User;
use Illuminate\Http\Request;

class LandingVisitTracker
{
    public function record(array $data, Request $request, ?User $user = null): LandingPageVisit
    {
        $payload = $this->payload($data, $request, $user);

        return LandingPageVisit::query()->updateOrCreate(
            [
                'visitor_uuid' => $payload['visitor_uuid'],
                'visit_date' => $payload['visit_date'],
            ],
            $payload + ['visited_at' => now(), 'last_seen_at' => now()]
        );
    }

    public function heartbeat(array $data, Request $request, ?User $user = null): LandingPageVisit
    {
        return LandingPageVisit::query()->updateOrCreate(
            ['session_uuid' => $data['session_uuid']],
            $this->payload($data, $request, $user) + ['last_seen_at' => now()]
        );
    }

    private function payload(array $data, Request $request, ?User $user): array
    {
        return [
            'user_id' => $user?->id,
            'visitor_uuid' => $data['visitor_uuid'],
            'session_uuid' => $data['session_uuid'],
            'visit_date' => now()->toDateString(),
            'route_path' => $data['path'] ?? $data['route_path'] ?? $request->path(),
            'landing_url' => $data['url'] ?? $data['landing_url'] ?? $request->fullUrl(),
            'referrer' => $data['referrer'] ?? $request->headers->get('referer'),
            'device_type' => $data['device_type'] ?? null,
            'browser_name' => $data['browser'] ?? null,
            'ip_address' => $request->ip(),
            'user_agent_hash' => $request->userAgent() ? hash('sha256', $request->userAgent().config('app.key')) : null,
            'user_agent' => $request->userAgent(),
        ];
    }
}
