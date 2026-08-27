<?php

namespace App\Services;

use App\Models\JabatanOkupasiMapping;

/**
 * Menghubungkan jabatan pegawai (data ASN) dengan Peta Okupasi Nasional
 * bidang TIK, supaya rekomendasi pelatihan tidak lagi berupa 1 label
 * generik ("Penyegaran Kompetensi"), tapi beberapa PILIHAN pelatihan
 * spesifik (dari tabel okupasi_tugas) yang relevan dengan JABATAN pegawai
 * saat ini.
 *
 * Datanya diambil dari tabel database (okupasi, okupasi_tugas,
 * jabatan_okupasi_mapping) -- diisi lewat `php artisan db:seed
 * --class=OkupasiSeeder`. Kalau kamu perlu MENGUBAH pemetaan jabatan ->
 * okupasi, tinggal UPDATE langsung tabel jabatan_okupasi_mapping lewat
 * phpMyAdmin/SQL, tidak perlu edit kode PHP apapun di sini.
 */
class OkupasiRekomendasi
{
    /**
     * Cache seluruh pemetaan jabatan -> okupasi (+ tugas) supaya dipanggil
     * berulang kali (1 per pegawai) tidak menyebabkan N+1 query -- semua
     * data diambil SEKALI lalu dipakai ulang dari memori.
     *
     * @var array<string, \App\Models\JabatanOkupasiMapping>|null
     */
    private static ?array $cache = null;

    private static function cache(): array
    {
        if (self::$cache === null) {
            self::$cache = [];
            foreach (JabatanOkupasiMapping::with('okupasi.tugas')->get() as $row) {
                self::$cache[mb_strtoupper(trim($row->jabatan))] = $row;
            }
        }
        return self::$cache;
    }

    /**
     * Ambil okupasi (beserta daftar tugas/modul pelatihannya) yang
     * dipetakan untuk sebuah jabatan. Null kalau jabatan itu belum
     * dipetakan / dianggap bukan TIK (kolom okupasi_id NULL di
     * jabatan_okupasi_mapping), atau kalau jabatan itu belum ada baris
     * pemetaannya sama sekali.
     */
    public static function okupasiUntukJabatan(?string $jabatan): ?\App\Models\Okupasi
    {
        if (!$jabatan) {
            return null;
        }
        $map = self::cache()[mb_strtoupper(trim($jabatan))] ?? null;
        return $map?->okupasi;
    }

    /**
     * Daftar PILIHAN pelatihan (tugas/modul) untuk sebuah jabatan --
     * inilah yang dipakai di halaman "Rekomendasi Pelatihan" supaya
     * pegawai bisa pilih beberapa, bukan cuma 1 label generik.
     *
     * Sekarang SEMUA jabatan dicarikan okupasi TIK yang paling relevan
     * (bukan cuma yang gelarnya bidang TI) -- baik dari tinjauan manual
     * (sumber = "tinjauan_manual_ai", skor 1.0, paling bisa dipercaya)
     * maupun dari kemiripan teks otomatis (sumber = "auto_tfidf_terdekat",
     * skor bisa rendah kalau bidangnya memang jauh berbeda dari TIK --
     * skor & sumber ini WAJIB ditampilkan ke pengguna dashboard supaya
     * tidak salah kira semuanya rekomendasi yang meyakinkan).
     *
     * $jabatanSpesifik (kolom pegawai.jabatan_fungsional_spesifik, mis. "Guru
     * Ahli Pertama") DICOBA DULUAN kalau ada -- lebih akurat daripada
     * $jabatan yang cuma 4 kategori generik ("Jabatan Fungsional", dst).
     * Kalau tidak ketemu/tidak ada, baru fallback ke $jabatan generik (yang
     * hampir selalu tidak ketemu juga di jabatan_okupasi_mapping, karena
     * mapping-nya memang isinya judul jabatan spesifik).
     *
     * Return: [
     *   'kode' => string|null,        // kode referensi okupasi di PON TIK, misal "TIK.ITG0901"
     *   'nama_okupasi' => string,
     *   'area' => string,             // "Area Fungsi", misal "01 Tata Kelola Teknologi Informasi / IT Governance"
     *   'kualifikasi' => string,
     *   'skor' => float|null,
     *   'sumber' => string,
     *   'pilihan' => [ ['nama' => ..., 'kode_standar' => ...], ... ],
     * ] atau null kalau jabatan belum ada baris pemetaannya sama sekali,
     * atau baris pemetaannya ada tapi okupasi_id-nya NULL (kosong sengaja).
     */
    public static function pilihanPelatihanUntukJabatan(?string $jabatan, ?string $jabatanSpesifik = null): ?array
    {
        $map = null;
        if ($jabatanSpesifik) {
            $map = self::cache()[mb_strtoupper(trim($jabatanSpesifik))] ?? null;
        }
        if (!$map && $jabatan) {
            $map = self::cache()[mb_strtoupper(trim($jabatan))] ?? null;
        }
        if (!$map || !$map->okupasi) {
            return null;
        }
        $okupasi = $map->okupasi;
        return [
            'kode' => $okupasi->kode,
            'nama_okupasi' => $okupasi->nama,
            'area' => $okupasi->area,
            'kualifikasi' => $okupasi->kualifikasi,
            'skor' => $map->skor !== null ? (float) $map->skor : null,
            'sumber' => $map->sumber,
            'pilihan' => $okupasi->tugas->map(function ($t) {
                return [
                    'nama' => $t->nama,
                    'kode_standar' => $t->kode_standar,
                ];
            })->values()->all(),
        ];
    }
}
