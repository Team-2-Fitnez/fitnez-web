<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateInternalBroadcast
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('services.socketio.internal_token', env('SOCKETIO_INTERNAL_TOKEN', ''));
        $provided = (string) $request->header('X-Internal-Broadcast-Token', $request->bearerToken() ?? '');

        if ($expected === '' || ! hash_equals($expected, $provided)) {
            return ApiResponse::error('Invalid internal broadcast token.', [], 403);
        }

        return $next($request);
    }
}
