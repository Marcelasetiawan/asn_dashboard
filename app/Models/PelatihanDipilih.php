<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PelatihanDipilih extends Model
{
    protected $table = 'pelatihan_dipilih';

    protected $fillable = [
        'nip', 'nama_okupasi', 'nama_pelatihan', 'kode_standar', 'dipilih_pada',
    ];

    protected $casts = [
        'dipilih_pada' => 'datetime',
    ];

    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class, 'nip', 'nip');
    }
}
