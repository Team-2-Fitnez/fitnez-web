<?php
namespace App\Services\Otp;
use App\Models\OtpCode;
use App\Models\User;
class OtpManager {
    public function __construct(private readonly OtpService $otp) {}
    public function sendForUser(User $user, string $purpose = 'register', ?string $ipAddress = null): OtpCode { return $this->otp->send($user,$user->email,$purpose,$ipAddress); }
    public function sendToEmail(string $email, string $purpose = 'register', ?string $ipAddress = null): OtpCode { return $this->otp->send(null,$email,$purpose,$ipAddress); }
    public function verify(string $email, string $plainCode, string $purpose = 'register'): User { return $this->otp->verify($email,$plainCode,$purpose); }
}
