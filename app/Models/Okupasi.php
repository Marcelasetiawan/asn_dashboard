<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Okupasi extends Model
{
    protected $table = 'okupasi';

    protected $fillable = ['kode', 'area', 'nama', 'kualifikasi', 'definisi', 'sertifikasi'];

    public function tugas(): HasMany
    {
        return $this->hasMany(OkupasiTugas::class, 'okupasi_id');
    }
}
