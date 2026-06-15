<?php
namespace App\Services\Auth;
use Illuminate\Http\Request;
class AuthManager {
    public function __construct(private readonly AuthService $auth) {}
    public function login(array $credentials, Request $request, string $channel = 'web'): array { return $this->auth->login($credentials,$request,$channel); }
    public function logout(Request $request): void { $this->auth->logout($request); }
    public function me(Request $request): array { return $this->auth->userPayload($request->user()); }
}
