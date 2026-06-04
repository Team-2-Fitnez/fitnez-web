<?php

namespace App\Http\Middleware;

use App\Services\Auth\AuthCookie;
use App\Services\Auth\JwtService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class JwtAuthenticate
{
    public function __construct(private readonly JwtService $jwtService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->bearerToken($request) ?: $request->cookie(AuthCookie::NAME) ?: $request->query('token');

        if (! $token) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Missing JWT bearer token or HttpOnly cookie.',
                'data' => null,
            ], 401);
        }

        try {
            $payload = $this->jwtService->payloadFromToken($token);
            $user = $this->jwtService->userFromToken($token);

            Auth::setUser($user);
            $request->setUserResolver(fn () => $user);
            $request->attributes->set('jwt_payload', $payload);
            $request->attributes->set('jwt_token', $token);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. '.$e->getMessage(),
                'data' => null,
            ], 401);
        }

        return $next($request);
    }

    private function bearerToken(Request $request): ?string
    {
        $header = $request->header('Authorization', '');

        if (preg_match('/Bearer\s+(.+)/i', $header, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }
}
