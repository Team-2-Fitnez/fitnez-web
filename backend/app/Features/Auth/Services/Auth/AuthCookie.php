<?php

namespace App\Features\Auth\Services\Auth;

use Symfony\Component\HttpFoundation\Response;

class AuthCookie
{
    public const NAME = 'fitnez_access_token';

    public function attach(Response $response, string $token): Response
    {
        return $response->cookie(self::NAME, $token, 60 * 24 * 7, '/', null, false, true, false, 'Lax');
    }

    public function forget(Response $response): Response
    {
        return $response->withoutCookie(self::NAME);
    }
}
