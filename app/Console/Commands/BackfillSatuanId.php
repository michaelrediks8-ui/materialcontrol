<?php

namespace App\Console\Commands;

use App\Models\Satuan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillSatuanId extends Command
{
    protected $signature = 'satuan:backfill';
    protected $description = 'Pindahkan satuan_besar (teks) dan satuan_catatan (teks) yang lama ke kolom FK satuan_id, berdasarkan kecocokan nama/kode.';

    public function handle(): int
    {
        $satuans = Satuan::all();

        // bikin kamus pencarian: nama & kode (di-lower+trim) -> id
        $kamus = [];
        foreach ($satuans as $s) {
            $kamus[strtolower(trim($s->nama_satuan))] = $s->id;
            $kamus[strtolower(trim($s->kode_satuan))] = $s->id;
        }

        // ---- 1. barangs.satuan_besar (teks) -> satuan_besar_id ----
        $barangs = DB::table('barangs')->whereNotNull('satuan_besar')->where('satuan_besar', '!=', '')->get();
        $cocokBarang = 0;
        $gagalBarang = [];

        foreach ($barangs as $b) {
            $key = strtolower(trim($b->satuan_besar));
            if (isset($kamus[$key])) {
                DB::table('barangs')->where('id', $b->id)->update(['satuan_besar_id' => $kamus[$key]]);
                $cocokBarang++;
            } else {
                $gagalBarang[] = "  - Barang #{$b->id} ({$b->nama_barang}): \"{$b->satuan_besar}\"";
            }
        }

        // ---- 2. transaksi_barang_details.satuan_catatan (teks) -> satuan_id ----
        $details = DB::table('transaksi_barang_details')
            ->whereNotNull('satuan_catatan')->where('satuan_catatan', '!=', '')
            ->get();
        $cocokDetail = 0;
        $gagalDetail = [];

        foreach ($details as $d) {
            $key = strtolower(trim($d->satuan_catatan));
            if (isset($kamus[$key])) {
                DB::table('transaksi_barang_details')->where('id', $d->id)->update(['satuan_id' => $kamus[$key]]);
                $cocokDetail++;
            } else {
                $gagalDetail[] = "  - Detail #{$d->id} (transaksi_barang_id {$d->transaksi_barang_id}): \"{$d->satuan_catatan}\"";
            }
        }

        $this->info("Barang: {$cocokBarang} cocok, " . count($gagalBarang) . " tidak ketemu.");
        if ($gagalBarang) {
            $this->warn('Satuan Besar yang TIDAK ketemu (perlu dicek manual / tambah ke tabel satuans):');
            foreach (array_unique($gagalBarang) as $line) $this->line($line);
        }

        $this->info("Detail transaksi: {$cocokDetail} cocok, " . count($gagalDetail) . " tidak ketemu.");
        if ($gagalDetail) {
            $this->warn('Satuan transaksi yang TIDAK ketemu:');
            foreach (array_unique($gagalDetail) as $line) $this->line($line);
        }

        $this->info('Selesai. Kolom lama (satuan_besar, satuan_catatan) SENGAJA tidak dihapus dulu — biarkan sebagai cadangan sampai kamu yakin semua sudah benar.');

        return self::SUCCESS;
    }
}
