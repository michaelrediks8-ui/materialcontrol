<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BarangSeeder extends Seeder
{
    /**
     * Sesuaikan 3 hal ini dengan struktur project-mu:
     * 1. $table       -> nama tabel tujuan
     * 2. kolom 'kode_barang' & 'nama_barang' -> ganti kalau nama kolommu beda
     * 3. Kalau pakai Model (mis. Barang::class), boleh ganti updateOrCreate
     *    lewat Model daripada DB::table() langsung.
     */
    public function run(): void
    {
        $table = 'barangs';
        $path  = database_path('seeders/data/nama_barang_kode_clean.csv');

        if (!file_exists($path)) {
            $this->command->error("File CSV tidak ditemukan di: {$path}");
            return;
        }

        $handle = fopen($path, 'r');

        // Buang BOM (Byte Order Mark) UTF-8 di awal file kalau ada
        $bom = "\xEF\xBB\xBF";
        $firstBytes = fread($handle, 3);
        if ($firstBytes !== $bom) {
            rewind($handle);
        }

        $header = fgetcsv($handle); // ['Kode Barang', 'Nama Barang']
        $count  = 0;

        DB::beginTransaction();

        try {
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < 2) {
                    continue; // lewati baris kosong/rusak
                }

                [$kodeBarang, $namaBarang] = $row;

                DB::table($table)->updateOrInsert(
                    ['kode_barang' => trim($kodeBarang)],
                    [
                        'nama_barang' => trim($namaBarang),
                        'updated_at'  => now(),
                        'created_at'  => now(),
                    ]
                );

                $count++;
            }

            DB::commit();
            $this->command->info("Berhasil import {$count} baris ke tabel '{$table}'.");
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->command->error('Gagal import: ' . $e->getMessage());
        } finally {
            fclose($handle);
        }
    }
}
