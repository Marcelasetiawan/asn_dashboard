<?php

namespace App\Models;

use App\Services\PegawaiKlasifikasi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pegawai extends Model
{
    protected $table = 'pegawai';

    protected $primaryKey = 'nip';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'nip', 'nama', 'jenis_kelamin', 'status', 'golongan_ruang', 'tmt_pangkat',
        'eselon', 'jabatan', 'tmt_jabatan', 'agama', 'satuan_kerja',
        'tmt_pensiun', 'pendidikan', 'tahun_lulus', 'umur', 'kelompok_opd',
        'opd_induk', 'unor_nama', 'instansi_kerja', 'alamat', 'email',
        'kategori_asn', 'level_1', 'level_2', 'level_3',
        'jabatan_fungsional_spesifik', 'kelas_jabatan_fungsional', 'jabatan_umum_label', 'unor_detail',
    ];

    /**
     * Relasi: 1 pegawai punya banyak riwayat diklat.
     */
    public function riwayatDiklat(): HasMany
    {
        return $this->hasMany(RiwayatDiklat::class, 'nip', 'nip');
    }

    /**
     * Accessor: jumlah diklat yang pernah diikuti.
     * Pakai: $pegawai->jumlah_diklat
     */
    public function getJumlahDiklatAttribute(): int
    {
        return $this->riwayatDiklat()->count();
    }

    /**
     * Accessor: total Jam Pelajaran (JP) yang pernah ditempuh.
     * Pakai: $pegawai->total_jp
     */
    public function getTotalJpAttribute(): int
    {
        return (int) $this->riwayatDiklat()->sum('jp');
    }

    /**
     * Accessor: kategori bangkom ("Aktif" jika >=2 diklat).
     * Pakai: $pegawai->kategori_bangkom
     */
    public function getKategoriBangkomAttribute(): string
    {
        return $this->jumlah_diklat >= 2 ? 'Aktif' : 'Kurang Aktif';
    }

    /**
     * Accessor: apakah punya jabatan struktural (eselon).
     * Pakai: $pegawai->has_eselon
     */
    public function getHasEselonAttribute(): bool
    {
        return !empty($this->eselon);
    }

    /**
     * ================== ACCESSOR BARU (Dashboard Bangkom ASN) ==================
     */

    public function getGelarDepanAttribute(): ?string
    {
        return PegawaiKlasifikasi::pisahNamaGelar($this->nama)[0] ?: null;
    }

    public function getNamaBersihAttribute(): ?string
    {
        return PegawaiKlasifikasi::pisahNamaGelar($this->nama)[1] ?: null;
    }

    public function getGelarBelakangAttribute(): ?string
    {
        return PegawaiKlasifikasi::pisahNamaGelar($this->nama)[2] ?: null;
    }

    public function getJabatanItAttribute(): bool
    {
        return $this->kategori_asn === 'ASN TIK'
            || PegawaiKlasifikasi::jabatanMengandungIt($this->jabatan);
    }

    public function getKelompokAttribute(): string
    {
        return match ($this->kategori_asn) {
            'ASN TIK' => 'TIK',
            'ASN MANAJERIAR' => 'Manajerial',
            'ASN NON TIK' => 'Non TIK',
            default => PegawaiKlasifikasi::kelompok($this->jabatan, $this->eselon),
        };
    }

    public function getBidangGelarAttribute(): ?string
    {
        return PegawaiKlasifikasi::bidangGelar($this->gelar_belakang);
    }

    public function getGelarItAttribute(): bool
    {
        return $this->bidang_gelar === 'Teknologi Informasi';
    }

    public function getJabatanSesuaiGelarAttribute(): bool
    {
        return PegawaiKlasifikasi::jabatanSesuaiBidang($this->jabatan, $this->bidang_gelar);
    }

    public function getRekomendasiPelatihanAttribute(): bool
    {
        return PegawaiKlasifikasi::rekomendasiPelatihan(
            $this->bidang_gelar,
            $this->jabatan_sesuai_gelar,
            $this->has_eselon
        );
    }

    public function getSudahDiklatAttribute(): bool
    {
        return $this->jumlah_diklat > 0;
    }

    public function getSertifikatKurangAttribute(): int
    {
        return $this->riwayatDiklat()
            ->get()
            ->filter(fn ($d) => !PegawaiKlasifikasi::sertifikatLengkap($d->no_sertifikat))
            ->count();
    }
}
