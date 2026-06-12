<?php

use App\Http\Controllers\Trainer\IncomingRentHistoryController;
use App\Http\Controllers\Trainer\MemberFitnessMonitoringController;
use App\Http\Middleware\EnsureTrainerWorkspaceAccess;
use Illuminate\Support\Facades\Route;

Route::prefix('trainer')->middleware(EnsureTrainerWorkspaceAccess::class)->group(function () {
    Route::get('/member-monitoring/summary', [MemberFitnessMonitoringController::class, 'summary']);
    Route::get('/member-monitoring/members', [MemberFitnessMonitoringController::class, 'members']);
    Route::get('/member-monitoring/members/{member}', [MemberFitnessMonitoringController::class, 'show']);

    Route::get('/incoming-rent-history/summary', [IncomingRentHistoryController::class, 'summary']);
    Route::get('/incoming-rent-history/breakdown', [IncomingRentHistoryController::class, 'breakdown']);
    Route::get('/incoming-rent-history', [IncomingRentHistoryController::class, 'index']);
});
