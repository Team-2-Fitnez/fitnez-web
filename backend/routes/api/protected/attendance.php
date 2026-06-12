<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Middleware\EnsureActiveMembership;
use Illuminate\Support\Facades\Route;

Route::prefix('attendance')->middleware(EnsureActiveMembership::class)->group(function () {
    Route::post('/check-in', [AttendanceController::class, 'checkIn']);
    Route::post('/check-out', [AttendanceController::class, 'checkOut']);
    Route::get('/history', [AttendanceController::class, 'myHistory']);
});
