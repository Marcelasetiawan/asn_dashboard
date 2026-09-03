<?php

namespace App\Services;

use App\Models\Pegawai;
use App\Models\RiwayatDiklat;
use App\Models\PelatihanDipilih;
use App\Models\PelatihanWajib;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Satu tempat untuk menghitung payload {"pegawai": [...], "diklat": [...]}
 * yang dipakai baik oleh endpoint API JSON (DashboardDataController) maupun
 * halaman Blade (BangkomDashboardController) -- supaya logikanya tidak
 * dobel-tulis di dua tempat dan berisiko "menceng" satu sama lain.
 *
 * Dihitung manual (bukan lewat accessor Model satu-satu per baris) supaya
 * tidak kena N+1 query waktu memproses ribuan baris riwayat diklat sekaligus.
 */
class BangkomDashboardData
{
    public static function build(): array
    {
        $pegawaiRows = Pegawai::all();
        $diklatRows = RiwayatDiklat::orderBy('nip')->get();

        $diklatByNip = [];
        foreach ($diklatRows as $d) {
            $diklatByNip[$d->nip][] = $d;
        }

        $dipilihByNip = self::dipilihByNip();
        [$wajibByKategori, $LEVEL_URUTAN] = self::wajibByKategori();

        $pegawaiOut = [];
        foreach ($pegawaiRows as $p) {
            $pegawaiOut[] = self::buildRow($p, $diklatByNip[$p->nip] ?? [], $dipilihByNip[$p->nip] ?? [], $wajibByKategori, $LEVEL_URUTAN);
        }

        $diklatOut = [];
        foreach ($diklatRows as $d) {
            $diklatOut[] = self::diklatRow($d);
        }

        return [
            'pegawai' => $pegawaiOut,
            'diklat' => $diklatOut,
        ];
    }

    /**
     * Sama seperti build(), tapi cuma untuk 1 pegawai -- dipakai halaman
     * "Profil Saya" (self-service ASN) supaya tidak perlu memuat &
     * menghitung data 14 ribuan pegawai lain yang tidak relevan.
     */
    public static function buildForNip(string $nip): ?array
    {
        $p = Pegawai::find($nip);
        if (!$p) {
            return null;
        }

        $riwayatPegawai = RiwayatDiklat::where('nip', $nip)->get();
        $dipilih = PelatihanDipilih::where('nip', $nip)->get()->map(fn ($row) => [
            'nama_okupasi' => $row->nama_okupasi,
            'nama_pelatihan' => $row->nama_pelatihan,
            'kode_standar' => $row->kode_standar,
        ])->all();
        [$wajibByKategori, $LEVEL_URUTAN] = self::wajibByKategori();

        return [
            'pegawai' => self::buildRow($p, $riwayatPegawai, $dipilih, $wajibByKategori, $LEVEL_URUTAN),
            'diklat' => $riwayatPegawai->map(fn ($d) => self::diklatRow($d))->all(),
        ];
    }

    /**
     * Rata-rata total JP diklat seluruh ASN (dipakai sebagai pembanding di
     * halaman "Ringkasan Saya" milik ASN, mis. "JP Anda: 24, rata-rata
     * seluruh ASN: 18"). Pembaginya TOTAL seluruh pegawai (termasuk yang
     * belum pernah diklat sama sekali = 0 JP) -- sama seperti konvensi
     * "Rata-rata JP Diklat" yang sudah dipakai di halaman "ASN per
     * Golongan" milik admin, supaya angkanya konsisten & tidak membingungkan.
     *
     * Dihitung lewat query agregat langsung di database (bukan load semua
     * Eloquent model) supaya ringan, dan di-cache 1 jam karena angkanya
     * sama untuk semua orang & tidak berubah tiap detik.
     */
    public static function rataRataJpSeluruhAsn(): float
    {
        return Cache::remember('rata_rata_jp_seluruh_asn', 3600, function () {
            $totalJp = (float) DB::table('riwayat_diklat')->sum('jp');
            $totalPegawai = DB::table('pegawai')->count();

            return $totalPegawai > 0 ? round($totalJp / $totalPegawai, 1) : 0.0;
        });
    }

