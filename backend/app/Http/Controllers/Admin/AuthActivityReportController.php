<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AuthActivityReportRequest;
use App\Models\SystemLog;
use App\Models\User;
use App\Support\ApiResponse;
use App\Support\SearchTerm;
use Illuminate\Http\Request;

class AuthActivityReportController extends Controller
{
    public function summary(Request $request)
    {
        $today = now()->toDateString();

        return ApiResponse::success('Authentication activity summary loaded.', [
            'total_registered_users' => User::query()->count(),
            'registered_today' => User::query()->whereDate('created_at', $today)->count(),
            'registered_this_month' => User::query()
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'login_success_today' => SystemLog::query()->where('action_type', 'LOGIN_SUCCESS')->whereDate('created_at', $today)->count(),
            'login_failed_today' => SystemLog::query()->where('action_type', 'LOGIN_FAILED')->whereDate('created_at', $today)->count(),
            'login_success_this_month' => SystemLog::query()
                ->where('action_type', 'LOGIN_SUCCESS')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'login_failed_this_month' => SystemLog::query()
                ->where('action_type', 'LOGIN_FAILED')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'latest_registrations' => User::query()->with('role')->latest('created_at')->limit(5)->get(),
            'latest_logs' => SystemLog::query()->with('user.role')->latest('created_at')->limit(8)->get(),
        ]);
    }

    public function logs(AuthActivityReportRequest $request)
    {
        $data = $request->validated();
        $search = SearchTerm::contains($data['search'] ?? null);

        $logs = SystemLog::query()
            ->with('user.role')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('action_type', 'ilike', $search)
                        ->orWhere('description', 'ilike', $search)
                        ->orWhere('table_affected', 'ilike', $search)
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('full_name', 'ilike', $search)
                                ->orWhere('email', 'ilike', $search);
                        });
                });
            })
            ->when($data['action_type'] ?? null, fn ($query, $action) => $query->where('action_type', $action))
            ->when($data['role'] ?? null, function ($query, $role) {
                $query->whereHas('user.role', fn ($roleQuery) => $roleQuery->where('name', $role));
            })
            ->when($data['start_date'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($data['end_date'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->orderByDesc('created_at')
            ->paginate($request->perPage(15));

        return ApiResponse::success('Authentication activity logs loaded.', $logs);
    }

    public function registrations(AuthActivityReportRequest $request)
    {
        $data = $request->validated();
        $search = SearchTerm::contains($data['search'] ?? null);

        $registrations = User::query()
            ->with('role')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'ilike', $search)
                        ->orWhere('email', 'ilike', $search)
                        ->orWhere('phone', 'ilike', $search);
                });
            })
            ->when($data['role'] ?? null, function ($query, $role) {
                $query->whereHas('role', fn ($roleQuery) => $roleQuery->where('name', $role));
            })
            ->when($data['start_date'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($data['end_date'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->orderByDesc('created_at')
            ->paginate($request->perPage(15));

        return ApiResponse::success('Registration report loaded.', $registrations);
    }
}
