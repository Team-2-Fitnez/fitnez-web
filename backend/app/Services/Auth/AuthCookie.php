<?php

namespace App\Services\Auth;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Cookie;

class AuthCookie
{
    public const NAME = 'fitnez_access_token';

    public function attach(JsonResponse $response, string $token, int $minutes = 1440): JsonResponse
    {
        $response->headers->setCookie(cookie(
            name: self::NAME,
            value: $token,
            minutes: $minutes,
            path: '/',
            domain: null,
            secure: app()->environment('production'),
            httpOnly: true,
            raw: false,
            sameSite: 'Lax'
        ));

        return $response;
    }

    public function forget(JsonResponse $response): JsonResponse
    {
        $response->headers->clearCookie(self::NAME, '/', null);

        return $response;
    }
}
