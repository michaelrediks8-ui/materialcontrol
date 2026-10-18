<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengembalianAlat extends Model
{
    protected $guarded = [];

    protected $casts = [
        'tanggal_kembali' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Setiap pengembalian dicatat/diubah/dihapus, status peminjaman induknya dihitung ulang
        $sync = fn (PengembalianAlat $pengembalian) => $pengembalian->peminjamanAlat?->syncStatusKembali();

        static::saved($sync);
        static::deleted($sync);
    }

    public function peminjamanAlat(): BelongsTo
    {
        return $this->belongsTo(PeminjamanAlat::class, 'peminjaman_alat_id');
    }
}