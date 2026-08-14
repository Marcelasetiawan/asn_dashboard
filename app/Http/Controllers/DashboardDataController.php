<?php

namespace App\Http\Controllers;

use App\Services\BangkomDashboardData;
use Illuminate\Http\JsonResponse;

class DashboardDataController extends Controller
{
    /**
     * GET /api/dashboard-bangkom-data
     *
     * Mengeluarkan JSON persis dengan struktur {"pegawai": [...], "diklat": [...]}
     * yang sama seperti dashboard_data.json hasil proses_data.py -- dipakai
     * kalau kamu mau fetch data live dari JavaScript (Opsi B di README),
     * bukan lewat halaman Blade langsung (Opsi A -- lihat BangkomDashboardController).
     */
    public function export(): JsonResponse
    {
        return response()->json(BangkomDashboardData::build());
    }
}
