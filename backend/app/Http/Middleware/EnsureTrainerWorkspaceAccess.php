<?php

namespace App\Http\Middleware;

use App\Models\TrainerApplication;
use App\Models\TrainerDetail;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTrainerWorkspaceAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
                'data' => null,
            ], 401);
        }

        $hasApprovedApplication = TrainerApplication::query()
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->exists();

        $hasTrainerProfile = TrainerDetail::query()
            ->where('user_id', $user->id)
            ->exists();

        if (! $hasApprovedApplication && ! $hasTrainerProfile && $user->roleName() !== 'trainer') {
            return response()->json([
                'success' => false,
                'message' => 'Trainer workspace is only available after admin approval.',
                'data' => null,
            ], 403);
        }

        return $next($request);
    }
}
