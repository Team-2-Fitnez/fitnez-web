<?php

namespace App\Http\Controllers;

use App\Actions\Auth\StartManualProspectiveRegistrationAction;
use App\Http\Requests\Auth\RegistrationStatusRequest;
use App\Http\Requests\Auth\StartManualProspectiveRegistrationRequest;
use App\Http\Requests\Auth\UploadManualPaymentProofRequest;
use App\Models\ProspectiveMemberRegistration;
use App\Support\ApiResponse;

class ManualProspectiveRegistrationController extends Controller
{
    public function start(StartManualProspectiveRegistrationRequest $request, StartManualProspectiveRegistrationAction $action)
    {
        return ApiResponse::success(
            'Registration created. Please complete payment and upload proof.',
            $action->handle($request->validated()),
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
}
