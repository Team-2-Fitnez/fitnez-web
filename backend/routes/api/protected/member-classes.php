<?php

use App\Http\Controllers\MemberClassesController;
use App\Http\Middleware\EnsureActiveMembership;
use Illuminate\Support\Facades\Route;

Route::prefix('member/classes')->middleware(EnsureActiveMembership::class)->group(function () {
    Route::get('/', [MemberClassesController::class, 'index']);
    Route::post('/{id}/join', [MemberClassesController::class, 'join']);
    Route::get('/my', [MemberClassesController::class, 'myClasses']);
});
