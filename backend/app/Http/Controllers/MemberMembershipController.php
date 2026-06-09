<?php

namespace App\Http\Controllers;

use App\Models\MembershipPackage;
use App\Models\Notification;
use App\Models\Payment;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MemberMembershipController extends Controller
{
    public function status(Request $request)
    {
        $user = $request->user();
        $user->activateDueMembershipRenewal();
        $user->refresh()->loadMissing(['membershipPackage', 'renewalPackage']);

        return ApiResponse::success('Membership status loaded.', $this->statusPayload($user));
    }

    public function renew(Request $request)
    {
        $data = $request->validate([
            'membership_package_id' => ['required', 'integer', 'exists:membership_packages,id'],
        ]);

        $user = $request->user();
        $user->activateDueMembershipRenewal();
        $user->refresh()->loadMissing(['membershipPackage', 'renewalPackage']);

        if ($user->renewal_package_id && $user->membership_renewal_starts_at?->isFuture()) {
            return ApiResponse::error('A renewal package is already queued. Wait until it starts before buying another renewal.', [], 422);
        }

        if ($user->isPastMembershipRenewalGracePeriod()) {
            $user->delete();
            return ApiResponse::error('This account has passed the renewal deadline and has been removed.', [], 410);
        }

        $package = MembershipPackage::query()
            ->where('id', $data['membership_package_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $result = DB::transaction(function () use ($user, $package) {
            $currentExpiresAt = $user->membership_expires_at;
            $startsAt = $currentExpiresAt && $currentExpiresAt->isFuture()
                ? $currentExpiresAt->copy()
                : now();
            $expiresAt = $startsAt->copy()->addMonths($package->duration_months);

            $payment = Payment::query()->create([
                'invoice_number' => 'INV-RENEW-' . strtoupper(Str::random(10)),
                'user_id' => $user->id,
                'payment_type' => 'membership_renewal',
                'amount' => $package->price,
                'payment_method' => 'membership_renewal_simulation',
                'payment_status' => 'paid',
                'payment_date' => now(),
                'external_reference' => 'RENEW-' . strtoupper(Str::random(12)),
            ]);

            if ($currentExpiresAt && $currentExpiresAt->isFuture()) {
                $user->forceFill([
                    'renewal_package_id' => $package->id,
                    'membership_renewal_starts_at' => $startsAt,
                    'membership_renewal_expires_at' => $expiresAt,
                ])->save();
            } else {
                $user->forceFill([
                    'membership_package_id' => $package->id,
                    'membership_started_at' => $startsAt,
                    'membership_expires_at' => $expiresAt,
                    'free_class_access' => (bool) $package->free_class_access,
                    'renewal_package_id' => null,
                    'membership_renewal_starts_at' => null,
                    'membership_renewal_expires_at' => null,
                ])->save();
            }

            Notification::query()->create([
                'user_id' => $user->id,
                'title' => 'Membership Renewal Successful',
                'body' => $currentExpiresAt && $currentExpiresAt->isFuture()
                    ? "Your {$package->name} package has been queued and will start after your current package ends."
                    : "Your {$package->name} package is now active.",
                'notification_type' => 'membership_renewal',
                'is_read' => false,
            ]);

            return [
                'payment' => $payment,
                'user' => $user->fresh(['membershipPackage', 'renewalPackage']),
            ];
        });

        return ApiResponse::success('Membership renewal payment completed.', [
            'payment' => $result['payment'],
            'membership' => $this->statusPayload($result['user']),
        ], 201);
    }

    public function destroyAccount(Request $request)
    {
        $user = $request->user();
        $user->delete();

        return ApiResponse::success('Your account has been deleted.');
    }

    private function statusPayload($user): array
    {
        return [
            'status' => $user->membershipStatus(),
            'is_expired' => $user->isMembershipExpired(),
            'is_expiring_soon' => $user->membershipStatus() === 'expiring_soon',
            'days_left' => $user->membershipDaysLeft(),
            'renewal_deadline_at' => optional($user->membershipRenewalDeadline())->toISOString(),
            'membership_package' => $user->membershipPackage,
            'membership_started_at' => optional($user->membership_started_at)->toISOString(),
            'membership_expires_at' => optional($user->membership_expires_at)->toISOString(),
            'queued_membership_package' => $user->renewalPackage,
            'queued_membership_starts_at' => optional($user->membership_renewal_starts_at)->toISOString(),
            'queued_membership_expires_at' => optional($user->membership_renewal_expires_at)->toISOString(),
        ];
    }
}
