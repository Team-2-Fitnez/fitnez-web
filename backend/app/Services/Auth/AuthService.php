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
        $user->loadMissing(['role', 'membershipPackage']);

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
            'trainer_status' => $application?->status ?? 'not_submitted',
            'can_access_trainer_workspace' => $canAccessTrainerWorkspace,
            'trainer_application_id' => $application?->id,
        ];
    }
}
