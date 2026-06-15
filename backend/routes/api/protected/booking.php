<?php

use App\Http\Controllers\Api\BookingController;
use App\Http\Middleware\EnsureActiveMembership;
use Illuminate\Support\Facades\Route;

Route::middleware(EnsureActiveMembership::class)->group(function () {
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::post('/bookings/{booking}/upload-proof', [BookingController::class, 'uploadPaymentProof']);
    Route::patch('/bookings/{id}/status', [BookingController::class, 'updateStatus']);
    Route::get('/bookings/{booking}/session-dates', [BookingController::class, 'sessionDates']);
});
