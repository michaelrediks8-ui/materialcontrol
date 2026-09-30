<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BarangAlias extends Model
{
    protected $table = 'barang_alias';

    protected $fillable = ['barang_id', 'nama_alias'];

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class);
    }

    /**
     * Cari barang berdasarkan nama ATAU alias-nya.
     * Dipakai di form input supaya nama yang beda-beda tetap ketemu barang yang sama.
     */
    public static function cariBarang(string $keyword): ?Barang
    {
        $barang = Barang::where('nama_barang', 'like', "%{$keyword}%")->first();
        if ($barang) {
            return $barang;
        }

        $alias = static::where('nama_alias', 'like', "%{$keyword}%")->first();
        return $alias?->barang;
    }
}
