<?php

namespace App\Services;

use App\Models\Pegawai;
use App\Models\RiwayatDiklat;

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

        $pegawaiOut = [];
        foreach ($pegawaiRows as $p) {
            [$gelarDepan, $namaBersih, $gelarBelakang] = PegawaiKlasifikasi::pisahNamaGelar($p->nama);
            $jabatanIt = PegawaiKlasifikasi::jabatanMengandungIt($p->jabatan);
            $punyaEselon = !empty($p->eselon);
            $kelompok = PegawaiKlasifikasi::kelompok($p->jabatan, $p->eselon);
            $bidangGelar = PegawaiKlasifikasi::bidangGelar($gelarBelakang);
            $jabatanSesuai = PegawaiKlasifikasi::jabatanSesuaiBidang($p->jabatan, $bidangGelar);
            $rekomendasi = PegawaiKlasifikasi::rekomendasiPelatihan($bidangGelar, $jabatanSesuai, $punyaEselon);

            $riwayatPegawai = $diklatByNip[$p->nip] ?? [];
            $jumlahDiklat = count($riwayatPegawai);
            $totalJp = 0;
            $sertifikatKurang = 0;
            foreach ($riwayatPegawai as $d) {
                $totalJp += (int) ($d->jp ?? 0);
                if (!PegawaiKlasifikasi::sertifikatLengkap($d->no_sertifikat)) {
                    $sertifikatKurang++;
                }
            }

            $pegawaiOut[] = [
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
                'pendidikan' => $p->pendidikan,
                'tahun_lulus' => $p->tahun_lulus !== null ? (int) $p->tahun_lulus : null,
                'kelompok' => $kelompok,
                'jabatan_it' => $jabatanIt,
                'gelar_it' => $bidangGelar === 'Teknologi Informasi',
                'bidang_gelar' => $bidangGelar,
                'rekomendasi_pelatihan' => $rekomendasi,
                'sudah_diklat' => $jumlahDiklat > 0,
                'jumlah_diklat' => $jumlahDiklat,
                'total_jp' => $totalJp,
                'sertifikat_kurang' => $sertifikatKurang,
            ];
        }

        $diklatOut = [];
        foreach ($diklatRows as $d) {
            $diklatOut[] = [
                'nip' => $d->nip,
                'jenis_sertifikasi' => $d->jenis_sertifikasi,
                'nama_diklat' => $d->nama_diklat,
                'no_sertifikat' => $d->no_sertifikat,
                'penyelenggara' => $d->penyelenggara,
                'pelaksanaan' => $d->pelaksanaan,
                'jp' => $d->jp !== null ? (int) $d->jp : null,
                'sertifikat_lengkap' => PegawaiKlasifikasi::sertifikatLengkap($d->no_sertifikat),
            ];
        }

        return [
            'pegawai' => $pegawaiOut,
            'diklat' => $diklatOut,
        ];
    }
}
