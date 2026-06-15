<?php
namespace App\Services\Otp;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\Otp\Senders\OtpSenderFactory;
use Illuminate\Support\Facades\Hash;
class OtpService {
    public function __construct(private readonly OtpGenerator $generator, private readonly OtpVerifier $verifier, private readonly OtpSenderFactory $factory) {}
    public function send(?User $user, string $email, string $purpose = 'register', ?string $ipAddress = null): OtpCode { $plain=$this->generator->numeric(); $otp=OtpCode::query()->create(['user_id'=>$user?->id,'email'=>strtolower(trim($email)),'purpose'=>$purpose,'code_hash'=>Hash::make($plain),'expires_at'=>now()->addMinutes(10),'ip_address'=>$ipAddress,'attempts'=>0]); $this->factory->make()->send($otp,$plain); return $otp; }
    public function verify(string $email, string $plainCode, string $purpose = 'register'): User { return $this->verifier->verify($email,$plainCode,$purpose); }
}
