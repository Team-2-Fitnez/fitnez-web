<?php
namespace App\Actions\Otp;
use App\Services\Auth\AuthService;
use App\Services\Otp\OtpManager;
class VerifyOtpAction { public function __construct(private readonly OtpManager $otp, private readonly AuthService $auth) {} public function handle(array $data): array { $user=$this->otp->verify($data['email'],$data['otp'],$data['purpose'] ?? 'register'); return ['user'=>$this->auth->userPayload($user)]; } }
