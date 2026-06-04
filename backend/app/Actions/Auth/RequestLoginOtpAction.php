<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Services\Otp\OtpManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class RequestLoginOtpAction
{
    public function __construct(private readonly OtpManager $otpManager) {}

    public function handle(array $data, Request $request): array
    {
        $email = strtolower(trim($data['email']));
        $password = (string) $data['password'];

        $user = User::query()
            ->with('role')
            ->where('email', $email)
            ->first();

        if (! $user || ! Hash::check($password, $user->password_hash)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid email or password.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['This account is not active.'],
            ]);
        }

        $this->otpManager->sendForUser($user, 'password_reset', $request->ip());

        return [
            'email' => $user->email,
            'requires_otp' => true,
            'purpose' => 'password_reset',
        ];
    }
}
