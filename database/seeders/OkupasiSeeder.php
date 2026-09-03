<?php

namespace Database\Seeders;

use App\Models\Okupasi;
use App\Models\OkupasiTugas;
use App\Models\JabatanOkupasiMapping;
use Illuminate\Database\Seeder;

/**
 * Isi tabel okupasi, okupasi_tugas, dan jabatan_okupasi_mapping dari 3 file
 * CSV di database/csv/ (hasil olahan dari Peta_Okupasi_PON_TIK_expanded.xlsx)
 * -- pola yang sama seperti importer pegawai.csv/riwayat_diklat.csv yang
 * sudah ada di project ini.
 *
 * File yang dibaca:
 * - database/csv/okupasi.csv               (kode, area, nama, kualifikasi, definisi, sertifikasi)
 * - database/csv/okupasi_tugas.csv         (nama_okupasi, nama_tugas, kode_standar)
 * - database/csv/jabatan_okupasi_mapping.csv (jabatan, nama_okupasi, skor, sumber)
 *
 * Jalankan dengan:
 *   php artisan db:seed --class=OkupasiSeeder
 *
 * Aman dijalankan ulang (idempotent) -- data lama dihapus dulu sebelum
 * diisi ulang, supaya tidak dobel kalau dijalankan lebih dari sekali.
 */
class OkupasiSeeder extends Seeder
{
    /**
     * Baca file CSV jadi array asosiatif per baris (baris pertama dipakai
     * sebagai nama kolom), mendukung nilai yang mengandung koma/tanda kutip
     * (sudah di-quote dengan benar waktu file CSV-nya dibuat).
     */
    private function bacaCsv(string $path): array
    {
        if (!file_exists($path)) {
            return [];
        }
        $rows = [];
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);
        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) !== count($header)) {
                continue; // baris rusak/kosong, lewati
            }
            $rows[] = array_combine($header, $data);
        }
        fclose($handle);
        return $rows;
    }

    public function run(): void
    {
        $okupasiPath = database_path('csv/okupasi.csv');
        $tugasPath = database_path('csv/okupasi_tugas.csv');
        $mappingPath = database_path('csv/jabatan_okupasi_mapping.csv');

        if (!file_exists($okupasiPath)) {
            $this->command->error('File tidak ditemukan: ' . $okupasiPath);
            return;
        }

        // Bersihkan data lama supaya seeder ini aman dijalankan berkali-kali.
        JabatanOkupasiMapping::query()->delete();
        OkupasiTugas::query()->delete();
        Okupasi::query()->delete();

        // ---- okupasi.csv -> tabel okupasi ----
        $okupasiIdByNama = [];
        foreach ($this->bacaCsv($okupasiPath) as $row) {
            $o = Okupasi::create([
                'kode' => $row['kode'] !== '' ? $row['kode'] : null,
                'area' => $row['area'],
                'nama' => $row['nama'],
                'kualifikasi' => $row['kualifikasi'] !== '' ? $row['kualifikasi'] : null,
                'definisi' => $row['definisi'] !== '' ? $row['definisi'] : null,
                'sertifikasi' => $row['sertifikasi'] !== '' ? $row['sertifikasi'] : null,
            ]);
            $okupasiIdByNama[$row['nama']] = $o->id;
        }
        $this->command->info('Okupasi tersimpan: ' . Okupasi::count());

        // ---- okupasi_tugas.csv -> tabel okupasi_tugas ----
        $jumlahTugas = 0;
        foreach ($this->bacaCsv($tugasPath) as $row) {
            $okupasiId = $okupasiIdByNama[$row['nama_okupasi']] ?? null;
            if (!$okupasiId) {
                continue; // nama okupasi di baris ini tidak ketemu di okupasi.csv
            }
            OkupasiTugas::create([
                'okupasi_id' => $okupasiId,
                'nama' => $row['nama_tugas'],
                'kode_standar' => $row['kode_standar'] !== '' ? $row['kode_standar'] : null,
            ]);
            $jumlahTugas++;
        }
        $this->command->info('Okupasi tugas tersimpan: ' . $jumlahTugas);

        // ---- jabatan_okupasi_mapping.csv -> tabel jabatan_okupasi_mapping ----
        $jumlahDipetakan = 0;
        $jumlahJabatan = 0;
        foreach ($this->bacaCsv($mappingPath) as $row) {
            $namaOkupasi = $row['nama_okupasi'] ?? '';
            $okupasiId = $namaOkupasi !== '' ? ($okupasiIdByNama[$namaOkupasi] ?? null) : null;
            JabatanOkupasiMapping::create([
                'jabatan' => $row['jabatan'],
                'okupasi_id' => $okupasiId,
                'skor' => $row['skor'] !== '' ? (float) $row['skor'] : null,
                'sumber' => $row['sumber'] !== '' ? $row['sumber'] : 'auto_tfidf_placeholder',
            ]);
            $jumlahJabatan++;
            if ($okupasiId) {
                $jumlahDipetakan++;
            }
        }
        $this->command->info('Pemetaan jabatan tersimpan: ' . $jumlahJabatan . ' (' . $jumlahDipetakan . ' punya okupasi cocok)');
    }
}
