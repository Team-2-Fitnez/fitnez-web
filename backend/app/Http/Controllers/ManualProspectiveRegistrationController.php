<?php

namespace App\Http\Controllers;

use App\Actions\Auth\StartManualProspectiveRegistrationAction;
use App\Http\Requests\Auth\RegistrationStatusRequest;
use App\Http\Requests\Auth\StartManualProspectiveRegistrationRequest;
use App\Http\Requests\Auth\UploadManualPaymentProofRequest;
use App\Models\ProspectiveMemberRegistration;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\DB;

class ManualProspectiveRegistrationController extends Controller
{
    public function start(StartManualProspectiveRegistrationRequest $request, StartManualProspectiveRegistrationAction $action)
    {
        $registration = $action->handle($request->validated());
        $this->logRegistrationEvent($registration, 'REGISTRATION_SUBMITTED', 'Prospective member submitted manual registration', $request);

        return ApiResponse::success(
            'Registration created. Please complete payment and upload proof.',
            $registration,
            201
        );
    }

    public function uploadProof(UploadManualPaymentProofRequest $request)
    {
        $data = $request->validated();

        $registration = ProspectiveMemberRegistration::query()
            ->where('registration_code', $data['registration_code'])
            ->where('email', $data['email'])
            ->firstOrFail();

        if (! in_array($registration->status, ['awaiting_payment', 'rejected'], true)) {
            return ApiResponse::error('This registration cannot upload proof in its current status.', [], 422);
        }

        $path = $request->file('payment_proof')->store('payment-proofs', 'public');

        $registration->update([
            'payment_proof_path' => $path,
            'payment_submitted_at' => now(),
            'status' => 'awaiting_admin_review',
            'rejection_reason' => null,
            'rejected_at' => null,
        ]);

        $this->logRegistrationEvent($registration->fresh(), 'REGISTRATION_PAYMENT_PROOF_UPLOADED', 'Prospective member uploaded payment proof', $request);

        return ApiResponse::success(
            'Payment proof uploaded. Please wait for admin verification.',
            $registration->fresh(['package', 'paymentMethod'])
        );
    }

    public function status(RegistrationStatusRequest $request)
    {
        $data = $request->validated();

        $registration = ProspectiveMemberRegistration::query()
            ->with(['package', 'paymentMethod'])
            ->where('registration_code', $data['registration_code'])
            ->where('email', $data['email'])
            ->firstOrFail();

        return ApiResponse::success('Registration status loaded.', $registration);
    }

    private function logRegistrationEvent(ProspectiveMemberRegistration $registration, string $action, string $description, $request): void
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
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => json_encode([
                'email' => $registration->email,
                'status' => $registration->status,
                'source' => 'prospective_registration',
            ]),
            'created_at' => now(),
        ]);
    }
}
