<?php
namespace App\Services\Otp\Senders;
use App\Models\OtpCode;
use Illuminate\Support\Facades\Mail;
class EmailOtpSender implements OtpSender { public function send(OtpCode $otpCode, string $plainCode): void { Mail::raw("Your Fitnez OTP code is {$plainCode}. It expires at {$otpCode->expires_at}.", fn($m) => $m->to($otpCode->email)->subject('Fitnez OTP Verification')); } }
