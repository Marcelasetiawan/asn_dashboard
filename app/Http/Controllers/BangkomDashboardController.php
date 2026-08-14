<?php

namespace App\Http\Controllers;

use App\Services\BangkomDashboardData;

class BangkomDashboardController extends Controller
{
    /**
     * GET /bangkom-asn
     *
     * Menyajikan dashboard sebagai halaman Blade biasa (server-rendered),
     * data pegawai & riwayat diklat dihitung langsung dari database lalu
     * ditanam sebagai JSON ke dalam HTML-nya -- sama persis dengan cara
     * kerja prototipe Dashboard_Bangkom_ASN.html sebelumnya, bedanya di sini
     * datanya diambil dari database sungguhan (bukan file CSV yang dibekukan).
     *
     * resources/views/bangkom/dashboard.blade.php memakai file JS & CSS yang
     * SAMA PERSIS dengan prototipe (public/js/bangkom-dashboard.js,
     * public/css/bangkom-dashboard.css) -- jadi semua fitur yang sudah ada di
     * prototipe (pencarian, filter, grafik, modal profil, dst) langsung jalan
     * tanpa perlu ditulis ulang.
     */
    public function index()
    {
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
