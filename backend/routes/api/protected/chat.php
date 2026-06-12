<?php

use App\Http\Controllers\ChatController;
use App\Http\Middleware\EnsureActiveMembership;
use Illuminate\Support\Facades\Route;

Route::middleware(EnsureActiveMembership::class)->group(function () {
    Route::prefix('chat')->group(function () {
        Route::get('/contacts', [ChatController::class, 'contacts']);
        Route::get('/messages', [ChatController::class, 'messages']);
        Route::get('/messages/{message}/attachment', [ChatController::class, 'attachment']);
        Route::post('/messages', [ChatController::class, 'send']);
    });
});
