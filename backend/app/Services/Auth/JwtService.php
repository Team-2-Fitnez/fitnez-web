<?php

namespace App\Services\Auth;

use App\Models\JwtSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class JwtService
{
    public function issue(User $user, Request $request, int $ttlMinutes = 1440): string
    {
        $jti = (string) Str::uuid();
        $issuedAt = now();
        $expiresAt = now()->addMinutes($ttlMinutes);

        JwtSession::query()->create([
            'user_id' => $user->id,
            'jti' => $jti,
            'ip_address' => $request->ip(),
            'user_agent_hash' => $request->userAgent() ? hash('sha256', $request->userAgent()) : null,
            'issued_at' => $issuedAt,
            'expires_at' => $expiresAt,
        ]);

        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256',
        ];

        $payload = [
            'iss' => config('app.url'),
            'aud' => config('app.url'),
            'sub' => $user->id,
            'email' => $user->email,
            'role' => $user->roleName(),
            'jti' => $jti,
            'iat' => $issuedAt->timestamp,
            'exp' => $expiresAt->timestamp,
        ];

        return $this->encode($header, $payload);
    }

    public function validate(string $token): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw ValidationException::withMessages(['token' => ['Invalid JWT format.']]);
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        $expected = $this->base64UrlEncode(
            hash_hmac('sha256', "{$headerB64}.{$payloadB64}", $this->secret(), true)
        );

        if (! hash_equals($expected, $signatureB64)) {
            throw ValidationException::withMessages(['token' => ['Invalid JWT signature.']]);
        }

        $payload = json_decode($this->base64UrlDecode($payloadB64), true);

        if (! is_array($payload)) {
            throw ValidationException::withMessages(['token' => ['Invalid JWT payload.']]);
        }

        if (($payload['exp'] ?? 0) < now()->timestamp) {
            throw ValidationException::withMessages(['token' => ['JWT has expired.']]);
        }

        $session = JwtSession::query()
            ->where('jti', $payload['jti'] ?? '')
            ->first();

        if (! $session || $session->isRevoked() || $session->isExpired()) {
            throw ValidationException::withMessages(['token' => ['JWT session is no longer valid.']]);
        }

        return $payload;
    }

    public function userFromToken(string $token): User
    {
        $payload = $this->validate($token);

        return User::query()
            ->with('role')
            ->findOrFail((int) $payload['sub']);
    }

    public function revoke(string $jti): void
    {
        JwtSession::query()
            ->where('jti', $jti)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    public function payloadFromToken(string $token): array
    {
        return $this->validate($token);
    }

    private function encode(array $header, array $payload): string
    {
        $headerB64 = $this->base64UrlEncode(json_encode($header));
        $payloadB64 = $this->base64UrlEncode(json_encode($payload));
        $signature = hash_hmac('sha256', "{$headerB64}.{$payloadB64}", $this->secret(), true);

        return "{$headerB64}.{$payloadB64}.".$this->base64UrlEncode($signature);
    }

    private function secret(): string
    {
        $secret = env('JWT_SECRET') ?: env('APP_KEY');

        if (str_starts_with($secret, 'base64:')) {
            $secret = base64_decode(substr($secret, 7));
        }

        return (string) $secret;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        $padding = strlen($value) % 4;

        if ($padding) {
            $value .= str_repeat('=', 4 - $padding);
        }

        return base64_decode(strtr($value, '-_', '+/')) ?: '';
    }
}
