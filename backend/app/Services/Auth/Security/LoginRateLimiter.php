<?php
namespace App\Services\Auth\Security;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
class LoginRateLimiter {
    public function ensureNotLimited(string $email, Request $request): string { $key = 'fitnez-login:'.strtolower(trim($email)).'|'.$request->ip(); if(RateLimiter::tooManyAttempts($key,5)) { $s=RateLimiter::availableIn($key); throw ValidationException::withMessages(['email'=>["Too many login attempts. Try again in {$s} seconds."]]); } return $key; }
    public function hit(string $key): void { RateLimiter::hit($key,300); }
    public function clear(string $key): void { RateLimiter::clear($key); }
}
