<?php

namespace App\Http\Controllers;

use App\Models\RiwayatDiklat;
use App\Services\BangkomDashboardData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    /**
     * POST /riwayat-diklat (khusus role admin) -- admin menandai satu
     * Pelatihan Wajib sebagai "sudah diikuti" tanpa harus re-import file
     * Excel: bikin baris baru di tabel riwayat_diklat untuk pegawai itu
     * (nama_diklat harus cocok dengan nama Pelatihan Wajib supaya status
     * "Sudah/Belum" di dashboard otomatis ke-update, lihat
     * BangkomDashboardData::cekKemungkinanSudahDiikuti), sekalian bisa
     * unggah bukti/berkas sertifikatnya.
     */
    public function simpanRiwayatDiklat(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nip' => 'required|string|exists:pegawai,nip',
            'nama_diklat' => 'required|string|max:255',
            'no_sertifikat' => 'nullable|string|max:255',
            'berkas' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $berkas = $request->hasFile('berkas')
            ? $request->file('berkas')->store('sertifikat/'.$data['nip'], 'public')
            : null;

        $riwayat = RiwayatDiklat::create([
            'nip' => $data['nip'],
            'nama_diklat' => $data['nama_diklat'],
            'jenis_sertifikasi' => 'Pelatihan Wajib',
            'no_sertifikat' => $data['no_sertifikat'] ?? null,
            'berkas_sertifikat' => $berkas,
            'pelaksanaan' => now()->format('Y-m-d'),
            'tahun' => now()->year,
            'sumber' => 'input_manual_admin',
        ]);

        return response()->json(['ok' => true, 'id' => $riwayat->id]);
    }
}
