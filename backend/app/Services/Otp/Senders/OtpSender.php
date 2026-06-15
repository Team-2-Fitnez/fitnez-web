<?php
namespace App\Services\Otp\Senders;
use App\Models\OtpCode;
interface OtpSender { public function send(OtpCode $otpCode, string $plainCode): void; }
