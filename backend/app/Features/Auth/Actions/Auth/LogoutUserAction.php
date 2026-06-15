<?php
namespace App\Features\Auth\Actions\Auth;
use App\Services\Auth\AuthManager;
use Illuminate\Http\Request;
class LogoutUserAction { public function __construct(private readonly AuthManager $auth) {} public function handle(Request $request): void { $this->auth->logout($request); } }
