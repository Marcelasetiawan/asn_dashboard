<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JabatanOkupasiMapping extends Model
{
    protected $table = 'jabatan_okupasi_mapping';

    protected $fillable = ['jabatan', 'okupasi_id', 'skor', 'sumber', 'catatan'];

    public function okupasi(): BelongsTo
    {
        return $this->belongsTo(Okupasi::class, 'okupasi_id');
    }
}
