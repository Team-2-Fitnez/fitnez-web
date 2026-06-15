<?php

namespace Tests\Helpers;

use App\Models\User;
use App\Features\Auth\Services\Auth\JwtService;

trait WithJwtAuth
{
    protected function authenticateAs(User $user): string
    {
        $jwtService = $this->app->make(JwtService::class);

        $token = $jwtService->tokenFor($user);

        $this->withToken($token);

        return $token;
    }
}
