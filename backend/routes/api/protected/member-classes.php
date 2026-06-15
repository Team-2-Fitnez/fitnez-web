<?php

use App\Http\Controllers\Api\ClassController;
use App\Http\Middleware\EnsureActiveMembership;
use Illuminate\Support\Facades\Route;

Route::prefix('member/classes')->middleware(EnsureActiveMembership::class)->group(function () {
    Route::get('/', [ClassController::class, 'index']);
    Route::post('/{id}/join', [ClassController::class, 'join']);
    Route::get('/my', [ClassController::class, 'myClasses']);
});
