<?php

namespace App\Features\Payments\Controllers;

use App\Http\Controllers\Controller;

use App\Models\MembershipPackage;
use App\Models\Notification;
use App\Models\Payment;
use App\Support\ActionConfirmation;
use App\Support\ApiResponse;
use App\Support\QueryLimit;
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
            if ($response = ActionConfirmation::require($request, 'delete expired account', $user->email ?? 'current account')) {
                return $response;
            }

            $user->delete();

            return ApiResponse::error('This account has passed the renewal deadline and has been removed.', ['deleted_id' => $user->id], 410);
        }

        $package = MembershipPackage::query()
            ->where('id', $data['membership_package_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $payment = Payment::query()->create([
            'invoice_number' => 'INV-RENEW-' . strtoupper(Str::random(10)),
            'user_id' => $user->id,
            'membership_package_id' => $package->id,
            'payment_type' => 'membership_renewal',
            'amount' => $package->price,
            'payment_method' => 'qris',
            'payment_status' => 'pending',
            'external_reference' => 'RENEW-' . strtoupper(Str::random(12)),
        ]);

        return ApiResponse::success('Renewal request created. Please upload payment proof.', [
            'payment' => $payment,
            'membership' => $this->statusPayload($user),
        ], 201);
    }

    public function uploadProof(Request $request, Payment $payment)
    {
        $user = $request->user();
        if ($payment->user_id !== $user->id) {
            return ApiResponse::error('Not authorized.', [], 403);
        }

        if ($payment->payment_status !== 'pending') {
            return ApiResponse::error('Cannot upload proof for this payment status.', [], 422);
        }

        $data = $request->validate([
            'payment_proof' => 'required|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $path = $request->file('payment_proof')->store('payment-proofs', 'public');

        $payment->update([
            'payment_proof_path' => $path,
            'payment_status' => 'pending_review',
        ]);

        Notification::query()->create([
            'user_id' => $user->id,
            'title' => 'Renewal Proof Submitted',
            'body' => "Payment proof for your membership renewal has been submitted. Admin will verify it within 2x24 hours.",
            'notification_type' => 'membership_renewal',
            'is_read' => false,
        ]);

        return ApiResponse::success('Payment proof uploaded. Awaiting admin confirmation.', [
            'payment' => $payment->fresh(),
            'membership' => $this->statusPayload($user),
        ]);
    }

    public function pendingRenewals(Request $request)
    {
        $payments = Payment::query()
            ->with(['user', 'membershipPackage'])
            ->where('payment_type', 'membership_renewal')
            ->where('payment_status', 'pending_review')
            ->orderByDesc('id')
            ->paginate(QueryLimit::perPage($request, 20, 100));

        return ApiResponse::success('Pending membership renewals loaded.', $payments);
    }

    public function confirmRenewal(Request $request, Payment $payment)
    {
        if ($response = ActionConfirmation::require($request, 'confirm membership renewal', 'payment #' . $payment->id)) {
            return $response;
        }

        if ($payment->payment_type !== 'membership_renewal' || $payment->payment_status !== 'pending_review') {
            return ApiResponse::error('Cannot confirm this payment.', [], 422);
        }

        $package = MembershipPackage::findOrFail($payment->membership_package_id);
        $user = $payment->user;

        DB::transaction(function () use ($payment, $user, $package) {
            $currentExpiresAt = $user->membership_expires_at;
            $startsAt = $currentExpiresAt && $currentExpiresAt->isFuture()
                ? $currentExpiresAt->copy()
                : now();
            $expiresAt = $startsAt->copy()->addMonths($package->duration_months);

            $payment->update([
                'payment_status' => 'paid',
                'payment_date' => now(),
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
        });

        return ApiResponse::success('Membership renewal payment confirmed.');
    }

    public function rejectRenewal(Request $request, Payment $payment)
    {
        if ($response = ActionConfirmation::require($request, 'reject membership renewal', 'payment #' . $payment->id)) {
            return $response;
        }

        if ($payment->payment_type !== 'membership_renewal' || $payment->payment_status !== 'pending_review') {
            return ApiResponse::error('Cannot reject this payment.', [], 422);
        }

        $data = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $payment->update([
            'payment_status' => 'failed',
        ]);

        Notification::query()->create([
            'user_id' => $payment->user_id,
            'title' => 'Renewal Payment Rejected',
            'body' => "Your membership renewal payment proof was rejected. Reason: " . ($data['reason'] ?? 'The transfer proof is invalid.'),
            'notification_type' => 'membership_renewal',
            'is_read' => false,
        ]);

        return ApiResponse::success('Membership renewal payment rejected.');
    }

    public function destroyAccount(Request $request)
    {
        $user = $request->user();
        if ($response = ActionConfirmation::require($request, 'delete account', $user->email ?? 'current account')) {
            return $response;
        }

        $user->delete();

        return ApiResponse::success('Your account has been deleted.', ['deleted_id' => $user->id]);
    }

    private function statusPayload($user): array
    {
        $pendingRenewal = Payment::query()
            ->where('user_id', $user->id)
            ->where('payment_type', 'membership_renewal')
            ->where('payment_status', 'pending_review')
            ->first();

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
            'pending_renewal_payment' => $pendingRenewal ? [
                'id' => $pendingRenewal->id,
                'amount' => $pendingRenewal->amount,
                'package_name' => optional($pendingRenewal->membershipPackage)->name,
            ] : null,
        ];
    }
}
