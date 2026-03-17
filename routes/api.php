<?php

use App\Http\Controllers\CurrencyConversionController;
use Illuminate\Support\Facades\Route;

Route::get('/currencies', [CurrencyConversionController::class, 'index']);
Route::get('/convert', [CurrencyConversionController::class, 'convert']);
