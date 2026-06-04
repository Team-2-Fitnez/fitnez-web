<?php

namespace App\Actions\Auth;

use App\Services\Auth\AuthManager;
use Illuminate\Http\Request;

class LoginMemberAction
{
    public function __construct(private readonly AuthManager $auth) {}

    public function handle(array $data, Request $request): array
    {
        return $this->auth->login($data, $request, $data['channel'] ?? 'web');
    }
}
