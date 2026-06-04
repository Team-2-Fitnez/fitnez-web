<?php

namespace App\Http\Controllers;

use App\Actions\Auth\GetAuthenticatedUserAction;
use App\Actions\Auth\LoginMemberAction;
use App\Actions\Auth\LogoutUserAction;
use App\Actions\Auth\RegisterProspectiveMemberAction;
use App\Actions\Auth\RequestLoginOtpAction;
use App\Actions\Auth\VerifyLoginOtpAction;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginMemberRequest;
use App\Http\Requests\Auth\RegisterProspectiveMemberRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\VerifyLoginOtpRequest;
use App\Models\User;
use App\Services\Auth\AuthCookie;
use App\Services\Otp\OtpManager;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function registerProspectiveMember(
        RegisterProspectiveMemberRequest $request,
        RegisterProspectiveMemberAction $action,
    ) {
        return ApiResponse::success(
            'Registration successful. Please verify OTP.',
            $action->handle($request->validated(), $request),
            201
        );
    }

    public function requestLoginOtp(LoginMemberRequest $request, RequestLoginOtpAction $action)
    {
        return ApiResponse::success(
            'Password reset OTP has been sent to your email.',
            $action->handle($request->validated(), $request)
        );
    }

    public function verifyLoginOtp(VerifyLoginOtpRequest $request, VerifyLoginOtpAction $action, AuthCookie $cookie)
    {
        $result = $action->handle($request->validated(), $request);

        return ApiResponse::success('Password reset OTP verified.', $result);
    }

    public function forgotPassword(ForgotPasswordRequest $request, OtpManager $otpManager)
    {
        $data = $request->validated();
        $user = User::query()->where('email', $data['email'])->first();

        if ($user) {
            $otpManager->sendForUser($user, 'password_reset', $request->ip());
        }

        return ApiResponse::success('If the email exists, a password reset OTP has been sent.', [
            'email' => $data['email'],
            'purpose' => 'password_reset',
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request, OtpManager $otpManager)
    {
        $data = $request->validated();
        $user = $otpManager->verify($data['email'], $data['otp'], 'password_reset');

        $user->forceFill([
            'password_hash' => Hash::make($data['password']),
            'is_active' => true,
            'email_verified_at' => $user->email_verified_at ?: now(),
        ])->save();

        return ApiResponse::success('Password has been reset. Please login with the new password.');
    }

    public function login(LoginMemberRequest $request, LoginMemberAction $action, AuthCookie $cookie)
    {
        $result = $action->handle($request->validated(), $request);
        $response = ApiResponse::success('Login successful.', $result);

        if (($request->validated('channel') ?? 'web') === 'web') {
            return $cookie->attach($response, $result['access_token']);
        }

        return $response;
    }

    public function memberLogin(LoginMemberRequest $request, LoginMemberAction $action, AuthCookie $cookie)
    {
        $result = $action->handle($request->validated(), $request);
        $response = ApiResponse::success('Member login successful.', $result);

        if (($request->validated('channel') ?? 'web') === 'web') {
            return $cookie->attach($response, $result['access_token']);
        }

        return $response;
    }

    public function me(GetAuthenticatedUserAction $action)
    {
        return ApiResponse::success('Authenticated user.', $action->handle(request()));
    }

    public function logout(LogoutUserAction $action, AuthCookie $cookie)
    {
        $action->handle(request());

        return $cookie->forget(ApiResponse::success('Logout successful.'));
    }
}
