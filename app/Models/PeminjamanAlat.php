<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PeminjamanAlat extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'tanggal_pinjam' => 'datetime',
        'tanggal_rencana_kembali' => 'datetime',
        'tanggal_aktual_kembali' => 'datetime',
    ];

    public function alat(): BelongsTo
    {
        return $this->belongsTo(Alat::class, 'alat_id');
    }

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_id');
    }

    public function gudang(): BelongsTo
    {
        return $this->belongsTo(Gudang::class, 'gudang_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function pengembalians(): HasMany
    {
        return $this->hasMany(PengembalianAlat::class, 'peminjaman_alat_id');
    }

    /** Total unit yang sudah dikembalikan (semua kali pengembalian dijumlah) */
    public function qtyKembali(): int
    {
        return (int) $this->pengembalians()->sum('qty_kembali');
    }

    /** Unit yang masih di luar */
    public function sisaBelumKembali(): int
    {
        return max(0, $this->qty - $this->qtyKembali());
    }

    /**
     * Tutup peminjaman otomatis kalau semua unit sudah kembali,
     * buka lagi kalau ternyata belum (misal pengembalian dihapus/diedit).
     */
    public function syncStatusKembali(): void
    {
        $selesai = $this->qtyKembali() >= $this->qty;

        $this->updateQuietly([
            'tanggal_aktual_kembali' => $selesai
                ? $this->pengembalians()->max('tanggal_kembali')
                : null,
        ]);
    }
}