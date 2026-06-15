<?php

namespace App\Features\Auth\Services\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthManager
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly JwtService $jwtService,
    ) {}

    public function login(array $data, Request $request, string $channel = 'web'): array
    {
        $user = User::query()->with('role')->where('email', strtolower(trim($data['email'])))->first();

        if (! $user || ! Hash::check((string) $data['password'], $user->password_hash)) {
            throw ValidationException::withMessages(['email' => ['Invalid email or password.']]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages(['email' => ['This account is not active.']]);
        }

        $user->forceFill(['last_login' => now()])->save();

        return [
            'access_token' => $this->jwtService->tokenFor($user->refresh()->load('role')),
            'token_type' => 'Bearer',
            'channel' => $channel,
            'user' => $this->authService->userPayload($user),
        ];
    }

    public function me(Request $request): array
    {
        return $this->authService->userPayload($request->user());
    }

    public function logout(Request $request): void
    {
        $request->attributes->remove('jwt_token');
    }
}
