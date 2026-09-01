<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Baca file Excel sumber di database/csv/ (hasil akhir.xlsx, diklat_siasn.xlsx,
 * sertifikasi asn.xlsx, pegawai_json_pns_pppk.xlsx) lalu tulis ulang jadi
 * storage/app/dashboard-import/pegawai.csv dan riwayat_diklat.csv yang dibaca
 * PegawaiSeeder & RiwayatDiklatSeeder.
 *
 * Port PHP murni dari skrip Python lama (Python/prepare_dashboard_import.py) --
 * logikanya sama persis, supaya seeding tidak lagi butuh Python terpasang.
 *
 * Kolom "nama" di hasil akhir.xlsx!'data asn' cuma nama polos tanpa gelar
 * akademik (mis. "FRANCISCO EKA YUDA KARTIKA"), beda dari pegawai_json_pns_pppk.xlsx
 * yang punya nama LENGKAP dengan gelar (mis. "Apt.. SEPTI YUSSINTA SARI, S.Farm.").
 * Tanpa gelar, PegawaiKlasifikasi::bidangGelar() tidak bisa kerja sama sekali
 * (rekomendasi "jabatan tidak sesuai gelar" selalu 0 orang) -- jadi nama dengan
 * gelar dari pegawais_2026.xlsx (utama, kolom glr_dpn/glr_blk, kunci NIP) dan
 * pegawai_json_pns_pppk.xlsx (cadangan) dipakai untuk MENIMPA nama polos.
 * Sisanya (yang tidak ketemu di kedua file itu) tetap pakai nama polos apa
 * adanya.
 *
 * pegawais_2026.xlsx juga sumber untuk kolom jabatan_fungsional_spesifik /
 * jabatan_umum_label / unor_detail (lewat jab_id -> master_jabfung.csv,
 * jabstr -> master_genpos.csv, unor -> master_unor.csv) -- semuanya kunci NIP,
 * akurat (beda dari versi lama pegawais_202608020842.csv yang NIP-nya rusak
 * notasi ilmiah Excel, terpaksa dicocokkan lewat nama).
 */
class DashboardImportNormalizer
{
    private string $sourceDir;

    private string $targetDir;

    public function __construct(?string $sourceDir = null, ?string $targetDir = null)
    {
        $this->sourceDir = $sourceDir ?? database_path('csv');
        $this->targetDir = $targetDir ?? storage_path('app/dashboard-import');
    }

