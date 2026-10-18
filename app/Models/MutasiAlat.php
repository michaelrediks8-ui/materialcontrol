<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MutasiAlat extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected static function booted(): void
    {
        static::created(fn () => \App\Support\AlatStokRebuilder::rebuild());
        static::updated(fn () => \App\Support\AlatStokRebuilder::rebuild());
        static::deleted(fn () => \App\Support\AlatStokRebuilder::rebuild());
    }

    public function details(): HasMany
    {
        return $this->hasMany(MutasiAlatDetail::class, 'mutasi_alat_id');
    }

    public function namaAsal(): string
    {
        if ($this->asal_type === 'gudang') {
            return Gudang::find($this->asal_id)?->nama_gudang ?? '-';
        }
        return '-';
    }

    public function namaTujuan(): string
    {
        if ($this->tujuan_type === 'gudang') {
            return Gudang::find($this->tujuan_id)?->nama_gudang ?? '-';
        }
        return '-';
    }
}