<?php

namespace App\Features\Auth\Services\Otp;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OtpManager
{
    public function sendForUser(User $user, string $purpose, ?string $ip = null): string
    {
        return $this->store($user->email, $purpose, $ip, $user->id);
    }

    public function sendToEmail(string $email, string $purpose, ?string $ip = null): string
    {
        return $this->store($email, $purpose, $ip);
    }

    public function verify(string $email, string $otp, string $purpose): User
    {
        $email = strtolower(trim($email));
        $record = DB::table('otp_codes')
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->where('expires_at', '>=', now())
            ->latest('id')
            ->first();

        if (! $record || ! password_verify($otp, $record->code_hash)) {
            throw ValidationException::withMessages(['otp' => ['Invalid or expired OTP.']]);
        }

        DB::table('otp_codes')->where('id', $record->id)->update(['used_at' => now()]);

        $user = User::query()->where('email', $email)->firstOrFail();
        $user->forceFill(['is_active' => true, 'email_verified_at' => $user->email_verified_at ?: now()])->save();

        return $user->refresh()->load('role');
    }

    private function store(string $email, string $purpose, ?string $ip, ?int $userId = null): string
    {
        $code = (string) random_int(100000, 999999);

        DB::table('otp_codes')->insert([
            'user_id' => $userId,
            'email' => strtolower(trim($email)),
            'purpose' => $purpose,
            'code_hash' => password_hash($code, PASSWORD_DEFAULT),
            'ip_address' => $ip,
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        logger()->info('Fitnez OTP generated', ['email' => $email, 'purpose' => $purpose, 'otp' => $code]);

        return $code;
    }
}
