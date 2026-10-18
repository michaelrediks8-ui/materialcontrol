<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class StokRebuilder
{
   
    public static function rebuild(): void
    {
        DB::transaction(function () {
            DB::table('stok_barangs')->delete();

            $konversi = "
                (td.qty * CASE
                    WHEN td.satuan_id = b.satuan_id THEN 1
                    WHEN td.satuan_id = b.satuan_besar_id AND b.isi_per_satuan_besar > 0
                        THEN b.isi_per_satuan_besar
                    ELSE 1
                END)
            ";

            $sql = "
                INSERT INTO stok_barangs (gudang_id, barang_id, stok, created_at, updated_at)
                SELECT gudang_id, barang_id, SUM(qty_net), NOW(), NOW()
                FROM (
                    SELECT
                        t.tujuan_id AS gudang_id,
                        td.barang_id,
                        {$konversi} AS qty_net
                    FROM transaksi_barang_details td
                    JOIN transaksi_barangs t ON t.id = td.transaksi_barang_id
                    JOIN barangs b ON b.id = td.barang_id
                    WHERE t.tujuan_type = 'gudang' AND t.tujuan_id IS NOT NULL

                    UNION ALL

                    SELECT
                        t.asal_id AS gudang_id,
                        td.barang_id,
                        -{$konversi} AS qty_net
                    FROM transaksi_barang_details td
                    JOIN transaksi_barangs t ON t.id = td.transaksi_barang_id
                    JOIN barangs b ON b.id = td.barang_id
                    WHERE t.asal_type = 'gudang' AND t.asal_id IS NOT NULL
                ) gabungan
                GROUP BY gudang_id, barang_id
            ";

            DB::statement($sql);
        });
    }

    
    public static function satuanTidakDikenali()
    {
        return DB::table('transaksi_barang_details as td')
            ->join('barangs as b', 'b.id', '=', 'td.barang_id')
            ->join('transaksi_barangs as t', 't.id', '=', 'td.transaksi_barang_id')
            ->whereNotNull('td.satuan_id')
            ->whereColumn('td.satuan_id', '!=', 'b.satuan_id')
            ->where(function ($q) {
                $q->whereNull('b.satuan_besar_id')
                  ->orWhereColumn('td.satuan_id', '!=', 'b.satuan_besar_id');
            })
            ->select('t.no_transaksi', 'b.nama_barang', 'td.qty', 'td.satuan_id', 'b.satuan_id as satuan_kecil_id', 'b.satuan_besar_id')
            ->get();
    }

    /** Baris yang satuan_id-nya masih NULL (belum sempat diisi/backfill) */
    public static function satuanKosong()
    {
        return DB::table('transaksi_barang_details as td')
            ->join('barangs as b', 'b.id', '=', 'td.barang_id')
            ->join('transaksi_barangs as t', 't.id', '=', 'td.transaksi_barang_id')
            ->whereNull('td.satuan_id')
            ->select('t.no_transaksi', 'b.nama_barang', 'td.qty', 'td.satuan_catatan')
            ->get();
    }
}
