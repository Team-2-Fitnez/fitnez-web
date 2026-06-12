<?php

use App\Http\Controllers\TrainerApplicationController;
use Illuminate\Support\Facades\Route;

Route::get('/trainer/application', [TrainerApplicationController::class, 'status']);
Route::post('/trainer/application', [TrainerApplicationController::class, 'store']);
Route::post('/trainer/workspace/enter', [TrainerApplicationController::class, 'enterWorkspace']);
Route::post('/trainer/workspace/leave', [TrainerApplicationController::class, 'leaveWorkspace']);
