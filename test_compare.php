<?php
// Standalone cross-check: run PegawaiKlasifikasi over the same pegawai.csv /
// riwayat_diklat.csv used to build dashboard_data.json, and diff every
// derived field against what proses_data.py (Python) computed. Not part of
// the Laravel app -- just a one-off verification script.

require __DIR__ . '/app/Services/PegawaiKlasifikasi.php';

use App\Services\PegawaiKlasifikasi as K;

$csvPath = $argv[1] ?? __DIR__ . '/../v5/LaravelApp/database/csv/pegawai.csv';
$diklatCsvPath = $argv[2] ?? __DIR__ . '/../v5/LaravelApp/database/csv/riwayat_diklat.csv';
$jsonPath = $argv[3] ?? __DIR__ . '/../dashboard_data.json';

$expected = json_decode(file_get_contents($jsonPath), true);
$expectedByNip = [];
foreach ($expected['pegawai'] as $p) {
    $expectedByNip[$p['nip']] = $p;
}

// -- load diklat.csv to compute sudah_diklat / jumlah_diklat / sertifikat_kurang --
$fh = fopen($diklatCsvPath, 'r');
$header = fgetcsv($fh);
$diklatByNip = [];
while (($row = fgetcsv($fh)) !== false) {
    $rec = array_combine($header, $row);
    $diklatByNip[$rec['nip']][] = $rec;
}
fclose($fh);

$fh = fopen($csvPath, 'r');
$header = fgetcsv($fh);
$mismatches = 0;
$total = 0;

while (($row = fgetcsv($fh)) !== false) {
    $rec = array_combine($header, $row);
    $nip = $rec['nip'];
    if (!isset($expectedByNip[$nip])) continue;
    $exp = $expectedByNip[$nip];
    $total++;

    [$gelarDepan, $namaBersih, $gelarBelakang] = K::pisahNamaGelar($rec['nama']);
    $kelompok = K::kelompok($rec['jabatan'], $rec['eselon'] ?: null);
    $bidang = K::bidangGelar($gelarBelakang);
    $jabatanSesuai = K::jabatanSesuaiBidang($rec['jabatan'], $bidang);
    $punyaEselon = !empty($rec['eselon']);
    $rekomendasi = K::rekomendasiPelatihan($bidang, $jabatanSesuai, $punyaEselon);

    $diklatList = $diklatByNip[$nip] ?? [];
    $sudahDiklat = count($diklatList) > 0;
    $jumlahDiklat = count($diklatList);
    $sertifikatKurang = 0;
    foreach ($diklatList as $d) {
        if (!K::sertifikatLengkap($d['no_sertifikat'] ?? null)) $sertifikatKurang++;
    }

    $checks = [
        'gelar_depan' => [$gelarDepan, $exp['gelar_depan'] ?? ''],
        'nama_bersih' => [$namaBersih, $exp['nama_bersih'] ?? ''],
        'gelar_belakang' => [$gelarBelakang, $exp['gelar_belakang'] ?? ''],
        'kelompok' => [$kelompok, $exp['kelompok']],
        'bidang_gelar' => [$bidang, $exp['bidang_gelar']],
        'rekomendasi_pelatihan' => [$rekomendasi, (bool) $exp['rekomendasi_pelatihan']],
        'sudah_diklat' => [$sudahDiklat, (bool) $exp['sudah_diklat']],
        'jumlah_diklat' => [$jumlahDiklat, (int) $exp['jumlah_diklat']],
        'sertifikat_kurang' => [$sertifikatKurang, (int) $exp['sertifikat_kurang']],
    ];

    foreach ($checks as $field => [$actual, $expectedVal]) {
        // null vs null / string vs string comparisons; normalize null-ish
        $a = $actual === '' ? null : $actual;
        $e = $expectedVal === '' ? null : $expectedVal;
        if ($a !== $e) {
            $mismatches++;
            echo "MISMATCH nip=$nip field=$field php=" . var_export($actual, true) . " python=" . var_export($expectedVal, true) . "\n";
        }
    }
}
fclose($fh);

echo "\nTotal pegawai dibandingkan: $total\n";
echo "Total mismatch: $mismatches\n";
echo $mismatches === 0 ? "COCOK 100% dengan hasil Python.\n" : "ADA PERBEDAAN -- cek di atas.\n";
