<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Query Laporan Transaksi Barang — dipakai bersama oleh halaman laporan dan export Excel.
 */
class LaporanTransaksiBarangQuery
{
    /**
     * @param  array{dari?:?string,sampai?:?string,status?:?string,project?:?string,search?:?string}  $f
     */
    public static function build(array $f = []): Builder
    {
        $q = DB::table('transaksi_barang_details as tbd')
            ->join('transaksi_barangs as tb', 'tbd.transaksi_barang_id', '=', 'tb.id')
            ->leftJoin('barangs as b', 'tbd.barang_id', '=', 'b.id')
            ->leftJoin('satuans as s', 'tbd.satuan_id', '=', 's.id')
            ->leftJoin('gudangs as g_asal', 'tb.asal_id', '=', 'g_asal.id')
            ->leftJoin('vendors as v', 'tb.asal_id', '=', 'v.id')
            ->leftJoin('projects as p', 'tbd.project_id', '=', 'p.id')
            ->leftJoin('purchase_orders as po', 'tb.purchase_order_id', '=', 'po.id')
            ->leftJoin('projects as po_p', 'po.project_id', '=', 'po_p.id')
            ->select([
                'tbd.id as id',
                'tb.tanggal_transaksi as tanggal',
                'b.nama_barang',
                DB::raw("CASE
                    WHEN b.nama_barang LIKE '%Kawat RB%' THEN CAST(tbd.qty AS CHAR)
                    ELSE CAST(CAST(ROUND(tbd.qty) AS SIGNED) AS CHAR)
                END AS qty"),
                's.nama_satuan as satuan',
                DB::raw('UPPER(tb.jenis_transaksi) AS status_barang'),
                DB::raw("COALESCE(v.nama_vendor, g_asal.nama_gudang, '-') AS asal"),
                DB::raw("COALESCE(tb.request_oleh, '-') AS nama_req"),
                DB::raw("COALESCE(tb.diterima_oleh, '-') AS nama_terima"),
                DB::raw("COALESCE(p.nama_project, po_p.nama_project, '-') AS ke_project"),
                DB::raw("COALESCE(po.no_po, '-') AS no_po"),
                DB::raw("COALESCE(p.area, '-') AS area"),
                'tb.no_surat_jalan',
            ])
            ->whereRaw("COALESCE(v.nama_vendor, g_asal.nama_gudang, '') NOT LIKE '%Stok Awal%'")
            ->whereRaw("COALESCE(v.nama_vendor, g_asal.nama_gudang, '') NOT LIKE '%Saldo Awal%'");

        if (filled($f['dari'] ?? null)) {
            $q->whereDate('tb.tanggal_transaksi', '>=', $f['dari']);
        }

        if (filled($f['sampai'] ?? null)) {
            $q->whereDate('tb.tanggal_transaksi', '<=', $f['sampai']);
        }

        if (filled($f['status'] ?? null)) {
            $q->where('tb.jenis_transaksi', $f['status']);
        }

        if (filled($f['project'] ?? null)) {
            $q->whereRaw('COALESCE(p.id, po_p.id) = ?', [$f['project']]);
        }

        if (filled($f['search'] ?? null)) {
            $s = '%' . trim($f['search']) . '%';

            $q->where(function ($w) use ($s) {
                $w->where('b.nama_barang', 'like', $s)
                    ->orWhere('v.nama_vendor', 'like', $s)
                    ->orWhere('g_asal.nama_gudang', 'like', $s)
                    ->orWhere('tb.request_oleh', 'like', $s)
                    ->orWhere('tb.diterima_oleh', 'like', $s)
                    ->orWhere('p.nama_project', 'like', $s)
                    ->orWhere('po_p.nama_project', 'like', $s)
                    ->orWhere('po.no_po', 'like', $s)
                    ->orWhere('p.area', 'like', $s)
                    ->orWhere('tb.no_surat_jalan', 'like', $s);
            });
        }

        return $q->orderBy('tb.tanggal_transaksi')->orderBy('tbd.id');
    }
}