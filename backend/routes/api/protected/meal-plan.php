<?php

use App\Http\Controllers\MealPlanController;
use App\Http\Middleware\EnsureActiveMembership;
use Illuminate\Support\Facades\Route;

Route::prefix('user')->middleware(EnsureActiveMembership::class)->group(function () {
    Route::get('/meal_plan', [MealPlanController::class, 'getMealPlan']);
    Route::put('/meal_plan', [MealPlanController::class, 'saveMealPlan']);
    Route::get('/food_log', [MealPlanController::class, 'getFoodLog']);
    Route::post('/food_log', [MealPlanController::class, 'addFood']);
    Route::delete('/food_log/{id}', [MealPlanController::class, 'deleteFood']);
});
