<?php

namespace App\Features\Auth\Actions;

use App\Features\Auth\Services\Auth\AuthService;
use App\Features\Auth\Services\Otp\OtpManager;
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
