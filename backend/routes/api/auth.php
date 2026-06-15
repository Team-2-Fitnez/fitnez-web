<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Middleware\JwtAuthenticate;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/prospective-registration/start', [AuthController::class, 'start']);
    Route::post('/prospective-registration/upload-proof', [AuthController::class, 'uploadProof']);
    Route::get('/prospective-registration/status', [AuthController::class, 'status']);

    Route::post('/register-prospective-member', [AuthController::class, 'registerProspectiveMember']);
    Route::post('/request-login-otp', [AuthController::class, 'requestLoginOtp']);
    Route::post('/verify-login-otp', [AuthController::class, 'verifyLoginOtp']);
    Route::post('/password/forgot', [AuthController::class, 'forgotPassword']);
    Route::post('/password/reset', [AuthController::class, 'resetPassword']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/member-login', [AuthController::class, 'memberLogin']);

    Route::post('/otp/send', [AuthController::class, 'send']);
    Route::post('/otp/verify', [AuthController::class, 'verify']);

    Route::middleware(JwtAuthenticate::class)->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::patch('/profile', [AuthController::class, 'updateProfile']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});
