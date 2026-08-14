<?php

namespace App\Models;

use App\Services\PegawaiKlasifikasi as K;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatDiklat extends Model
{
    protected $table = 'riwayat_diklat';

    protected $fillable = [
        'nip', 'jenis_sertifikasi', 'nama_diklat', 'no_sertifikat',
        'penyelenggara', 'pelaksanaan', 'jp',
    ];

    /**
     * Relasi: 1 riwayat diklat dimiliki oleh 1 pegawai.
     */
    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'nip', 'nip');
    }

    /** Pakai: $riwayat->sertifikat_lengkap -- true kalau no_sertifikat terisi & bukan "-" */
    public function getSertifikatLengkapAttribute(): bool
    {
        return K::sertifikatLengkap($this->no_sertifikat);
    }
}
