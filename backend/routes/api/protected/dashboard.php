<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard/summary', [DashboardController::class, 'summary']);
Route::get('/dashboard/stream', [DashboardController::class, 'stream']);
