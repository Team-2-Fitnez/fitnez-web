<?php

use App\Features\Health\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthController::class, 'index']);
