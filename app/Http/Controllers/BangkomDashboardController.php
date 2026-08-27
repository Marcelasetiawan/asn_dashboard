<?php

namespace App\Http\Controllers;

use App\Services\BangkomDashboardData;

class BangkomDashboardController extends Controller
{
    /**
     * GET / (khusus role admin -- lihat routes/web.php)
     *
     * Menyajikan dashboard admin sebagai halaman Blade server-rendered:
     * data seluruh pegawai & riwayat diklat dihitung dari database lalu
     * ditanam sebagai JSON ke dalam HTML-nya, dibaca oleh public/js/dashboard.js.
     */
    public function index()
    {
        // Dashboard admin menanam data SELURUH pegawai + riwayat diklat
        // (14 ribuan pegawai, puluhan ribu riwayat) sebagai satu blob JSON
        // di HTML supaya public/js/dashboard.js bisa filter/cari secara
        // instan di sisi client -- default memory_limit 512M PHP tidak
        // cukup untuk membangun array itu lalu json_encode-nya sekaligus.
        ini_set('memory_limit', '1536M');

        $data = BangkomDashboardData::build();

        // Escape "</script>" supaya string apapun di data (nama diklat, dst)
        // tidak bisa memutus tag <script> lebih awal.
        $dataJson = str_replace(
            '</script>',
            '<\/script>',
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        return view('dashboard', ['dataJson' => $dataJson]);
    }
}
