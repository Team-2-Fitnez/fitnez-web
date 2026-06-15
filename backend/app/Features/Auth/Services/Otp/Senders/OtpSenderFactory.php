<?php
namespace App\Features\Auth\Services\Otp\Senders;
class OtpSenderFactory { public function make(): OtpSender { $sender = config('mail.default') === 'log' ? app(LogOtpSender::class) : app(EmailOtpSender::class); return new RateLimitedOtpSender($sender); } }
