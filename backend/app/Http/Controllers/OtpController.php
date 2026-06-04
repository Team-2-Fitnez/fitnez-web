<?php
namespace App\Http\Controllers;
use App\Actions\Otp\SendOtpAction;
use App\Actions\Otp\VerifyOtpAction;
use App\Http\Requests\Otp\SendOtpRequest;
use App\Http\Requests\Otp\VerifyOtpRequest;
use App\Support\ApiResponse;
class OtpController extends Controller {
    public function send(SendOtpRequest $request, SendOtpAction $action) { return ApiResponse::success('OTP sent. Check Laravel log in local development.', $action->handle($request->validated(), $request)); }
    public function verify(VerifyOtpRequest $request, VerifyOtpAction $action) { return ApiResponse::success('OTP verified successfully.', $action->handle($request->validated())); }
}
