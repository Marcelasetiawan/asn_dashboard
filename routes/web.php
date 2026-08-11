<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| Web Routes — Dashboard Bangkom ASN
|--------------------------------------------------------------------------
| Halaman utama (1 Blade view, 4 tab difokuskan lewat JS) + endpoint API
| yang dipanggil lewat fetch() dari JavaScript.
*/

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

Route::prefix('api')->group(function () {
    Route::get('/ringkasan', [DashboardController::class, 'apiRingkasan']);
    Route::get('/diklat-unit', [DashboardController::class, 'apiDiklatUnit']);
    Route::get('/diklat-peserta', [DashboardController::class, 'apiDiklatPeserta']);
    Route::get('/analisis-lanjutan', [DashboardController::class, 'apiAnalisisLanjutan']);
    Route::post('/prediksi', [DashboardController::class, 'apiPrediksi']);
});