    /**
     * Pilihan pelatihan yang sudah disimpan tiap pegawai sebelumnya,
     * dikelompokkan per nip (sekali query saja -- bukan per pegawai).
     */
    private static function dipilihByNip(): array
    {
        $dipilihByNip = [];
        foreach (PelatihanDipilih::all() as $row) {
            $dipilihByNip[$row->nip][] = [
                'nama_okupasi' => $row->nama_okupasi,
                'nama_pelatihan' => $row->nama_pelatihan,
                'kode_standar' => $row->kode_standar,
            ];
        }
        return $dipilihByNip;
    }

    /**
     * Kurikulum Pelatihan Wajib per kategori jabatan (pegawai.jabatan),
     * dikelompokkan sekali -- bukan query per pegawai.
     *
     * @return array{0: array, 1: array<string,int>}
     */
    private static function wajibByKategori(): array
    {
        $LEVEL_URUTAN = ['Dasar' => 1, 'Menengah' => 2, 'Tinggi' => 3];
        $wajibByKategori = [];
        foreach (PelatihanWajib::orderBy('urutan')->get() as $w) {
            $wajibByKategori[$w->kategori_jabatan][] = $w;
        }
        return [$wajibByKategori, $LEVEL_URUTAN];
    }

    /**
     * Susun 1 baris payload pegawai (dipakai baik untuk daftar semua
     * pegawai di build(), maupun 1 pegawai saja di buildForNip()).
     */
    private static function buildRow(Pegawai $p, iterable $riwayatPegawai, array $dipilih, array $wajibByKategori, array $LEVEL_URUTAN): array
    {
        [$gelarDepan, $namaBersih, $gelarBelakang] = PegawaiKlasifikasi::pisahNamaGelar($p->nama);
        $jabatanIt = $p->jabatan_it;
        $punyaEselon = !empty($p->eselon) || $p->kategori_asn === 'ASN MANAJERIAR';
        $kelompok = $p->kelompok;
        $bidangGelar = PegawaiKlasifikasi::bidangGelar($gelarBelakang);
        $jabatanSesuai = PegawaiKlasifikasi::jabatanSesuaiBidang($p->jabatan, $bidangGelar);
        $rekomendasi = PegawaiKlasifikasi::rekomendasiPelatihan($bidangGelar, $jabatanSesuai, $punyaEselon);
        $rekomendasiUmum = PegawaiKlasifikasi::rekomendasiUmum($bidangGelar, $rekomendasi);

        $jumlahDiklat = 0;
        $totalJp = 0;
        $sertifikatKurang = 0;
        foreach ($riwayatPegawai as $d) {
            $jumlahDiklat++;
            $totalJp += (int) ($d->jp ?? 0);
            if (!PegawaiKlasifikasi::sertifikatLengkap($d->no_sertifikat)) {
                $sertifikatKurang++;
            }
        }

        // Rekomendasi pelatihan versi Peta Okupasi TIK: SEMUA pegawai
        // yang direkomendasikan penyegaran (rekomendasi_pelatihan =
        // true, apapun bidang gelarnya) dicarikan okupasi TIK yang
        // paling relevan dengan JABATANNYA SEKARANG, lewat
        // jabatan_okupasi_mapping.
        //
        // Datanya 2 sumber (lihat kolom 'sumber' & 'skor' yang ikut
        // dikirim ke frontend supaya bisa ditandai):
        // - 'tinjauan_manual_ai' (skor 1.0): jabatan yang MEMANG jelas
        //   pekerjaan TIK, sudah ditinjau satu-satu -- bisa dipercaya.
        // - 'auto_tfidf_terdekat' (skor bisa rendah): untuk jabatan
        //   yang bidangnya beda jauh dari TIK (misalnya Nutrisionis,
        //   Auditor), sistem tetap mengambil okupasi TIK PALING MIRIP
        //   secara teks -- TAPI ini cuma perkiraan kasar, bisa saja
        //   tidak relevan sama sekali. Skor rendah = tanda supaya
        //   hasilnya ditinjau ulang manual, bukan langsung dipakai.
        //
        // status_okupasi = 'tersedia' kalau ketemu, atau
        // 'jabatan_belum_dipetakan' kalau jabatannya belum ada baris
        // pemetaan sama sekali (misalnya pegawai baru).
        //
        // PENTING: dihitung untuk SEMUA pegawai (bukan cuma yang
        // rekomendasi_pelatihan = true) -- halaman "Rekomendasi
        // Pelatihan" di frontend menampilkan SELURUH pegawai: yang
        // jabatannya sudah TIK (jabatan_it = true) ditandai "Sudah
        // TIK" dan okupasinya jadi identitas kerja saat ini; yang
        // jabatannya belum TIK ditandai "Rekomendasi ke TIK" dan
        // okupasinya jadi arah pelatihan penyegaran.
        $namaRiwayatPegawai = array_map(fn ($d) => $d->nama_diklat, is_array($riwayatPegawai) ? $riwayatPegawai : iterator_to_array($riwayatPegawai));

        $opsiPelatihan = OkupasiRekomendasi::pilihanPelatihanUntukJabatan($p->jabatan, $p->jabatan_fungsional_spesifik);
        $statusOkupasi = $opsiPelatihan ? 'tersedia' : 'jabatan_belum_dipetakan';
        if ($opsiPelatihan) {
            foreach ($opsiPelatihan['pilihan'] as &$item) {
                $item['kemungkinan_sudah_diikuti'] = self::cekKemungkinanSudahDiikuti(
                    $item['nama'],
                    $namaRiwayatPegawai
                );
            }
            unset($item);
        }

        // Pelatihan Wajib: daftar Dasar/Menengah/Tinggi sesuai kategori
        // jabatan pegawai (pegawai.jabatan), status "sudah diikuti" dicek
        // dengan pembanding kata yang sama seperti rekomendasi TIK di atas.
        $pelatihanWajib = [];
        $wajibTerpenuhi = 0;
        foreach ($wajibByKategori[$p->jabatan] ?? [] as $w) {
            $cocok = self::cekKemungkinanSudahDiikuti($w->nama_pelatihan, $namaRiwayatPegawai);
            if ($cocok) {
                $wajibTerpenuhi++;
            }
            $pelatihanWajib[] = [
                'level' => $w->level,
                'urutan_level' => $LEVEL_URUTAN[$w->level] ?? 99,
                'nama_pelatihan' => $w->nama_pelatihan,
                'sudah_diikuti' => $cocok !== null,
                'diklat_cocok' => $cocok,
            ];
        }

        return [
            'nip' => $p->nip,
            'nama' => $p->nama,
            'nama_bersih' => $namaBersih ?: null,
            'gelar_depan' => $gelarDepan ?: null,
            'gelar_belakang' => $gelarBelakang ?: null,
            'jenis_kelamin' => $p->jenis_kelamin,
            'status' => $p->status,
            'golongan_ruang' => $p->golongan_ruang,
            'eselon' => $p->eselon,
            'jabatan' => $p->jabatan,
            'satuan_kerja' => $p->satuan_kerja,
            'jabatan_fungsional_spesifik' => $p->jabatan_fungsional_spesifik,
            'unor_detail' => $p->unor_detail,
            'pendidikan' => $p->pendidikan,
            'tahun_lulus' => $p->tahun_lulus !== null ? (int) $p->tahun_lulus : null,
            'alamat' => $p->alamat,
            'email' => $p->email,
            'kelompok' => $kelompok,
            'jabatan_it' => $jabatanIt,
            'gelar_it' => $bidangGelar === 'Teknologi Informasi',
            'bidang_gelar' => $bidangGelar,
            'rekomendasi_pelatihan' => $rekomendasi,
            'rekomendasi_pelatihan_umum' => $rekomendasiUmum,
            'rekomendasi_pelatihan_opsi' => $opsiPelatihan,
            'status_okupasi' => $statusOkupasi,
            'pelatihan_wajib' => $pelatihanWajib,
            'pelatihan_wajib_terpenuhi' => $wajibTerpenuhi,
            'pelatihan_wajib_total' => count($pelatihanWajib),
            'pelatihan_dipilih' => $dipilih,
            'sudah_diklat' => $jumlahDiklat > 0,
            'jumlah_diklat' => $jumlahDiklat,
            'total_jp' => $totalJp,
            'sertifikat_kurang' => $sertifikatKurang,
        ];
    }