    /**
     * @return array{pegawai: int, riwayat: int}
     */
    public function run(): array
    {
        // File sumbernya besar (sertifikasi asn.xlsx ~90rb baris), naikkan batas
        // memori khusus untuk proses seeding ini.
        ini_set('memory_limit', '2048M');

        $hasilPath = $this->sourceDir.'/hasil akhir.xlsx';
        $diklatPath = $this->sourceDir.'/diklat_siasn.xlsx';
        $sertifikasiPath = $this->sourceDir.'/sertifikasi asn.xlsx';
        $gelarPath = $this->sourceDir.'/pegawai_json_pns_pppk.xlsx';

        foreach ([$hasilPath, $diklatPath, $sertifikasiPath, $gelarPath] as $path) {
            if (!is_file($path)) {
                throw new \RuntimeException("File sumber Excel tidak ditemukan: {$path}");
            }
        }

        $namaBergelar = $this->readNamaBergelar($gelarPath);

        $pegawais2026Path = $this->sourceDir.'/pegawais_2026.xlsx';
        $jabfungPath = $this->sourceDir.'/master_jabfung.csv';
        $genposPath = $this->sourceDir.'/master_genpos.csv';
        $unorPath = $this->sourceDir.'/master_unor.csv';
        $referensiJabatan = [];
        $namaBergelarDariPegawais2026 = [];
        $namaPolosDariPegawais2026 = [];
        if (is_file($pegawais2026Path) && is_file($jabfungPath) && is_file($genposPath) && is_file($unorPath)) {
            [$referensiJabatan, $namaBergelarDariPegawais2026, $namaPolosDariPegawais2026] = $this->readPegawais2026(
                $pegawais2026Path, $jabfungPath, $genposPath, $unorPath
            );
        }
        // pegawais_2026.xlsx (kunci NIP, akurat) diutamakan; pegawai_json_pns_pppk.xlsx
        // (kunci NIP juga, tapi cakupannya lebih kecil) jadi cadangan kalau NIP itu
        // tidak ada gelarnya di pegawais_2026.xlsx.
        $namaBergelar = $namaBergelarDariPegawais2026 + $namaBergelar;

        $hasilSheets = [
            'dokumen akhir keseluruhan', 'asn tik', 'asn manajerliar', 'data asn',
            'ASN Seri 6', 'mengkuti arsitektur keamanan', 'data science', 'ARTIFICIAL INTELLIGENCE',
        ];
        $hasilReader = IOFactory::createReaderForFile($hasilPath);
        $hasilReader->setReadDataOnly(true);
        $hasilReader->setLoadSheetsOnly($hasilSheets);
        $hasil = $hasilReader->load($hasilPath);

        $levels = $this->indexByColumn($hasil->getSheetByName('dokumen akhir keseluruhan'), 'nip_baru');
        $tik = $this->columnValueSet($hasil->getSheetByName('asn tik'), 'nip');
        $manajerial = $this->columnValueSet($hasil->getSheetByName('asn manajerliar'), 'nip');

        [$pegawaiRows, $validNips] = $this->buildPegawaiRows(
            $hasil->getSheetByName('data asn'), $levels, $tik, $manajerial, $namaBergelar, $referensiJabatan, $namaPolosDariPegawais2026
        );
        $this->writeCsv($this->targetDir.'/pegawai.csv', $pegawaiRows);

        $riwayatRows = [];
        $this->appendFromDiklatSiasn($diklatPath, $validNips, $riwayatRows);
        $this->appendFromSertifikasiAsn($sertifikasiPath, $validNips, $riwayatRows);
        $this->appendFromHasilAkhirModules($hasil, $validNips, $riwayatRows);

        $hasil->disconnectWorksheets();
        unset($hasil);

        $this->writeCsv($this->targetDir.'/riwayat_diklat.csv', $riwayatRows);

        return ['pegawai' => count($pegawaiRows), 'riwayat' => count($riwayatRows)];
    }

