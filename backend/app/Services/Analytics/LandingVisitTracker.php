<?php

namespace App\Services\Analytics;

use App\Models\LandingPageVisit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LandingVisitTracker
{
    public function __construct(private readonly UserAgentParser $userAgentParser) {}

    public function record(array $data, Request $request, ?User $user = null): LandingPageVisit
    {
        $userAgent = $request->userAgent();
        $parsed = $this->userAgentParser->parse($userAgent);

        /*
         * Unique visitor rule:
         * visitor_uuid + visit_date
         *
         * This prevents refresh/new tabs in the same browser from increasing
         * unique visitor count.
         */
        $keys = [
            'visitor_uuid' => $data['visitor_uuid'],
            'visit_date' => today()->toDateString(),
        ];

        /*
         * Browser name priority:
         * 1. Client-side browser detection, because Brave can look like Chrome in UA.
         * 2. Backend UA parser fallback.
         */
        $browserName = $data['client_browser_name'] ?? $parsed['browser_name'];
        $browserEngine = $data['client_browser_engine'] ?? null;

        $payload = [
            'session_uuid' => $data['session_uuid'],
            'browser_context' => $data['browser_context'] ?? null,
            'browser_context_label' => $data['browser_context_label'] ?? null,
            'client_browser_name' => $data['client_browser_name'] ?? null,
	    'private_mode_detected' => $data['private_mode_detected'] ?? null,
	    'private_mode_confidence' => $data['private_mode_confidence'] ?? null,
	    'private_mode_source' => $data['private_mode_source'] ?? null,
            'client_browser_engine' => $browserEngine,

            'user_id' => $user?->id,

            'ip_address' => $request->ip(),
            'user_agent_hash' => $userAgent ? hash('sha256', $userAgent) : null,
            'user_agent' => $userAgent,

            'browser_name' => $browserName,
            'browser_version' => $parsed['browser_version'],
            'os_name' => $parsed['os_name'],
            'device_type' => $parsed['device_type'],

            'referrer' => $data['referrer'] ?? $request->headers->get('referer'),
            'landing_url' => $data['landing_url'] ?? null,
            'route_path' => $data['route_path'] ?? '/',
            'query_params' => $data['query_params'] ?? null,

            'locale' => $data['locale'] ?? null,
            'timezone' => $data['timezone'] ?? null,

            'screen_width' => $data['screen_width'] ?? null,
            'screen_height' => $data['screen_height'] ?? null,
            'viewport_width' => $data['viewport_width'] ?? null,
            'viewport_height' => $data['viewport_height'] ?? null,

            'last_seen_at' => now(),
        ];

        return DB::transaction(function () use ($keys, $payload) {
            $visit = LandingPageVisit::query()
                ->where($keys)
                ->lockForUpdate()
                ->first();

            if (! $visit) {
                return LandingPageVisit::query()->create([
                    ...$keys,
                    ...$payload,
                    'page_view_count' => 1,
                    'visited_at' => now(),
                ]);
            }

            $visit->fill($payload);
            $visit->page_view_count = $visit->page_view_count + 1;
            $visit->save();

            return $visit;
        });
    }

    public function heartbeat(array $data, Request $request, ?User $user = null): LandingPageVisit
    {
        return $this->record($data, $request, $user);
    }
}
