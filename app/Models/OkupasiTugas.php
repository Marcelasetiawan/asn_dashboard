<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OkupasiTugas extends Model
{
    protected $table = 'okupasi_tugas';

    protected $fillable = ['okupasi_id', 'nama', 'kode_standar'];

    public function okupasi(): BelongsTo
    {
        return $this->belongsTo(Okupasi::class, 'okupasi_id');
    }
}
