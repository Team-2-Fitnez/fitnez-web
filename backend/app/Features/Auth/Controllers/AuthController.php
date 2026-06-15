<?php

namespace App\Features\Auth\Controllers;

use App\Http\Controllers\Controller;

use App\Features\Auth\Actions\Auth\GetAuthenticatedUserAction;
use App\Features\Auth\Actions\Auth\LoginMemberAction;
use App\Features\Auth\Actions\Auth\LogoutUserAction;
use App\Features\Auth\Actions\Auth\RegisterProspectiveMemberAction;
use App\Features\Auth\Actions\Auth\RequestLoginOtpAction;
use App\Features\Auth\Actions\Auth\VerifyLoginOtpAction;
use App\Features\Auth\Requests\Auth\ForgotPasswordRequest;
use App\Features\Auth\Requests\Auth\LoginMemberRequest;
use App\Features\Auth\Requests\Auth\RegisterProspectiveMemberRequest;
use App\Features\Auth\Requests\Auth\ResetPasswordRequest;
use App\Features\Auth\Requests\Auth\UpdateProfileRequest;
use App\Features\Auth\Requests\Auth\VerifyLoginOtpRequest;
use App\Models\User;
use App\Services\Auth\AuthCookie;
use App\Services\Auth\AuthService;
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

    public function updateProfile(UpdateProfileRequest $request, AuthService $authService)
    {
        $user = $request->user();
        $data = $request->validated();

        $user->forceFill([
            'full_name' => $data['full_name'],
            'age' => $data['age'] ?? null,
            'phone' => $data['phone'] ?? null,
        ])->save();

        return ApiResponse::success('Profile updated successfully.', $authService->userPayload($user->refresh()));
    }

    public function logout(LogoutUserAction $action, AuthCookie $cookie)
    {
        $action->handle(request());

        return $cookie->forget(ApiResponse::success('Logout successful.'));
    }
}
