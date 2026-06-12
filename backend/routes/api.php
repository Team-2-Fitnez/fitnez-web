<?php

use App\Http\Controllers\Admin\TrainerManagementController;
use Illuminate\Support\Facades\Route;

require __DIR__ . '/api/public.php';
require __DIR__ . '/api/analytics.php';
require __DIR__ . '/api/auth.php';

Route::get('/trainers/list', [TrainerManagementController::class, 'publicList']);

require __DIR__ . '/api/protected.php';
