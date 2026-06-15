<?php

use App\Http\Controllers\FaqController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\Api\CookieConsentController;
use App\Http\Controllers\Api\MembershipController;
use App\Http\Controllers\Api\PaymentController;
use Illuminate\Support\Facades\Route;

Route::post('/cookie-consents', [CookieConsentController::class, 'store']);
Route::get('/landing', [LandingController::class, 'index']);
Route::get('/faqs', [FaqController::class, 'index']);
Route::get('/faqs/categories', [FaqController::class, 'categories']);
Route::get('/membership-packages', [MembershipController::class, 'packages']);
Route::get('/manual-payment-methods', [PaymentController::class, 'manualMethods']);
