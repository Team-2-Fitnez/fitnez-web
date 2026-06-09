<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveMembership
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isMember()) {
            return $next($request);
        }

        $user->activateDueMembershipRenewal();
        $user->refresh()->loadMissing('role');

        if ($user->isPastMembershipRenewalGracePeriod()) {
            $user->delete();

            return ApiResponse::error('This membership account has passed the renewal deadline and has been removed.', [], 410);
        }

        if ($user->isMembershipExpired()) {
            return ApiResponse::error('Your membership has expired. Please renew your package before using this feature.', [
                'membership_expires_at' => optional($user->membership_expires_at)->toISOString(),
                'renewal_deadline_at' => optional($user->membershipRenewalDeadline())->toISOString(),
            ], 402);
        }

        return $next($request);
    }
}
