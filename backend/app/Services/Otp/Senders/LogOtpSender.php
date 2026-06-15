<?php
namespace App\Services\Otp\Senders;
use App\Models\OtpCode;
use Illuminate\Support\Facades\Log;
class LogOtpSender implements OtpSender { public function send(OtpCode $otpCode, string $plainCode): void { Log::info('FITNEZ_OTP',['email'=>$otpCode->email,'purpose'=>$otpCode->purpose,'otp'=>$plainCode,'expires_at'=>$otpCode->expires_at?->toDateTimeString()]); } }
