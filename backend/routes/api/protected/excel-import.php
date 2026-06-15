<?php

use App\Features\Excel\Controllers\ExcelImportController;
use Illuminate\Support\Facades\Route;

Route::post('/excel/import', [ExcelImportController::class, 'upload']);
