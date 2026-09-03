<?php

namespace Database\Seeders;

use App\Models\PelatihanWajib;
use Illuminate\Database\Seeder;

/**
 * Kurikulum Pelatihan Wajib per kategori jabatan x level (Dasar/Menengah/
 * Tinggi). Jalur Struktural mengikuti istilah resmi diklat kepemimpinan ASN
 * Indonesia (PKP/PKA/PKN) -- terverifikasi cocok dengan nama diklat asli yang
 * sudah ada di riwayat_diklat ("DIKLAT PIM TK.IV/PKP", "DIKLAT PIM TK.III/PKA",
 * "PELATIHAN KEPEMIMPINAN NASIONAL TINGKAT II"). Kategori lain disusun manual
 * berdasar kebutuhan kompetensi umum ASN per level.
 */
class PelatihanWajibSeeder extends Seeder
{
    public function run(): void
    {
        PelatihanWajib::query()->delete();

        $kurikulum = [
            'Jabatan Pelaksana' => [
                'Dasar' => [
                    'Pelatihan Dasar Aplikasi Perkantoran (Microsoft Office/Excel)',
                    'Pelatihan Tata Naskah Dinas & Kearsipan',
                    'Orientasi Pelayanan Publik Dasar',
                ],
                'Menengah' => [
                    'Pelatihan Pengelolaan Data & Pelaporan Kinerja',
                    'Pelatihan Digitalisasi Layanan Publik',
                ],
                'Tinggi' => [
                    'Pelatihan Manajemen Kearsipan & Data Lanjutan',
                    'Sertifikasi Kompetensi Bidang Administrasi',
                ],
            ],
            'Jabatan Fungsional Umum' => [
                'Dasar' => [
                    'Pelatihan Dasar Aplikasi Perkantoran (Microsoft Office/Excel)',
                    'Orientasi Tugas & Fungsi Jabatan',
                    'Pelatihan Dasar Pelayanan Publik',
                ],
                'Menengah' => [
                    'Pelatihan Pengelolaan Data & Pelaporan Kinerja',
                    'Pelatihan Komunikasi & Kerja Sama Tim',
                ],
                'Tinggi' => [
                    'Pelatihan Penyusunan Kebijakan & Analisis Data Lanjutan',
                ],
            ],
            'Jabatan Fungsional' => [
                'Dasar' => [
                    'Pelatihan Dasar Jabatan Fungsional (sesuai bidang)',
                    'Orientasi Angka Kredit Jabatan Fungsional',
                ],
                'Menengah' => [
                    'Pelatihan Teknis Fungsional Tingkat Lanjut',
                    'Bimbingan Teknis Substansi Sesuai Bidang Keahlian',
                ],
                'Tinggi' => [
                    'Pelatihan Fungsional Jenjang Ahli Madya/Ahli Utama',
                    'Pelatihan Penulisan Karya Tulis Ilmiah/Publikasi',
                ],
            ],
            'Jabatan Struktural' => [
                'Dasar' => [
                    'Pelatihan Kepemimpinan Pengawas (PKP)',
                ],
                'Menengah' => [
                    'Pelatihan Kepemimpinan Administrator (PKA)',
                ],
                'Tinggi' => [
                    'Pelatihan Kepemimpinan Nasional Tingkat II/I (PKN)',
                ],
            ],
        ];

        $total = 0;
        foreach ($kurikulum as $kategori => $perLevel) {
            foreach ($perLevel as $level => $daftarPelatihan) {
                foreach ($daftarPelatihan as $urutan => $namaPelatihan) {
                    PelatihanWajib::create([
                        'kategori_jabatan' => $kategori,
                        'level' => $level,
                        'nama_pelatihan' => $namaPelatihan,
                        'urutan' => $urutan,
                    ]);
                    $total++;
                }
            }
        }

        $this->command->info("Pelatihan wajib tersimpan: {$total} baris.");
    }
}
