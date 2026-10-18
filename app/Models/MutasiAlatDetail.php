<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MutasiAlatDetail extends Model
{
    protected $guarded = [];

    public function mutasiAlat(): BelongsTo
    {
        return $this->belongsTo(MutasiAlat::class, 'mutasi_alat_id');
    }

    public function alat(): BelongsTo
    {
        return $this->belongsTo(Alat::class, 'alat_id');
    }
}