<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeamGudang extends Model
{
    protected $fillable = ['nama', 'posisi', 'gudang_id', 'kontak', 'aktif'];

    /** Gudang home base / penempatan utama orang ini */
    public function gudang(): BelongsTo
    {
        return $this->belongsTo(Gudang::class);
    }

    /** Semua transaksi yang pernah ditangani orang ini, di gudang manapun */
    public function transaksiDitangani(): HasMany
    {
        return $this->hasMany(TransaksiBarang::class, 'team_gudang_id');
    }
}
