<?php

namespace App\Features\Auth\Services\Auth;

use App\Models\User;
use Illuminate\Support\Str;
use RuntimeException;

class JwtService
{
    public function tokenFor(User $user): string
    {
        $issuedAt = time();

        return $this->encode([
            'iss' => config('app.url'),
            'iat' => $issuedAt,
            'exp' => $issuedAt + (int) env('JWT_TTL_SECONDS', 60 * 60 * 24 * 7),
            'sub' => $user->id,
            'id' => $user->id,
            'email' => $user->email,
            'role' => $user->role?->name,
        ]);
    }

    public function userFromToken(string $token): User
    {
        $payload = $this->payloadFromToken($token);
        $user = User::query()->with('role')->find($payload['sub'] ?? null);

        if (! $user) {
            throw new RuntimeException('User not found.');
        }

        return $user;
    }

    public function payloadFromToken(string $token): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new RuntimeException('Invalid token format.');
        }

        [$header, $payload, $signature] = $parts;
        $expected = $this->base64UrlEncode(hash_hmac('sha256', $header.'.'.$payload, $this->secret(), true));

        if (! hash_equals($expected, $signature)) {
            throw new RuntimeException('Invalid token signature.');
        }

        $data = json_decode($this->base64UrlDecode($payload), true);

        if (! is_array($data)) {
            throw new RuntimeException('Invalid token payload.');
        }

        if (($data['exp'] ?? 0) < time()) {
            throw new RuntimeException('Token expired.');
        }

        return $data;
    }

    private function encode(array $payload): string
    {
        $header = $this->base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => 'HS256'], JSON_THROW_ON_ERROR));
        $body = $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));
        $signature = $this->base64UrlEncode(hash_hmac('sha256', $header.'.'.$body, $this->secret(), true));

        return $header.'.'.$body.'.'.$signature;
    }

    private function secret(): string
    {
        return (string) env('JWT_SECRET', env('APP_KEY', Str::random(32)));
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        return base64_decode(strtr($value, '-_', '+/')) ?: '';
    }
}
