<?php

use App\Http\Controllers\Api\TrainerWorkspaceController;
use App\Http\Middleware\EnsureTrainerWorkspaceAccess;
use Illuminate\Support\Facades\Route;

Route::prefix('trainer')->middleware(EnsureTrainerWorkspaceAccess::class)->group(function () {
    Route::get('/member-monitoring/summary', [TrainerWorkspaceController::class, 'monitoringSummary']);
    Route::get('/member-monitoring/members', [TrainerWorkspaceController::class, 'monitoringMembers']);
    Route::get('/member-monitoring/members/{member}', [TrainerWorkspaceController::class, 'monitoringMember']);

    Route::get('/incoming-rent-history/summary', [TrainerWorkspaceController::class, 'rentSummary']);
    Route::get('/incoming-rent-history/breakdown', [TrainerWorkspaceController::class, 'rentBreakdown']);
    Route::get('/incoming-rent-history', [TrainerWorkspaceController::class, 'rentHistory']);
});
