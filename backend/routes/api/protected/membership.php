<?php

use App\Http\Controllers\Api\MembershipController;
use Illuminate\Support\Facades\Route;

Route::get('/member/membership/status', [MembershipController::class, 'status']);
Route::post('/member/membership/renew', [MembershipController::class, 'renew']);
Route::post('/member/membership/renew/{payment}/upload-proof', [MembershipController::class, 'uploadProof']);
Route::delete('/member/account', [MembershipController::class, 'destroyAccount']);
