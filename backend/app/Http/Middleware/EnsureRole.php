<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
                'data' => null,
            ], 401);
        }

        if (! in_array($user->roleName(), $roles, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Insufficient role.',
                'data' => null,
            ], 403);
        }

        return $next($request);
    }
}
