<?php

use App\Http\Controllers\MemberPaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('member/payments')->group(function () {
    Route::get('/', [MemberPaymentController::class, 'index']);
    Route::get('/summary', [MemberPaymentController::class, 'summary']);
    Route::post('/pay', [MemberPaymentController::class, 'pay']);
    Route::post('/simulate-create', [MemberPaymentController::class, 'simulateCreate']);
    Route::post('/simulate-pay', [MemberPaymentController::class, 'simulatePay']);
});
