<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexTableRequest;
use App\Models\LandingPageVisit;
use App\Support\ApiResponse;
use App\Support\SearchTerm;

class LandingVisitReportController extends Controller
{
    public function index(IndexTableRequest $request)
    {
        $data = $request->validated();
        $search = SearchTerm::contains($data['search'] ?? null);

        $visits = LandingPageVisit::query()
            ->with('user:id,email,full_name')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('ip_address', 'ilike', $search)
                        ->orWhere('visitor_uuid', 'ilike', $search)
                        ->orWhere('session_uuid', 'ilike', $search)
                        ->orWhere('browser_name', 'ilike', $search)
                        ->orWhere('os_name', 'ilike', $search);
                });
            })
            ->when($data['browser'] ?? null, fn ($query, $browser) => $query->where('browser_name', $browser))
            ->when($data['device'] ?? null, fn ($query, $device) => $query->where('device_type', $device))
            ->orderByDesc('last_seen_at')
            ->paginate($request->perPage(15));

        return ApiResponse::success('Landing visits loaded.', $visits);
    }

    public function summary()
    {
        $activeAfter = now()->subSeconds(60);

        return ApiResponse::success('Landing visit summary loaded.', [
            'total_visit_rows' => LandingPageVisit::query()->count(),
            'total_page_views' => LandingPageVisit::query()->sum('page_view_count'),
            'unique_visitors_today' => LandingPageVisit::query()
                ->whereDate('visit_date', today())
                ->distinct('visitor_uuid')
                ->count('visitor_uuid'),
            'active_visitors_now' => LandingPageVisit::query()
                ->where('last_seen_at', '>=', $activeAfter)
                ->distinct('visitor_uuid')
                ->count('visitor_uuid'),

            'by_browser' => LandingPageVisit::query()
                ->select('browser_name')
                ->selectRaw('COUNT(*) as total')
                ->groupBy('browser_name')
                ->orderByDesc('total')
                ->limit(10)
                ->get(),

            'by_device' => LandingPageVisit::query()
                ->select('device_type')
                ->selectRaw('COUNT(*) as total')
                ->groupBy('device_type')
                ->orderByDesc('total')
                ->limit(10)
                ->get(),

            'latest' => LandingPageVisit::query()
                ->orderByDesc('last_seen_at')
                ->limit(10)
                ->get([
                    'id',
                    'ip_address',
                    'browser_name',
                    'os_name',
                    'device_type',
                    'page_view_count',
                    'route_path',
                    'visited_at',
                    'last_seen_at',
                ]),
        ]);
    }
}
