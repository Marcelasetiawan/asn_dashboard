<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pegawai extends Model
{
    protected $primaryKey = 'nip';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'nip', 'nama', 'jenis_kelamin', 'status', 'golongan_ruang', 'tmt_pangkat',
        'eselon', 'jabatan', 'tmt_jabatan', 'agama', 'satuan_kerja',
        'tmt_pensiun', 'pendidikan', 'tahun_lulus',
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
}
