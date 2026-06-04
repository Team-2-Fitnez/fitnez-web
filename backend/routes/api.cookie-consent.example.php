<?php

use App\Http\Controllers\Api\CookieConsentController;
use Illuminate\Support\Facades\Route;

Route::post('/cookie-consents', [CookieConsentController::class, 'store']);
