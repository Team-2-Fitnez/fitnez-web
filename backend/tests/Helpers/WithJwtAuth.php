<?php

namespace Tests\Helpers;

use App\Models\User;
use App\Features\Anggota1AuthDashboardProfile\Services\Auth\JwtService;

trait WithJwtAuth
{
    protected function authenticateAs(User $user): string
    {
        $jwtService = $this->app->make(JwtService::class);

        $token = $jwtService->issue($user, request(), 60);

        $this->withToken($token);

        return $token;
    }
}
