<?php

use App\Http\Controllers\Api\TrainerController;
use Illuminate\Support\Facades\Route;

require __DIR__ . '/api/public.php';
require __DIR__ . '/api/analytics.php';
require __DIR__ . '/api/auth.php';

Route::get('/trainers/list', [TrainerController::class, 'publicList']);

require __DIR__ . '/api/protected.php';
