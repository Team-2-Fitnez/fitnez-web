<?php
namespace App\Features\Auth\Services\Auth\Login;
use Illuminate\Http\Request;
interface LoginStrategy { public function login(array $credentials, Request $request): array; }
