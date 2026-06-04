<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexTableRequest;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Support\ApiResponse;
use App\Support\SearchTerm;
use Illuminate\Support\Facades\DB;
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

        if (DB::getSchemaBuilder()->hasTable('system_logs')) {
            DB::table('system_logs')->insert([
                'user_id' => $user->id,
                'action_type' => 'USER_CREATED_BY_ADMIN',
                'table_affected' => 'users',
                'record_id' => $user->id,
                'description' => 'Admin created user account',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => json_encode([
                    'email' => $user->email,
                    'role_id' => $user->role_id,
                    'source' => 'admin_created_user',
                    'admin_id' => $request->user()?->id,
                ]),
                'created_at' => now(),
            ]);
        }

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

    public function destroy(User $user)
    {
        $user->delete();

        return ApiResponse::success('User deleted.');
    }

    public function roles()
    {
        return ApiResponse::success('Roles loaded.', Role::query()->orderBy('name')->limit(20)->get());
    }
}