    /**
     * Baca pegawai_json_pns_pppk.xlsx -> peta NIP => nama lengkap dengan gelar
     * akademik (mis. "Apt.. SEPTI YUSSINTA SARI, S.Farm."). Cuma menutupi
     * sebagian pegawai, bukan berarti bug kalau tidak lengkap.
     *
     * @return array<string, string>
     */
    private function readNamaBergelar(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getActiveSheet();

        $map = [];
        foreach ($this->rowsWithHeaders($sheet) as $row) {
            $nip = $this->sanitizeNip($row['NIP Baru'] ?? '');
            $nama = $row['Nama'] ?? '';
            if ($nip !== '' && $nama !== '') {
                $map[$nip] = $nama;
            }
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return $map;
    }

    /**
     * Baca pegawais_2026.xlsx -- versi PERBAIKAN dari pegawais_202608020842.csv
     * lama (NIP-nya sekarang utuh 18 digit, bukan notasi ilmiah Excel lagi),
     * cocokkan ke master_jabfung.csv/master_genpos.csv/master_unor.csv lewat
     * NIP langsung (akurat, beda dari pendekatan cocok-nama sebelumnya).
     *
     * Kuirk file ini: tiap baris ke-paste jadi SATU sel (kolom A) berisi teks
     * ala-CSV, bukan kolom Excel biasa -- makanya dibaca cell-by-cell lalu
     * diurai manual pakai str_getcsv().
     *
     * @return array{0: array<string, array<string, string>>, 1: array<string, string>, 2: array<string, string>}
     *   [0] = referensi jabatan per NIP, [1] = nama+gelar per NIP (dari glr_dpn/glr_blk),
     *   [2] = nama POLOS (tanpa gelar) per NIP, dipakai sebagai cadangan terakhir
     *   untuk 1.488 pegawai yang cuma ada di file ini (tidak ada di hasil akhir.xlsx)
     */
    private function readPegawais2026(string $path, string $jabfungPath, string $genposPath, string $unorPath): array
    {
        $jabfungById = $this->readLookupCsv($jabfungPath, ',', 'id', ['nama_jabfung', 'kelas_jab']);
        $genposByKode = $this->readLookupCsv($genposPath, ',', 'kd_genpos', ['nm_genpos']);
        $unorByKode = $this->readLookupCsv($unorPath, ',', 'kd_unor', ['nm_unor']);

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getActiveSheet();

        $header = null;
        $idx = [];
        $referensi = [];
        $namaBergelar = [];
        $namaPolos = [];

        foreach ($sheet->getRowIterator() as $excelRow) {
            $cell = $sheet->getCell('A'.$excelRow->getRowIndex());
            $fields = str_getcsv((string) $cell->getValue(), ',', '"');

            if ($header === null) {
                $header = $fields;
                foreach (['nip', 'nama', 'glr_dpn', 'glr_blk', 'jab_id', 'jabstr', 'unor'] as $col) {
                    $idx[$col] = array_search($col, $header);
                }
                continue;
            }

            $nip = $this->sanitizeNip(trim($fields[$idx['nip']] ?? ''));
            if ($nip === '') {
                continue;
            }

            $jabId = trim($fields[$idx['jab_id']] ?? '');
            $jabstr = trim($fields[$idx['jabstr']] ?? '');
            $unor = trim($fields[$idx['unor']] ?? '');
            $jabfung = $jabfungById[$jabId] ?? null;
            $genpos = $genposByKode[$jabstr] ?? null;
            $unorInfo = $unorByKode[$unor] ?? null;

            // Kategori jabatan generik (4 nilai yang sama dipakai
            // pegawai.jabatan / pelatihan_wajib.kategori_jabatan) --
            // dipakai untuk mengisi 1.488 pegawai yang cuma ada di file ini
            // (lihat buildPegawaiRows), yang aslinya kolom 'jabatan'-nya
            // kosong total sehingga tidak pernah dapat rekomendasi/pilihan
            // pelatihan sama sekali. jabstr "9999" = Fungsional Umum,
            // "FT" = Fungsional Tertentu (dipetakan ke Jabatan Fungsional,
            // dikuatkan lagi kalau jab_id juga terisi), kode lain = jabatan
            // struktural definitif (nama posisi asli, lihat master_genpos.csv).
            $jabatanKategori = '';
            if ($jabId !== '') {
                $jabatanKategori = 'Jabatan Fungsional';
            } elseif ($jabstr === '9999') {
                $jabatanKategori = 'Jabatan Fungsional Umum';
            } elseif ($jabstr === 'FT') {
                $jabatanKategori = 'Jabatan Fungsional';
            } elseif ($jabstr !== '') {
                $jabatanKategori = 'Jabatan Struktural';
            }

            $referensi[$nip] = [
                'jabatan_fungsional_spesifik' => $jabfung['nama_jabfung'] ?? '',
                'kelas_jabatan_fungsional' => $jabfung['kelas_jab'] ?? '',
                'jabatan_umum_label' => $genpos['nm_genpos'] ?? '',
                'unor_detail' => $unorInfo['nm_unor'] ?? '',
                'jabatan_kategori' => $jabatanKategori,
            ];

            $glrDepan = trim($fields[$idx['glr_dpn']] ?? '');
            $glrBelakang = trim($fields[$idx['glr_blk']] ?? '');
            $nama = trim($fields[$idx['nama']] ?? '');
            if ($nama !== '') {
                $namaPolos[$nip] = $nama;
            }
            if ($nama !== '' && ($glrDepan !== '' || $glrBelakang !== '')) {
                $namaLengkap = $glrDepan !== '' ? $glrDepan.' '.$nama : $nama;
                if ($glrBelakang !== '') {
                    $namaLengkap .= ', '.$glrBelakang;
                }
                $namaBergelar[$nip] = $namaLengkap;
            }
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return [$referensi, $namaBergelar, $namaPolos];
    }

    /**
     * Baca CSV kecil (master_*.csv) jadi peta key => [kolom => nilai].
     *
     * @return array<string, array<string, string>>
     */
    private function readLookupCsv(string $path, string $delimiter, string $keyColumn, array $valueColumns): array
    {
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle, 0, $delimiter, '"', '\\');
        $idxKey = array_search($keyColumn, $header);
        $valueIdx = [];
        foreach ($valueColumns as $col) {
            $valueIdx[$col] = array_search($col, $header);
        }

        $map = [];
        while (($row = fgetcsv($handle, 0, $delimiter, '"', '\\')) !== false) {
            $key = trim($row[$idxKey] ?? '');
            if ($key === '') {
                continue;
            }
            $values = [];
            foreach ($valueIdx as $col => $idx) {
                $values[$col] = trim($row[$idx] ?? '');
            }
            $map[$key] = $values;
        }
        fclose($handle);

        return $map;
    }

    /**
     * @param array<string, array<string, string>> $levels
     * @param array<string, bool> $tik
     * @param array<string, bool> $manajerial
     * @param array<string, string> $namaBergelar
     * @param array<string, array<string, string>> $referensiJabatan
     * @param array<string, string> $namaPolosPegawais2026
     * @return array{0: list<array<string, string>>, 1: array<string, bool>}
     */
    private function buildPegawaiRows(Worksheet $dataAsn, array $levels, array $tik, array $manajerial, array $namaBergelar, array $referensiJabatan = [], array $namaPolosPegawais2026 = []): array
    {
        $pegawaiRows = [];
        $validNips = [];

        foreach ($this->rowsWithHeaders($dataAsn) as $row) {
            $nip = $this->sanitizeNip($row['nip_baru'] ?? '');
            if ($nip === '') {
                continue;
            }
            $validNips[$nip] = true;

            $level = $levels[$nip] ?? [];
            $category = ($level['KATEGORI ASN'] ?? '') ?: 'ASN NON TIK';
            if (isset($tik[$nip])) {
                $category = 'ASN TIK';
            } elseif (isset($manajerial[$nip])) {
                $category = 'ASN MANAJERIAR';
            }

            $referensi = $referensiJabatan[$nip] ?? [];

            $pegawaiRows[] = [
                'nip' => $nip,
                'nama' => $namaBergelar[$nip] ?? ($row['nama'] ?? ''),
                'jenis_kelamin' => $row['JENIS_KELAMIN'] ?? '',
                'umur' => $row['UMUR'] ?? '',
                'status' => $row['JENIS_PEGAWAI'] ?? '',
                'golongan_ruang' => $row['golongan_nama'] ?? '',
                'jabatan' => $row['jenis_jabatan_nama'] ?? '',
                'eselon' => isset($manajerial[$nip]) ? 'Manajerial' : '',
                'satuan_kerja' => $row['OPD'] ?? '',
                'kelompok_opd' => $row['KELOMPOK_OPD'] ?? '',
                'opd_induk' => $row['OPD_INDUK'] ?? '',
                'unor_nama' => $row['unor_nama'] ?? '',
                'instansi_kerja' => $row['instansi_kerja_nama'] ?? '',
                'alamat' => $row['alamat'] ?? '',
                'email' => $row['email'] ?? '',
                'kategori_asn' => $category,
                'level_1' => $level['LEVEL 1 ASN BERSINAR'] ?? '',
                'level_2' => $level['LEVEL 2'] ?? '',
                'level_3' => $level['LEVEL 3'] ?? '',
                'jabatan_fungsional_spesifik' => $referensi['jabatan_fungsional_spesifik'] ?? '',
                'kelas_jabatan_fungsional' => $referensi['kelas_jabatan_fungsional'] ?? '',
                'jabatan_umum_label' => $referensi['jabatan_umum_label'] ?? '',
                'unor_detail' => $referensi['unor_detail'] ?? '',
            ];
        }

        // pegawais_2026.xlsx (16.365 baris) punya 1.488 pegawai yang TIDAK ada
        // di hasil akhir.xlsx!'data asn' (14.877 baris) sama sekali -- selama
        // ini diam-diam terbuang. File itu cuma punya NIP/nama/gelar/kode
        // jabatan mentah (tidak ada jenis kelamin, umur, golongan ruang, nama
        // OPD, atau kategori jabatan), jadi field itu dikosongkan untuk
        // mereka -- lebih baik pegawainya tetap tercatat (termasuk riwayat
        // diklatnya kalau ada) daripada hilang total dari dashboard.
        foreach ($referensiJabatan as $nip => $referensi) {
            if (isset($validNips[$nip])) {
                continue;
            }
            $validNips[$nip] = true;

            $pegawaiRows[] = [
                'nip' => $nip,
                'nama' => $namaBergelar[$nip] ?? $namaPolosPegawais2026[$nip] ?? '',
                'jenis_kelamin' => '',
                'umur' => '',
                'status' => '',
                'golongan_ruang' => '',
                'jabatan' => $referensi['jabatan_kategori'] ?? '',
                'eselon' => ($referensi['jabatan_kategori'] ?? '') === 'Jabatan Struktural' ? 'Manajerial' : '',
                'satuan_kerja' => '',
                'kelompok_opd' => '',
                'opd_induk' => '',
                'unor_nama' => '',
                'instansi_kerja' => '',
                'alamat' => '',
                'email' => '',
                'kategori_asn' => 'ASN NON TIK',
                'level_1' => '',
                'level_2' => '',
                'level_3' => '',
                'jabatan_fungsional_spesifik' => $referensi['jabatan_fungsional_spesifik'] ?? '',
                'kelas_jabatan_fungsional' => $referensi['kelas_jabatan_fungsional'] ?? '',
                'jabatan_umum_label' => $referensi['jabatan_umum_label'] ?? '',
                'unor_detail' => $referensi['unor_detail'] ?? '',
            ];
        }

        return [$pegawaiRows, $validNips];
    }

    /**
     * @param array<string, bool> $validNips
     * @param list<array<string, string>> $riwayatRows
     */
    private function appendFromDiklatSiasn(string $path, array $validNips, array &$riwayatRows): void
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($this->rowsWithHeaders($sheet) as $row) {
            $added = $this->addRiwayat(
                $riwayatRows, $validNips,
                nip: $row['NIP Baru'] ?? '',
                jenis: $row['Jenis Diklat'] ?? '',
                nama: $row['Nama Diklat'] ?? '',
                nomor: $row['Nomor Sertifikat'] ?? '',
                penyelenggara: $row['Institusi Penyelenggara'] ?? '',
                mulai: $row['Tanggal Mulai'] ?? '',
                selesai: $row['Tanggal Selesai'] ?? '',
                tahun: $row['Tahun'] ?? '',
                bknId: $row['BKN_ID'] ?? '',
                sumber: 'diklat_siasn',
            );
            if ($added) {
                $riwayatRows[array_key_last($riwayatRows)]['jp'] = $row['Jumlah Jam'] ?? '';
            }
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
    }

    /**
     * @param array<string, bool> $validNips
     * @param list<array<string, string>> $riwayatRows
     */
    private function appendFromSertifikasiAsn(string $path, array $validNips, array &$riwayatRows): void
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($this->rowsWithHeaders($sheet) as $row) {
            $added = $this->addRiwayat(
                $riwayatRows, $validNips,
                nip: $row['NIP Baru'] ?? '',
                jenis: $row['Jenis'] ?? '',
                nama: $row['Nama/Modul'] ?? '',
                nomor: $row['Nomor'] ?? '',
                penyelenggara: $row['Institusi Penyelenggara'] ?? '',
                mulai: $row['Tanggal Kursus'] ?? '',
                selesai: $row['Tanggal Kursus Berakhir'] ?? '',
                tahun: '',
                bknId: $row['BKN_ID'] ?? '',
                sumber: 'sertifikasi_asn',
                statusCrawl: $row['Status_Crawl'] ?? '',
            );
            if ($added) {
                $riwayatRows[array_key_last($riwayatRows)]['jp'] = $row['Jumlah Jam'] ?? '';
            }
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
    }

    /**
     * @param array<string, bool> $validNips
     * @param list<array<string, string>> $riwayatRows
     */
    private function appendFromHasilAkhirModules(\PhpOffice\PhpSpreadsheet\Spreadsheet $hasil, array $validNips, array &$riwayatRows): void
    {
        foreach ($this->rowsWithHeaders($hasil->getSheetByName('ASN Seri 6')) as $row) {
            $this->addRiwayat(
                $riwayatRows, $validNips,
                nip: $row['NIP / NIK'] ?? '',
                jenis: 'Modul Digital',
                nama: 'ASN Bersinar Seri 6 - Efektivitas AiSN untuk Akselerasi Kinerja Birokrasi',
                nomor: $row['Ringkasan Kelulusan'] ?? '',
                penyelenggara: $row['Satuan Kerja / OPD'] ?? '',
                sumber: 'hasil_akhir_seri_6',
            );
        }

        $singleColumnModules = [
            'mengkuti arsitektur keamanan' => 'Arsitektur Keamanan SPBE',
            'data science' => 'Data Science',
        ];
        foreach ($singleColumnModules as $sheetName => $moduleName) {
            $sheet = $hasil->getSheetByName($sheetName);
            foreach ($sheet->getRowIterator() as $row) {
                $cellIterator = $row->getCellIterator('A', 'A');
                $cellIterator->setIterateOnlyExistingCells(false);
                $nip = '';
                foreach ($cellIterator as $cell) {
                    $nip = $this->clean($this->cellValue($cell));
                }
                $this->addRiwayat($riwayatRows, $validNips, nip: $nip, jenis: 'Modul Digital', nama: $moduleName, sumber: 'hasil_akhir_modul');
            }
        }

        foreach ($this->rowsWithHeaders($hasil->getSheetByName('ARTIFICIAL INTELLIGENCE')) as $row) {
            $this->addRiwayat($riwayatRows, $validNips, nip: $row['NIP'] ?? '', jenis: 'Modul Digital', nama: 'Artificial Intelligence', sumber: 'hasil_akhir_modul');
        }
    }

    /**
     * @param array<string, bool> $validNips
     * @param list<array<string, string>> $riwayatRows
     */
    private function addRiwayat(
        array &$riwayatRows,
        array $validNips,
        string $nip,
        string $jenis,
        string $nama,
        string $nomor = '',
        string $penyelenggara = '',
        string $mulai = '',
        string $selesai = '',
        string $tahun = '',
        string $bknId = '',
        string $sumber = '',
        string $statusCrawl = '',
    ): bool {
        $nip = $this->sanitizeNip($nip);
        if ($nip === '' || !isset($validNips[$nip]) || $nama === '') {
            return false;
        }

        $mulai = $this->normaliseDate($mulai);
        $selesai = $this->normaliseDate($selesai);
        $pelaksanaan = implode(' s/d ', array_filter([$mulai, $selesai], fn ($value) => $value !== ''));

        $riwayatRows[] = [
            'nip' => $nip,
            'sumber' => $sumber,
            'bkn_id' => $this->clean($bknId),
            'jenis_sertifikasi' => $this->clean($jenis),
            'nama_diklat' => $this->clean($nama),
            'no_sertifikat' => $this->clean($nomor),
            'penyelenggara' => $this->clean($penyelenggara),
            'pelaksanaan' => $pelaksanaan,
            'tanggal_mulai' => $mulai,
            'tanggal_selesai' => $selesai,
            'tahun' => $this->clean($tahun),
            'jp' => '',
            'status_crawl' => $this->clean($statusCrawl),
        ];

        return true;
    }

    /**
     * Baca satu sheet jadi baris asosiatif (baris pertama = header), setara
     * `rows_with_headers()` di skrip Python lama. Rentang kolom dikunci ke
     * `getHighestColumn()` supaya tiap baris selalu punya jumlah kolom yang
     * sama dengan header -- termasuk sel yang kosong.
     *
     * @return \Generator<array<string, string>>
     */
    private function rowsWithHeaders(Worksheet $sheet): \Generator
    {
        $highestColumn = $sheet->getHighestColumn();
        $headers = null;

        foreach ($sheet->getRowIterator() as $row) {
            $cellIterator = $row->getCellIterator('A', $highestColumn);
            $cellIterator->setIterateOnlyExistingCells(false);

            $values = [];
            foreach ($cellIterator as $cell) {
                $values[] = $this->clean($this->cellValue($cell));
            }

            if ($headers === null) {
                $headers = $values;
                continue;
            }

            yield array_combine($headers, $values);
        }
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function indexByColumn(Worksheet $sheet, string $keyColumn): array
    {
        $index = [];
        foreach ($this->rowsWithHeaders($sheet) as $row) {
            $key = $this->sanitizeNip($row[$keyColumn] ?? '');
            if ($key !== '') {
                $index[$key] = $row;
            }
        }

        return $index;
    }

    /**
     * @return array<string, bool>
     */
    private function columnValueSet(Worksheet $sheet, string $keyColumn): array
    {
        $set = [];
        foreach ($this->rowsWithHeaders($sheet) as $row) {
            $key = $this->sanitizeNip($row[$keyColumn] ?? '');
            if ($key !== '') {
                $set[$key] = true;
            }
        }

        return $set;
    }

    /**
     * Nilai sel: kalau berisi rumus, ambil nilai hasil hitung yang sudah di-cache
     * Excel di file-nya (bukan mengevaluasi ulang rumusnya -- beberapa rumus di
     * "hasil akhir.xlsx" pakai COUNTIF lintas sheet yang mahal kalau dihitung ulang).
     */
    private function cellValue(Cell $cell): mixed
    {
        if ($cell->getDataType() === DataType::TYPE_FORMULA) {
            return $cell->getOldCalculatedValue();
        }

        return $cell->getValue();
    }

    private function clean(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_float($value) && fmod($value, 1.0) === 0.0) {
            return (string) (int) $value;
        }

        return trim((string) $value);
    }

    /**
     * NIP harusnya cuma angka. Beberapa baris di file sumber ada karakter
     * nyasar (mis. ":19811014200501201" -- kemungkinan salah ketik meniru
     * trik "petik satu di depan angka" di Excel supaya tidak dibulatkan,
     * tapi pakai titik dua). Buang semua karakter selain digit di sini
     * supaya NIP yang tampil di dashboard maupun yang dipakai untuk
     * mencocokkan antar file selalu bersih angka saja.
     */
    private function sanitizeNip(string $nip): string
    {
        return preg_replace('/\D+/', '', $nip) ?? '';
    }

    private function normaliseDate(mixed $value): string
    {
        $value = $this->clean($value);

        return preg_match('/^(\d{4}-\d{2}-\d{2})/', $value, $matches) ? $matches[1] : '';
    }

    /**
     * @param list<array<string, string>> $rows
     */
    private function writeCsv(string $path, array $rows): void
    {
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        if (empty($rows)) {
            throw new \RuntimeException("Tidak ada baris untuk ditulis ke {$path}");
        }

        $handle = fopen($path, 'w');
        fputcsv($handle, array_keys($rows[0]));
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);
    }
}
