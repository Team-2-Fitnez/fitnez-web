<?php

use App\Features\Landing\Controllers\FaqController;
use App\Features\Landing\Controllers\LandingController;
use App\Features\Landing\Controllers\ManualPaymentMethodController;
use App\Features\Landing\Controllers\MembershipPackageController;
use App\Features\Landing\Controllers\CookieConsentController;
use Illuminate\Support\Facades\Route;

Route::post('/cookie-consents', [CookieConsentController::class, 'store']);
Route::get('/landing', [LandingController::class, 'index']);
Route::get('/faqs', [FaqController::class, 'index']);
Route::get('/faqs/categories', [FaqController::class, 'categories']);
Route::get('/membership-packages', [MembershipPackageController::class, 'index']);
Route::get('/manual-payment-methods', [ManualPaymentMethodController::class, 'index']);
