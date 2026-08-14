<?php

use App\Http\Controllers\DashboardDataController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Route tambahan untuk Dashboard Bangkom ASN
|--------------------------------------------------------------------------
| Tempel isi file ini ke dalam routes/api.php yang sudah ada (atau require
| file ini dari sana). Kalau proyekmu belum punya routes/api.php, buat
| filenya lalu daftarkan di bootstrap/app.php (Laravel 11) atau
| app/Providers/RouteServiceProvider.php (Laravel <=10).
*/
Route::get('/dashboard-bangkom-data', [DashboardDataController::class, 'export']);
