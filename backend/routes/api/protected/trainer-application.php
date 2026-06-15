<?php

use App\Http\Controllers\Api\TrainerController;
use Illuminate\Support\Facades\Route;

Route::get('/trainer/application', [TrainerController::class, 'applicationStatus']);
Route::post('/trainer/application', [TrainerController::class, 'storeApplication']);
Route::post('/trainer/workspace/enter', [TrainerController::class, 'enterWorkspace']);
Route::post('/trainer/workspace/leave', [TrainerController::class, 'leaveWorkspace']);
