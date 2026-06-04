<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Auth\CreateMemberFromApprovedRegistrationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexTableRequest;
use App\Http\Requests\Admin\RejectProspectiveMemberRequest;
use App\Models\ProspectiveMemberRegistration;
use App\Support\ApiResponse;
use App\Support\SearchTerm;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProspectiveMemberReviewController extends Controller
{
    public function index(IndexTableRequest $request)
    {
        $data = $request->validated();
        $search = SearchTerm::contains($data['search'] ?? null);

        $registrations = ProspectiveMemberRegistration::query()
            ->with(['package', 'paymentMethod', 'user', 'admin'])
            ->when($data['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('registration_code', 'ilike', $search)
                        ->orWhere('full_name', 'ilike', $search)
                        ->orWhere('email', 'ilike', $search);
                });
            })
            ->orderByDesc('id')
            ->paginate($request->perPage(15));

        return ApiResponse::success('Prospective member registrations loaded.', $registrations);
    }

    public function approve(ProspectiveMemberRegistration $registration, CreateMemberFromApprovedRegistrationAction $action)
    {
        if ($registration->status !== 'awaiting_admin_review') {
            return ApiResponse::error('Only registrations awaiting admin review can be approved.', [], 422);
        }

        if (! $registration->payment_proof_path) {
            return ApiResponse::error('Payment proof is missing.', [], 422);
        }

        $user = $action->handle($registration);

        $registration->update([
            'status' => 'approved',
            'admin_id' => request()->user()?->id,
            'approved_at' => now(),
        ]);

        $registration->refresh()->load(['package', 'paymentMethod', 'user']);

        $this->notifyApplicantApproved($registration);

        return ApiResponse::success('Registration approved and member account activated.', [
            'registration' => $registration,
            'user' => $user->fresh(['role', 'membershipPackage']),
        ]);
    }

    public function reject(RejectProspectiveMemberRequest $request, ProspectiveMemberRegistration $registration)
    {
        if (! in_array($registration->status, ['awaiting_admin_review', 'awaiting_payment'], true)) {
            return ApiResponse::error('This registration cannot be rejected in its current status.', [], 422);
        }

        $registration->update([
            'status' => 'rejected',
            'admin_id' => request()->user()?->id,
            'rejected_at' => now(),
            'rejection_reason' => $request->validated('reason'),
        ]);

        $registration->refresh()->load(['package', 'paymentMethod']);

        $this->notifyApplicantRejected($registration);

        return ApiResponse::success('Registration rejected.', $registration);
    }

    private function notifyApplicantApproved(ProspectiveMemberRegistration $registration): void
    {
        $loginUrl = rtrim((string) env('FRONTEND_URL', 'http://localhost:5173'), '/') . '/login/member';

        $body = "Hello {$registration->full_name},\n\n"
            . "Your Fitnez registration has been approved.\n"
            . "Your member account is now active.\n\n"
            . "Package: {$registration->package?->name}\n"
            . "Amount: Rp " . number_format((int) $registration->amount, 0, ',', '.') . "\n"
            . "Registration Code: {$registration->registration_code}\n\n"
            . "You can login here:\n{$loginUrl}\n\n"
            . "Thank you.";

        try {
            Mail::raw($body, function ($message) use ($registration) {
                $message->to($registration->email)
                    ->subject('Fitnez Registration Approved');
            });
        } catch (\Throwable $e) {
            Log::warning('FITNEZ_APPROVAL_EMAIL_FAILED', [
                'email' => $registration->email,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('FITNEZ_REGISTRATION_APPROVED', [
            'email' => $registration->email,
            'registration_code' => $registration->registration_code,
            'user_id' => $registration->user_id,
        ]);
    }

    private function notifyApplicantRejected(ProspectiveMemberRegistration $registration): void
    {
        $statusUrl = rtrim((string) env('FRONTEND_URL', 'http://localhost:5173'), '/') . '/registration-status';

        $body = "Hello {$registration->full_name},\n\n"
            . "Your Fitnez registration was rejected.\n\n"
            . "Reason:\n{$registration->rejection_reason}\n\n"
            . "Registration Code: {$registration->registration_code}\n"
            . "You can check your status here:\n{$statusUrl}\n\n"
            . "Please contact admin if you need help.";

        try {
            Mail::raw($body, function ($message) use ($registration) {
                $message->to($registration->email)
                    ->subject('Fitnez Registration Rejected');
            });
        } catch (\Throwable $e) {
            Log::warning('FITNEZ_REJECTION_EMAIL_FAILED', [
                'email' => $registration->email,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('FITNEZ_REGISTRATION_REJECTED', [
            'email' => $registration->email,
            'registration_code' => $registration->registration_code,
            'reason' => $registration->rejection_reason,
        ]);
    }
}
