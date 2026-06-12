<?php

use App\Http\Controllers\MemberMembershipController;
use Illuminate\Support\Facades\Route;

Route::get('/member/membership/status', [MemberMembershipController::class, 'status']);
Route::post('/member/membership/renew', [MemberMembershipController::class, 'renew']);
Route::post('/member/membership/renew/{payment}/upload-proof', [MemberMembershipController::class, 'uploadProof']);
Route::delete('/member/account', [MemberMembershipController::class, 'destroyAccount']);
