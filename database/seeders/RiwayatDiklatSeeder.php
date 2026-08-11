<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RiwayatDiklatSeeder extends Seeder
{
    /**
     * VERSI KHUSUS: baca file CSV MENTAH hasil export langsung dari Excel
     * (sheet "Riwayat Bangkom 2 Th. Terakhir"), ATAU file yang sudah disederhanakan
     * (7 kolom) - keduanya otomatis terdeteksi.
     *
     * Kenapa perlu logika khusus untuk format mentah (21 kolom):
     * 1. Ada 2 kolom bernama "Nama" (nama pegawai di posisi 2, nama diklat di posisi 16)
     *    -> tidak bisa pakai array_combine(header, row) karena key akan bentrok.
     *    -> jadi kita baca berdasarkan POSISI kolom (index 0-20), bukan nama header.
     * 2. Kolom "NIP Baru" cuma terisi di baris PERTAMA tiap pegawai; baris lanjutan
     *    (diklat ke-2, ke-3, dst milik pegawai yang sama) NIP-nya kosong.
     *    -> perlu forward-fill: simpan NIP terakhir yang terisi, pakai itu untuk
     *       baris-baris kosong berikutnya.
     * 3. Banyak baris "Kursus" tapi nama diklatnya cuma tanda "-" (placeholder,
     *    bukan diklat sungguhan) -> baris ini WAJIB dilewati.
     *
     * Urutan 21 kolom di file mentah (index 0-20):
     * 0=No. 1=NIP Baru 2=Nama(pegawai) 3=L/P 4=Status 5=Gol.Ruang 6=TMT Pangkat
     * 7=Eselon 8=Jabatan 9=TMT Jabatan 10=Agama 11=Satuan Kerja 12=TMT Pensiun
     * 13=Pendidikan 14=Lulus 15=Jenis Sertifikasi 16=Nama(diklat) 17=No.Sertifikat
     * 18=Penyelenggara 19=Pelaksanaan 20=JP
     */
    public function run(): void
    {
        $path = database_path('csv/riwayat_diklat.csv');

        if (!file_exists($path)) {
            $this->command->error("FILE TIDAK DITEMUKAN: $path");
            return;
        }

        $raw = file_get_contents($path);
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw); // buang BOM kalau ada

        $firstLine = strtok($raw, "\n");
        $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
        $this->command->info("Delimiter terdeteksi: '$delimiter'");

        $handle = fopen('php://memory', 'r+');
        fwrite($handle, $raw);
        rewind($handle);

        $header = fgetcsv($handle, 0, $delimiter);
        if (!$header) {
            $this->command->error("Gagal membaca header CSV.");
            return;
        }

        $jumlahKolom = count($header);
        $this->command->info("Jumlah kolom terdeteksi: $jumlahKolom");

        // deteksi format: 21 kolom mentah (raw export Excel), ATAU 7 kolom yang sudah disederhanakan
        $isFormatMentah = $jumlahKolom >= 20;

        $nipValid = DB::table('pegawai')->pluck('nip')->flip();
        $batch = [];
        $batchSize = 200;
        $total = 0;
        $skipped = 0;
        $skippedPlaceholder = 0;
        $now = now();
        $currentNip = null;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if ($isFormatMentah) {
                // ── FORMAT MENTAH (21 kolom, forward-fill NIP) ──
                if (count($row) < 20) continue;

                $nipBaru = trim($row[1] ?? '');
                if ($nipBaru !== '') {
                    $currentNip = $nipBaru;
                }
                if (!$currentNip) continue; // belum ada NIP sama sekali (baris di awal file)

                $namaDiklat = trim($row[16] ?? '');
                if ($namaDiklat === '' || $namaDiklat === '-') {
                    $skippedPlaceholder++;
                    continue; // buang baris placeholder "-" / kosong
                }

                if (!isset($nipValid[$currentNip])) {
                    $skipped++;
                    continue;
                }

                $jpRaw = trim($row[20] ?? '');
                $batch[] = [
                    'nip'               => $currentNip,
                    'jenis_sertifikasi' => ($row[15] ?? '') !== '' ? trim($row[15]) : null,
                    'nama_diklat'       => $namaDiklat,
                    'no_sertifikat'     => ($row[17] ?? '') !== '' ? trim($row[17]) : null,
                    'penyelenggara'     => ($row[18] ?? '') !== '' ? trim($row[18]) : null,
                    'pelaksanaan'       => ($row[19] ?? '') !== '' ? trim($row[19]) : null,
                    'jp'                => ($jpRaw !== '' && $jpRaw !== '-') ? (int) $jpRaw : null,
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ];
            } else {
                // ── FORMAT SUDAH DISEDERHANAKAN (7 kolom, header: nip,jenis_sertifikasi,...) ──
                if (count($row) < $jumlahKolom) continue;
                $data = array_combine($header, $row);
                $nip = trim($data['nip'] ?? '');
                if (!isset($nipValid[$nip])) {
                    $skipped++;
                    continue;
                }
                $batch[] = [
                    'nip'               => $nip,
                    'jenis_sertifikasi' => ($data['jenis_sertifikasi'] ?? '') !== '' ? trim($data['jenis_sertifikasi']) : null,
                    'nama_diklat'       => trim($data['nama_diklat'] ?? ''),
                    'no_sertifikat'     => ($data['no_sertifikat'] ?? '') !== '' ? trim($data['no_sertifikat']) : null,
                    'penyelenggara'     => ($data['penyelenggara'] ?? '') !== '' ? trim($data['penyelenggara']) : null,
                    'pelaksanaan'       => ($data['pelaksanaan'] ?? '') !== '' ? trim($data['pelaksanaan']) : null,
                    'jp'                => ($data['jp'] ?? '') !== '' ? (int) $data['jp'] : null,
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ];
            }

            if (count($batch) >= $batchSize) {
                DB::table('riwayat_diklat')->insert($batch);
                $total += count($batch);
                $batch = [];
            }
        }
        if (count($batch) > 0) {
            DB::table('riwayat_diklat')->insert($batch);
            $total += count($batch);
        }
        fclose($handle);

        if ($total === 0) {
            $this->command->error("PERINGATAN: 0 baris berhasil diimport. Cek format file CSV.");
        } else {
            $this->command->info("Berhasil import $total riwayat diklat." .
                ($skippedPlaceholder > 0 ? " ($skippedPlaceholder baris placeholder '-' dilewati)" : "") .
                ($skipped > 0 ? " ($skipped baris dilewati karena NIP tidak ditemukan)" : ""));
        }
    }
}
