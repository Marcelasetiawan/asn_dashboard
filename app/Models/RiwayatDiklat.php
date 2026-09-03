<?php

namespace App\Models;

use App\Services\PegawaiKlasifikasi as K;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class RiwayatDiklat extends Model
{
    protected $table = 'riwayat_diklat';

    protected $fillable = [
        'nip', 'jenis_sertifikasi', 'nama_diklat', 'no_sertifikat', 'berkas_sertifikat',
        'penyelenggara', 'pelaksanaan', 'jp', 'sumber', 'bkn_id', 'status_crawl',
        'tanggal_mulai', 'tanggal_selesai', 'tahun',
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

    /** Pakai: $riwayat->berkas_url -- URL publik file sertifikat, null kalau belum diunggah. */
    public function getBerkasUrlAttribute(): ?string
    {
        return $this->berkas_sertifikat ? Storage::disk('public')->url($this->berkas_sertifikat) : null;
    }
}
