<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BangkomDashboardController;
use App\Http\Controllers\PelatihanPilihanController;
use App\Http\Controllers\SelfServiceController;

/*
|--------------------------------------------------------------------------
| Web Routes — Dashboard Bangkom ASN
|--------------------------------------------------------------------------
*/

Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/', [BangkomDashboardController::class, 'index'])->name('dashboard');
});

Route::middleware(['auth', 'role:asn'])->group(function () {
    Route::get('/saya', [SelfServiceController::class, 'ringkasan'])->name('saya');
    Route::get('/saya/profil', [SelfServiceController::class, 'profil'])->name('saya.profil');
    Route::get('/saya/riwayat', [SelfServiceController::class, 'riwayat'])->name('saya.riwayat');
    Route::get('/saya/pelatihan', [SelfServiceController::class, 'pelatihan'])->name('saya.pelatihan');
    Route::get('/saya/akun', [SelfServiceController::class, 'akun'])->name('saya.akun');
    Route::patch('/saya/akun', [SelfServiceController::class, 'update'])->name('saya.update');
    Route::put('/saya/password', [SelfServiceController::class, 'updatePassword'])->name('saya.password');
    Route::post('/saya/sertifikat/{riwayat}', [SelfServiceController::class, 'uploadSertifikat'])->name('saya.sertifikat');
});

Route::middleware('auth')->group(function () {
    Route::post('/pelatihan-pilihan', [PelatihanPilihanController::class, 'store']);
    Route::get('/pelatihan-pilihan/{nip}', [PelatihanPilihanController::class, 'show']);
});
