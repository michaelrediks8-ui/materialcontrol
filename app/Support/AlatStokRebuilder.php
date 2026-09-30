<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class AlatStokRebuilder
{
    /**
     * Hitung ulang stok_alats dari histori mutasi_alats.
     * - registrasi/transfer -> tambah di gudang tujuan
     * - transfer -> kurangi di gudang asal
     * - rusak -> di gudang yang sama: jumlah_baik turun, jumlah_rusak naik
     * - perbaikan -> kebalikannya: jumlah_rusak turun, jumlah_baik naik
     */
    public static function rebuild(): void
    {
        DB::transaction(function () {
            DB::table('stok_alats')->delete();

            $sql = "
                INSERT INTO stok_alats (alat_id, gudang_id, jumlah_baik, jumlah_rusak, created_at, updated_at)
                SELECT alat_id, gudang_id, SUM(baik_net), SUM(rusak_net), NOW(), NOW()
                FROM (
                    -- Masuk ke gudang tujuan (registrasi / transfer)
                    SELECT alat_id, tujuan_id AS gudang_id,
                           COALESCE(jumlah_baik, 0) AS baik_net,
                           COALESCE(jumlah_rusak, 0) AS rusak_net
                    FROM mutasi_alats
                    WHERE jenis_mutasi IN ('registrasi', 'transfer')
                      AND tujuan_type = 'gudang'
                      AND tujuan_id IS NOT NULL

                    UNION ALL

                    -- Keluar dari gudang asal (transfer)
                    SELECT alat_id, asal_id AS gudang_id,
                           -COALESCE(jumlah_baik, 0),
                           -COALESCE(jumlah_rusak, 0)
                    FROM mutasi_alats
                    WHERE jenis_mutasi = 'transfer'
                      AND asal_type = 'gudang'
                      AND asal_id IS NOT NULL

                    UNION ALL

                    -- Rusak: baik turun, rusak naik, di gudang yang sama
                    SELECT alat_id, asal_id AS gudang_id,
                           -COALESCE(jumlah, 0),
                           COALESCE(jumlah, 0)
                    FROM mutasi_alats
                    WHERE jenis_mutasi = 'rusak'
                      AND asal_type = 'gudang'
                      AND asal_id IS NOT NULL

                    UNION ALL

                    -- Perbaikan: rusak turun, baik naik, di gudang yang sama
                    SELECT alat_id, asal_id AS gudang_id,
                           COALESCE(jumlah, 0),
                           -COALESCE(jumlah, 0)
                    FROM mutasi_alats
                    WHERE jenis_mutasi = 'perbaikan'
                      AND asal_type = 'gudang'
                      AND asal_id IS NOT NULL
                ) gabungan
                GROUP BY alat_id, gudang_id
            ";

            DB::statement($sql);
        });
    }
}