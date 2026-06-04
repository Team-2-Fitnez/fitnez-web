<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Auth\CreateMemberFromApprovedRegistrationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexTableRequest;
use App\Http\Requests\Admin\RejectProspectiveMemberRequest;
use App\Models\ProspectiveMemberRegistration;
use App\Models\Payment;
use App\Support\ApiResponse;
use App\Support\SearchTerm;
use Illuminate\Support\Facades\DB;
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

        Payment::query()->firstOrCreate(
            ['external_reference' => $registration->registration_code, 'payment_type' => 'membership'],
            [
                'invoice_number' => 'FITNEZ-MBR-'.$registration->id.'-'.now()->format('YmdHis'),
                'user_id' => $user->id,
                'booking_id' => null,
                'amount' => $registration->amount,
                'payment_method' => $registration->paymentMethod?->name ?? 'manual',
                'payment_status' => 'paid',
                'status' => 'paid',
                'type' => 'membership',
                'payment_date' => now(),
                'paid_at' => now(),
                'proof_path' => $registration->payment_proof_path,
                'notes' => 'Membership registration payment approved by admin.',
            ]
        );

        $registration->refresh()->load(['package', 'paymentMethod', 'user']);

        $this->logReviewEvent($registration, 'REGISTRATION_APPROVED', 'Admin approved prospective member registration');

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

        $this->logReviewEvent($registration, 'REGISTRATION_REJECTED', 'Admin rejected prospective member registration');

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

    private function logReviewEvent(ProspectiveMemberRegistration $registration, string $action, string $description): void
    {
        if (! DB::getSchemaBuilder()->hasTable('system_logs')) {
            return;
        }

        DB::table('system_logs')->insert([
            'user_id' => $registration->user_id,
            'action_type' => $action,
            'table_affected' => 'prospective_member_registrations',
            'record_id' => $registration->id,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => json_encode([
                'email' => $registration->email,
                'status' => $registration->status,
                'source' => 'prospective_registration',
                'admin_id' => request()->user()?->id,
            ]),
            'created_at' => now(),
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
