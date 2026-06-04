<?php

namespace App\Services\Auth\Login;

use App\Services\Auth\JwtService;
use Illuminate\Http\Request;

class WebLoginStrategy extends BaseLoginStrategy implements LoginStrategy
{
    public function __construct(private readonly JwtService $jwtService) {}

    public function login(array $credentials, Request $request): array
    {
        $user = $this->validateUser($credentials, $credentials['required_role'] ?? null);

        $user->forceFill(['last_login' => now()])->save();

        return [
            'token_type' => 'Bearer',
            'access_token' => $this->jwtService->issue($user, $request),
            'user' => $this->userPayload($user),
        ];
    }
}
