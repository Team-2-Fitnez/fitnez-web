<?php
namespace App\Services\Otp\Senders;
use App\Models\OtpCode;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
class RateLimitedOtpSender implements OtpSender {
    public function __construct(private readonly OtpSender $inner) {}
    public function send(OtpCode $otpCode, string $plainCode): void { $key='fitnez-otp-send:'.$otpCode->email.':'.$otpCode->purpose; if(RateLimiter::tooManyAttempts($key,3)) { $s=RateLimiter::availableIn($key); throw ValidationException::withMessages(['email'=>["Too many OTP requests. Try again in {$s} seconds."]]); } RateLimiter::hit($key,600); $this->inner->send($otpCode,$plainCode); }
}
