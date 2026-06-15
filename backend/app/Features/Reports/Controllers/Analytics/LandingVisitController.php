<?php

namespace App\Features\Reports\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Features\Reports\Requests\Analytics\StoreLandingVisitRequest;
use App\Features\Reports\Services\Analytics\LandingVisitTracker;
use App\Support\ApiResponse;

class LandingVisitController extends Controller
{
    public function store(StoreLandingVisitRequest $request, LandingVisitTracker $tracker)
    {
        $visit = $tracker->record($request->validated(), $request, $request->user());

        return ApiResponse::success('Landing visit tracked.', [
            'id' => $visit->id,
            'visitor_uuid' => $visit->visitor_uuid,
            'session_uuid' => $visit->session_uuid,
            'visited_at' => $visit->visited_at?->toISOString(),
        ], 201);
    }

    public function heartbeat(StoreLandingVisitRequest $request, LandingVisitTracker $tracker)
    {
        $visit = $tracker->heartbeat($request->validated(), $request, $request->user());

        return ApiResponse::success('Landing visit heartbeat tracked.', [
            'id' => $visit->id,
            'visitor_uuid' => $visit->visitor_uuid,
            'session_uuid' => $visit->session_uuid,
            'visited_at' => $visit->visited_at?->toISOString(),
            'last_seen_at' => $visit->last_seen_at?->toISOString(),
        ]);
    }
}
