<?php

use App\Features\Sse\Controllers\SseController;
use Illuminate\Support\Facades\Route;

Route::get('/sse/{jobId}', [SseController::class, 'stream'])->name('sse.stream');
