<?php

use App\Http\Controllers\Analytics\LandingVisitController;
use Illuminate\Support\Facades\Route;

Route::prefix('analytics')->group(function () {
    Route::post('/landing-visit', [LandingVisitController::class, 'store']);
    Route::post('/landing-visit/heartbeat', [LandingVisitController::class, 'heartbeat']);
});
