<?php

namespace App\Actions\Auth;

use App\Services\Auth\AuthService;
use App\Services\Otp\OtpManager;
use Illuminate\Http\Request;

class VerifyLoginOtpAction
{
    public function __construct(
        private readonly OtpManager $otpManager,
        private readonly AuthService $authService,
    ) {}

    public function handle(array $data, Request $request): array
    {
        $user = $this->otpManager->verify($data['email'], $data['otp'], 'password_reset');

        return [
            'user' => $this->authService->userPayload($user),
            'purpose' => 'password_reset',
        ];
    }
}
