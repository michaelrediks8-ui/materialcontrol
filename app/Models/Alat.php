<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

class Alat extends Model
{
    use Searchable;

    protected $guarded = [];

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'kode_alat' => $this->kode_alat,
            'nama_alat' => $this->nama_alat,
        ];
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriAlat::class, 'kategori_alat_id');
    }

    public static function generateNextCode(): string
    {
        $last = static::where('kode_alat', 'like', 'ALT-%')
            ->orderByRaw('CAST(SUBSTRING(kode_alat, 5) AS UNSIGNED) DESC')
            ->value('kode_alat');

        $nextNumber = $last ? ((int) substr($last, 4)) + 1 : 1;

        return 'ALT-' . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }

    public function stokAlats(): HasMany
    {
        return $this->hasMany(StokAlat::class, 'alat_id');
    }

    public function peminjaman(): HasMany
    {
        return $this->hasMany(PeminjamanAlat::class);
    }

    /** Total baik milik SEMUA gudang, termasuk yang sedang dipinjam */
    public function totalBaik(): int
    {
        return (int) $this->stokAlats()->sum('jumlah_baik');
    }

    /** Total rusak di SEMUA gudang */
    public function totalRusak(): int
    {
        return (int) $this->stokAlats()->sum('jumlah_rusak');
    }

    /** Total keseluruhan milik semua gudang (baik + rusak) */
    public function totalKeseluruhan(): int
    {
        return $this->totalBaik() + $this->totalRusak();
    }

    /** Sedang dipinjam (belum dikembalikan), opsional difilter per gudang asal pinjam */
    public function sedangDipinjam(?int $gudangId = null): int
    {
        $query = $this->peminjaman()->whereNull('tanggal_aktual_kembali');

        if ($gudangId) {
            $query->where('gudang_id', $gudangId);
        }

        return (int) $query->sum('qty');
    }

    /**
     * Baik yang bisa dipinjam sekarang dari gudang ini
     * = jumlah baik milik gudang dikurangi yang sedang dipinjam.
     * Dipakai juga sebagai batas maksimal qty saat meminjam.
     */
    public function tersediaDiGudang(int $gudangId): int
    {
        $baik = (int) ($this->stokAlats()->where('gudang_id', $gudangId)->value('jumlah_baik') ?? 0);

        return max(0, $baik - $this->sedangDipinjam($gudangId));
    }

    /** Total baik yang bisa dipinjam di semua gudang gabungan */
    public function sisaTersedia(): int
    {
        return max(0, $this->totalBaik() - $this->sedangDipinjam());
    }

    public function peminjamanTelat(): HasMany
    {
        return $this->peminjaman()
            ->whereNull('tanggal_aktual_kembali')
            ->whereDate('tanggal_rencana_kembali', '<', now());
    }
}