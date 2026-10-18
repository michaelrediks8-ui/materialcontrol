<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

class Barang extends Model
{
    use Searchable;

    protected $guarded = [];

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'kode_barang' => $this->kode_barang,
            'nama_barang' => $this->nama_barang,
            'spesifikasi' => $this->spesifikasi,
        ];
    }

    public function satuan(): BelongsTo
    {
        return $this->belongsTo(Satuan::class, 'satuan_id');
    }

    public function satuanBesar(): BelongsTo
    {
        return $this->belongsTo(Satuan::class, 'satuan_besar_id');
    }

    public function kategoriBarang(): BelongsTo
    {
        return $this->belongsTo(KategoriBarang::class, 'kategori_barang_id');
    }

    public function stokBarangs(): HasMany
    {
        return $this->hasMany(StokBarang::class, 'barang_id');
    }

    public static function generateNextCode(): string
    {
        $last = static::where('kode_barang', 'like', 'BRG-%')
            ->orderByRaw('CAST(SUBSTRING(kode_barang, 5) AS UNSIGNED) DESC')
            ->value('kode_barang');

        $nextNumber = $last ? ((int) substr($last, 4)) + 1 : 1;

        return 'BRG-' . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }
}