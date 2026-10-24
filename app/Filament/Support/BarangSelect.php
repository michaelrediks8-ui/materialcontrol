<?php

namespace App\Filament\Support;

use App\Models\Barang;
use Filament\Forms\Components\Select;

/**
 * Pilihan barang yang ringan untuk form transaksi (Barang Masuk / Keluar / Transfer Gudang).
 *
 * Tanpa ->preload(): daftar barang TIDAK dimuat ke halaman. Barang baru dicari
 * lewat ketikan (maks. 30 hasil), jadi form tetap ringan walau barang ribuan
 * dan walau repeater punya banyak baris.
 */
class BarangSelect
{
    /** Cache label per request supaya baris repeater tidak query berulang. */
    protected static array $labelCache = [];

    public static function make(string $name = 'barang_id'): Select
    {
        return Select::make($name)
            ->label('Nama Barang')
            ->placeholder('Ketik kode / nama barang...')
            ->searchable()
            ->searchDebounce(120) // bawaan Filament 1000 ms (terasa lambat)
            ->searchPrompt('Ketik kode atau nama barang')
            ->noSearchResultsMessage('Barang tidak ditemukan')
            ->getSearchResultsUsing(function (string $search): array {
                $kata = preg_split('/\s+/', trim($search), -1, PREG_SPLIT_NO_EMPTY);

                return Barang::query()
                    ->select(['id', 'kode_barang', 'nama_barang'])
                    ->where(function ($q) use ($kata) {
                        // setiap kata harus cocok dengan kode ATAU nama ("semen k350")
                        foreach ($kata as $k) {
                            $q->where(fn ($w) => $w
                                ->where('nama_barang', 'like', "%{$k}%")
                                ->orWhere('kode_barang', 'like', "%{$k}%"));
                        }
                    })
                    // yang diawali kata pertama tampil lebih dulu
                    ->orderByRaw('CASE WHEN nama_barang LIKE ? THEN 0 ELSE 1 END', [($kata[0] ?? '') . '%'])
                    ->orderBy('nama_barang')
                    ->limit(30)
                    ->get()
                    ->mapWithKeys(fn ($b) => [$b->id => "[{$b->kode_barang}] {$b->nama_barang}"])
                    ->all();
            })
            ->getOptionLabelUsing(function ($value): ?string {
                if (! $value) {
                    return null;
                }

                return static::$labelCache[$value] ??= (function () use ($value) {
                    $b = Barang::select(['id', 'kode_barang', 'nama_barang'])->find($value);

                    return $b ? "[{$b->kode_barang}] {$b->nama_barang}" : null;
                })();
            });
    }

    /** Opsi satuan (kecil + besar) milik satu barang, dengan cache per request. */
    protected static array $satuanCache = [];

    public static function opsiSatuan($barangId): array
    {
        if (! $barangId) {
            return [];
        }

        return static::$satuanCache[$barangId] ??= (function () use ($barangId) {
            $barang = Barang::with(['satuan', 'satuanBesar'])->find($barangId);
            if (! $barang) {
                return [];
            }

            $opsi = [];
            if ($barang->satuan_id) {
                $opsi[$barang->satuan_id] = $barang->satuan->nama_satuan ?? '-';
            }
            if ($barang->satuan_besar_id) {
                $opsi[$barang->satuan_besar_id] = $barang->satuanBesar->nama_satuan ?? '-';
            }

            return $opsi;
        })();
    }
}