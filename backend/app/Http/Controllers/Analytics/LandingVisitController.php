<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Analytics\StoreLandingVisitRequest;
use App\Services\Analytics\LandingVisitTracker;
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
}