    private static function diklatRow(RiwayatDiklat $d): array
    {
        return [
            'id' => $d->id,
            'nip' => $d->nip,
            'jenis_sertifikasi' => $d->jenis_sertifikasi,
            'nama_diklat' => $d->nama_diklat,
            'no_sertifikat' => $d->no_sertifikat,
            'penyelenggara' => $d->penyelenggara,
            'pelaksanaan' => $d->pelaksanaan,
            'jp' => $d->jp !== null ? (int) $d->jp : null,
            'sumber' => $d->sumber,
            'status_crawl' => $d->status_crawl,
            'sertifikat_lengkap' => PegawaiKlasifikasi::sertifikatLengkap($d->no_sertifikat),
            'berkas_url' => $d->berkas_url,
        ];
    }

    /**
     * Cek apakah 1 nama tugas/modul pelatihan (dari daftar okupasi TIK)
     * KEMUNGKINAN sudah pernah diikuti pegawai ini, dibandingkan dengan
     * nama_diklat di riwayat_diklat miliknya.
     *
     * Catatan penting: ini cuma perbandingan TEKS (irisan kata), bukan
     * pencocokan makna. Nama pelatihan riil di riwayat_diklat kebanyakan
     * memang pelatihan umum ASN (misal "Peningkatan Kapasitas Kinerja ASN
     * Adaptif", "Fostering Loyalty Across Teams") yang istilahnya jauh
     * berbeda dari nama tugas okupasi TIK (misal "Administrasi Basis
     * Data") -- jadi WAJAR kalau hasilnya kebanyakan null (belum
     * ketemu kecocokan), itu bukan berarti skripnya salah, tapi memang
     * belum pernah ada pelatihan teknis TIK yang persis untuk tugas itu.
     *
     * Return: nama_diklat yang paling mirip (kalau irisan kata >= 60% dari
     * kata di nama tugas), atau null kalau tidak ada yang cukup mirip.
     */
    private static function cekKemungkinanSudahDiikuti(string $namaTugas, array $namaRiwayatList): ?string
    {
        $kataTugas = self::normalisasiKata($namaTugas);
        if (empty($kataTugas)) {
            return null;
        }

        $namaTerbaik = null;
        $skorTerbaik = 0.0;
        foreach ($namaRiwayatList as $namaRiwayat) {
            if (!$namaRiwayat || trim($namaRiwayat) === '' || trim($namaRiwayat) === '-') {
                continue;
            }
            $kataRiwayat = self::normalisasiKata($namaRiwayat);
            if (empty($kataRiwayat)) {
                continue;
            }
            $irisan = array_intersect($kataTugas, $kataRiwayat);
            $skor = count($irisan) / count($kataTugas);
            if ($skor > $skorTerbaik) {
                $skorTerbaik = $skor;
                $namaTerbaik = $namaRiwayat;
            }
        }

        return $skorTerbaik >= 0.6 ? $namaTerbaik : null;
    }

    private static function normalisasiKata(string $teks): array
    {
        $teks = mb_strtolower($teks);
        $teks = preg_replace('/[^a-z0-9 ]/', ' ', $teks);
        $kata = array_filter(explode(' ', $teks), fn($k) => mb_strlen($k) > 2);
        return array_values($kata);
    }
}
