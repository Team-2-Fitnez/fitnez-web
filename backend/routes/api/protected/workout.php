<?php

use App\Http\Controllers\Api\WorkoutPlanController;
use App\Http\Middleware\EnsureActiveMembership;
use Illuminate\Support\Facades\Route;

Route::middleware(EnsureActiveMembership::class)->group(function () {
    Route::delete('/workout-plans/clear-all', [WorkoutPlanController::class, 'clearAll']);
    Route::apiResource('workout-plans', WorkoutPlanController::class);
});
