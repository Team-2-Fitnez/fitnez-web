<?php

use App\Http\Controllers\Api\AdminReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('analytics')->group(function () {
    Route::post('/landing-visit', [AdminReportController::class, 'storeLandingVisit']);
    Route::post('/landing-visit/heartbeat', [AdminReportController::class, 'heartbeatLandingVisit']);
});
