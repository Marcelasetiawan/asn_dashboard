<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PelatihanWajib extends Model
{
    protected $table = 'pelatihan_wajib';

    protected $fillable = ['kategori_jabatan', 'level', 'nama_pelatihan', 'urutan'];
}
