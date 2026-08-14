<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BangkomDashboardController;

/*
|--------------------------------------------------------------------------
| Web Routes — Dashboard Bangkom ASN
|--------------------------------------------------------------------------
| Halaman utama sekarang pakai dashboard Bangkom ASN (server-rendered,
| data live dari database). Endpoint /api/... di bawah ini punya
| DashboardController lama (Ringkasan/Diklat & Unit Kerja/Analisis
| Lanjutan/Prediksi) -- sudah tidak dipanggil oleh halaman manapun lagi
| kalau dashboard lama sudah tidak dipakai, jadi boleh dihapus atau
| dibiarkan saja.
*/

Route::get('/', [BangkomDashboardController::class, 'index'])->name('dashboard');

Route::prefix('api')->group(function () {
    Route::get('/ringkasan', [DashboardController::class, 'apiRingkasan']);
    Route::get('/diklat-unit', [DashboardController::class, 'apiDiklatUnit']);
    Route::get('/diklat-peserta', [DashboardController::class, 'apiDiklatPeserta']);
    Route::get('/analisis-lanjutan', [DashboardController::class, 'apiAnalisisLanjutan']);
    Route::post('/prediksi', [DashboardController::class, 'apiPrediksi']);
});