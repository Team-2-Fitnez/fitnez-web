<?php

namespace App\Http\Controllers\Api;

use App\Features\Auth\Controllers\AuthController as FeatureAuthController;
use App\Features\Auth\Controllers\ManualProspectiveRegistrationController;
use App\Features\Auth\Controllers\OtpController;
use App\Features\Auth\Requests\Auth\RegistrationStatusRequest;
use App\Features\Auth\Requests\Auth\StartManualProspectiveRegistrationRequest;
use App\Features\Auth\Requests\Auth\UploadManualPaymentProofRequest;
use App\Features\Auth\Requests\Otp\SendOtpRequest;
use App\Features\Auth\Requests\Otp\VerifyOtpRequest;
use App\Features\Auth\Actions\Auth\StartManualProspectiveRegistrationAction;
use App\Features\Auth\Actions\Otp\SendOtpAction;
use App\Features\Auth\Actions\Otp\VerifyOtpAction;

class AuthController extends FeatureAuthController
{
    public function start(StartManualProspectiveRegistrationRequest $request, StartManualProspectiveRegistrationAction $action)
    {
        return app(ManualProspectiveRegistrationController::class)->start($request, $action);
    }

    public function uploadProof(UploadManualPaymentProofRequest $request)
    {
        return app(ManualProspectiveRegistrationController::class)->uploadProof($request);
    }

    public function status(RegistrationStatusRequest $request)
    {
        return app(ManualProspectiveRegistrationController::class)->status($request);
    }

    public function send(SendOtpRequest $request, SendOtpAction $action)
    {
        return app(OtpController::class)->send($request, $action);
    }

    public function verify(VerifyOtpRequest $request, VerifyOtpAction $action)
    {
        return app(OtpController::class)->verify($request, $action);
    }
}
