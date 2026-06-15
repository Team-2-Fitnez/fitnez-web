<?php
namespace App\Features\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Features\Auth\Actions\Otp\SendOtpAction;
use App\Features\Auth\Actions\Otp\VerifyOtpAction;
use App\Features\Auth\Requests\Otp\SendOtpRequest;
use App\Features\Auth\Requests\Otp\VerifyOtpRequest;
use App\Support\ApiResponse;
class OtpController extends Controller {
    public function send(SendOtpRequest $request, SendOtpAction $action) { return ApiResponse::success('OTP sent. Check Laravel log in local development.', $action->handle($request->validated(), $request)); }
    public function verify(VerifyOtpRequest $request, VerifyOtpAction $action) { return ApiResponse::success('OTP verified successfully.', $action->handle($request->validated())); }
}
