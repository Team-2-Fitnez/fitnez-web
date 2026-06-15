<?php

namespace App\Features\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Requests\Admin\IndexTableRequest;
use App\Shared\Requests\Admin\StoreUserRequest;
use App\Shared\Requests\Admin\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Support\ApiResponse;
use App\Support\RequiresConfirmation;
use App\Support\SearchTerm;
use App\Shared\Support\TrendCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserManagementController extends Controller
{
    public function index(IndexTableRequest $request)
    {
        $data = $request->validated();
        $search = SearchTerm::contains($data['search'] ?? null);

        $users = User::query()
            ->with('role')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'ilike', $search)
                        ->orWhere('email', 'ilike', $search)
                        ->orWhere('phone', 'ilike', $search);
                });
            })
            ->when($data['role'] ?? null, function ($query, $role) {
                $query->whereHas('role', fn ($q) => $q->where('name', $role));
            })
            ->when(isset($data['status']) && $data['status'] !== '', function ($query) use ($data) {
                if ($data['status'] === 'active') {
                    $query->where('is_active', true);
                } elseif ($data['status'] === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->orderByDesc('id')
            ->paginate($request->perPage());

        return ApiResponse::success('Users loaded.', $users);
    }

    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();

        $user = User::query()->create([
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'role_id' => $data['role_id'],
            'password_hash' => Hash::make($data['password']),
            'is_active' => $data['is_active'],
            'email_verified_at' => $data['is_active'] ? now() : null,
        ]);

        return ApiResponse::success('User created.', $user->load('role'), 201);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();

        $payload = [
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'role_id' => $data['role_id'],
            'is_active' => $data['is_active'],
        ];

        if (! empty($data['password'])) {
            $payload['password_hash'] = Hash::make($data['password']);
        }

        if ($data['is_active'] && is_null($user->email_verified_at)) {
            $payload['email_verified_at'] = now();
        }

        $user->update($payload);

        return ApiResponse::success('User updated.', $user->fresh('role'));
    }

    public function destroy(Request $request, User $user)
    {
        if ($confirmation = RequiresConfirmation::check($request, 'delete_user', $user->email)) {
            return $confirmation;
        }

        $user->delete();

        return ApiResponse::success('User deleted.');
    }

    public function roles()
    {
        return ApiResponse::success('Roles loaded.', Role::query()->orderBy('name')->limit(20)->get());
    }

    public function summary()
    {
        $memberRoleId = Role::query()->where('name', 'member')->value('id');

        $membersQuery = User::query()
            ->when($memberRoleId, fn ($query) => $query->where('role_id', $memberRoleId));

        // Use new database queries for counts to ensure correct scoping
        $totalMembersNow = User::query()->when($memberRoleId, fn ($query) => $query->where('role_id', $memberRoleId))->count();
        $totalMembersPrev = User::query()->when($memberRoleId, fn ($query) => $query->where('role_id', $memberRoleId))->where('created_at', '<=', now()->subDays(7))->count();
        $totalMembersTrend = TrendCalculator::percentage($totalMembersNow, $totalMembersPrev);

        $newMembersThisMonth = User::query()->when($memberRoleId, fn ($query) => $query->where('role_id', $memberRoleId))
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $newMembersLastMonth = User::query()->when($memberRoleId, fn ($query) => $query->where('role_id', $memberRoleId))
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();
        $newMembersTrend = TrendCalculator::percentage($newMembersThisMonth, $newMembersLastMonth);

        $inactiveNow = User::query()->when($memberRoleId, fn ($query) => $query->where('role_id', $memberRoleId))->where('is_active', false)->count();
        $inactivePrev = User::query()->when($memberRoleId, fn ($query) => $query->where('role_id', $memberRoleId))->where('is_active', false)->where('created_at', '<=', now()->subDays(7))->count();
        $inactiveTrend = TrendCalculator::percentage($inactiveNow, $inactivePrev);

        return ApiResponse::success('User management summary loaded.', [
            'total_members' => $totalMembersNow,
            'total_members_trend' => $totalMembersTrend,
            'new_members_this_month' => $newMembersThisMonth,
            'new_members_this_month_trend' => $newMembersTrend,
            'inactive_members' => $inactiveNow,
            'inactive_members_trend' => $inactiveTrend,
        ]);
    }

}
