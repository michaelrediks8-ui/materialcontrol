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

    /** Total baik di semua gudang (termasuk yang sedang dipinjam) */
    public function totalBaik(): int
    {
        return (int) $this->stokAlats()->sum('jumlah_baik');
    }

    public function totalRusak(): int
    {
        return (int) $this->stokAlats()->sum('jumlah_rusak');
    }

    public function totalKeseluruhan(): int
    {
        return $this->totalBaik() + $this->totalRusak();
    }

    /**
     * Unit yang masih di luar = dipinjam dikurangi yang sudah kembali.
     * Opsional difilter per gudang asal pinjam.
     */
    public function sedangDipinjam(?int $gudangId = null): int
    {
        $query = $this->peminjaman()
            ->whereNull('tanggal_aktual_kembali')
            ->withSum('pengembalians as total_kembali', 'qty_kembali');

        if ($gudangId) {
            $query->where('gudang_id', $gudangId);
        }

        return (int) $query->get()->sum(fn ($p) => max(0, $p->qty - ($p->total_kembali ?? 0)));
    }

    /**
     * Bisa dipinjam dari gudang ini = kondisi baik di gudang
     * dikurangi yang sedang dipinjam dari gudang ini.
     */
    public function tersediaDiGudang(int $gudangId): int
    {
        $baik = (int) ($this->stokAlats()->where('gudang_id', $gudangId)->value('jumlah_baik') ?? 0);

        return $baik - $this->sedangDipinjam($gudangId);
    }

    public function sisaTersedia(): int
    {
        return $this->totalBaik() - $this->sedangDipinjam();
    }

    public function peminjamanTelat(): HasMany
    {
        return $this->peminjaman()
            ->whereNull('tanggal_aktual_kembali')
            ->whereDate('tanggal_rencana_kembali', '<', now());
    }
}