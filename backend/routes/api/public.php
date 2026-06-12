<?php

use App\Http\Controllers\FaqController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\ManualPaymentMethodController;
use App\Http\Controllers\MembershipPackageController;
use App\Http\Controllers\Api\CookieConsentController;
use Illuminate\Support\Facades\Route;

Route::post('/cookie-consents', [CookieConsentController::class, 'store']);
Route::get('/landing', [LandingController::class, 'index']);
Route::get('/faqs', [FaqController::class, 'index']);
Route::get('/faqs/categories', [FaqController::class, 'categories']);
Route::get('/membership-packages', [MembershipPackageController::class, 'index']);
Route::get('/manual-payment-methods', [ManualPaymentMethodController::class, 'index']);
