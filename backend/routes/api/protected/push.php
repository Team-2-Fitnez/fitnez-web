<?php

use App\Http\Controllers\Api\NotificationController;
use Illuminate\Support\Facades\Route;

Route::post('/push/subscribe', [NotificationController::class, 'subscribe']);
Route::delete('/push/unsubscribe', [NotificationController::class, 'unsubscribe']);
