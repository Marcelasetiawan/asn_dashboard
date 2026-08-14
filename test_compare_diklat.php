<?php
// Cross-check sertifikat_lengkap for every riwayat_diklat row against the
// Python-derived dashboard_data.json.

require __DIR__ . '/app/Services/PegawaiKlasifikasi.php';
use App\Services\PegawaiKlasifikasi as K;

$diklatCsvPath = __DIR__ . '/../v5/LaravelApp/database/csv/riwayat_diklat.csv';
$jsonPath = __DIR__ . '/../dashboard_data.json';

$expected = json_decode(file_get_contents($jsonPath), true)['diklat'];

$fh = fopen($diklatCsvPath, 'r');
$header = fgetcsv($fh);
$i = 0;
$mismatches = 0;
while (($row = fgetcsv($fh)) !== false) {
    $rec = array_combine($header, $row);
    $actual = K::sertifikatLengkap($rec['no_sertifikat'] ?? null);
    $exp = (bool) $expected[$i]['sertifikat_lengkap'];
    if ($actual !== $exp) {
        $mismatches++;
        echo "MISMATCH row=$i no_sertifikat=" . var_export($rec['no_sertifikat'] ?? null, true) . " php=$actual python=$exp\n";
    }
    $i++;
}
fclose($fh);

echo "Total diklat dibandingkan: $i\n";
echo "Mismatch: $mismatches\n";
echo $mismatches === 0 ? "COCOK 100%.\n" : "ADA PERBEDAAN.\n";
