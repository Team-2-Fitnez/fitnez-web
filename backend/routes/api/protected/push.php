<?php

use App\Http\Controllers\PushSubscriptionController;
use Illuminate\Support\Facades\Route;

Route::post('/push/subscribe', [PushSubscriptionController::class, 'store']);
Route::delete('/push/unsubscribe', [PushSubscriptionController::class, 'destroy']);
