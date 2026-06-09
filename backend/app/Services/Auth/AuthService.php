<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\TrainerApplication;
use App\Models\TrainerDetail;
use App\Services\Auth\Login\LoginStrategyFactory;
use App\Services\Auth\Security\LoginAuditLogger;
use App\Services\Auth\Security\LoginRateLimiter;
use Illuminate\Http\Request;
use Throwable;

class AuthService
{
    public function __construct(
        private readonly LoginStrategyFactory $loginStrategyFactory,
        private readonly LoginRateLimiter $loginRateLimiter,
        private readonly LoginAuditLogger $loginAuditLogger,
        private readonly JwtService $jwtService,
    ) {}

    public function login(array $credentials, Request $request, string $channel = 'web'): array
    {
        $email = strtolower(trim((string) ($credentials['email'] ?? '')));
        $rateLimitKey = $this->loginRateLimiter->ensureNotLimited($email, $request);

        try {
            $strategy = $this->loginStrategyFactory->make($channel);
            $result = $strategy->login($credentials, $request);

            $this->loginRateLimiter->clear($rateLimitKey);
            $this->loginAuditLogger->success($result['user']['id'] ?? null, $request);

            return $result;
        } catch (Throwable $e) {
            $this->loginRateLimiter->hit($rateLimitKey);
            $this->loginAuditLogger->failed(null, $request, $e->getMessage());

            throw $e;
        }
    }

    public function logout(Request $request): void
    {
        $payload = $request->attributes->get('jwt_payload');

        if (is_array($payload) && isset($payload['jti'])) {
            $this->jwtService->revoke($payload['jti']);
        }
    }

    public function userPayload(User $user): array
    {
        if ($user->isPastMembershipRenewalGracePeriod()) {
            $user->delete();
            abort(410, 'This account has passed the renewal deadline and has been removed.');
        }

        $user->activateDueMembershipRenewal();
        $user->refresh()->loadMissing(['role', 'membershipPackage', 'renewalPackage']);

        $application = TrainerApplication::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();

        $canAccessTrainerWorkspace = $application?->status === 'approved'
            || TrainerDetail::query()->where('user_id', $user->id)->exists();

        return [
            'id' => $user->id,
            'email' => $user->email,
            'full_name' => $user->full_name,
            'phone' => $user->phone,
            'birth_date' => optional($user->birth_date)->toDateString(),
            'profile_picture_url' => $user->profile_picture_url,
            'role' => $user->roleName(),
            'is_active' => $user->is_active,
            'email_verified_at' => optional($user->email_verified_at)->toISOString(),
            'membership_package_id' => $user->membership_package_id,
            'membership_started_at' => optional($user->membership_started_at)->toISOString(),
            'membership_expires_at' => optional($user->membership_expires_at)->toISOString(),
            'free_class_access' => $user->free_class_access,
            'membership_package' => $user->membershipPackage ? [
                'id' => $user->membershipPackage->id,
                'code' => $user->membershipPackage->code,
                'name' => $user->membershipPackage->name,
                'duration_months' => $user->membershipPackage->duration_months,
                'price' => $user->membershipPackage->price,
                'free_class_access' => $user->membershipPackage->free_class_access,
                'benefits' => $user->membershipPackage->benefits,
                'is_active' => $user->membershipPackage->is_active,
            ] : null,
            'membership_status' => $user->membershipStatus(),
            'membership_days_left' => $user->membershipDaysLeft(),
            'membership_is_expired' => $user->isMembershipExpired(),
            'membership_expires_within_3_days' => $user->membershipStatus() === 'expiring_soon',
            'membership_renewal_deadline_at' => optional($user->membershipRenewalDeadline())->toISOString(),
            'renewal_package_id' => $user->renewal_package_id,
            'membership_renewal_starts_at' => optional($user->membership_renewal_starts_at)->toISOString(),
            'membership_renewal_expires_at' => optional($user->membership_renewal_expires_at)->toISOString(),
            'renewal_package' => $user->renewalPackage ? [
                'id' => $user->renewalPackage->id,
                'code' => $user->renewalPackage->code,
                'name' => $user->renewalPackage->name,
                'duration_months' => $user->renewalPackage->duration_months,
                'price' => $user->renewalPackage->price,
                'free_class_access' => $user->renewalPackage->free_class_access,
                'benefits' => $user->renewalPackage->benefits,
                'is_active' => $user->renewalPackage->is_active,
            ] : null,
            'trainer_status' => $application?->status ?? 'not_submitted',
            'can_access_trainer_workspace' => $canAccessTrainerWorkspace,
            'trainer_application_id' => $application?->id,
        ];
    }
}
