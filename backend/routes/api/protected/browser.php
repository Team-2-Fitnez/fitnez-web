<?php

use App\Http\Controllers\BrowserTrackingController;
use Illuminate\Support\Facades\Route;

Route::prefix('browser')->group(function () {
    Route::post('/heartbeat', [BrowserTrackingController::class, 'heartbeat']);
    Route::post('/elect-leader', [BrowserTrackingController::class, 'electLeader']);
    Route::post('/release-leader', [BrowserTrackingController::class, 'releaseLeader']);
});
