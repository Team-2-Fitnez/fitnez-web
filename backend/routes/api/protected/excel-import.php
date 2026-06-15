<?php

use App\Http\Controllers\Api\ExcelController;
use Illuminate\Support\Facades\Route;

Route::post('/excel/import', [ExcelController::class, 'upload']);
