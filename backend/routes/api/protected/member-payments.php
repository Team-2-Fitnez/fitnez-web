<?php

use App\Http\Controllers\Api\PaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('member/payments')->group(function () {
    Route::get('/', [PaymentController::class, 'index']);
    Route::get('/summary', [PaymentController::class, 'summary']);
    Route::post('/pay', [PaymentController::class, 'pay']);
    Route::post('/simulate-create', [PaymentController::class, 'simulateCreate']);
    Route::post('/simulate-pay', [PaymentController::class, 'simulatePay']);
});
