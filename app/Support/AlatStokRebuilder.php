<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class AlatStokRebuilder
{
    public static function rebuild(): void
    {
        DB::transaction(function () {
            DB::table('stok_alats')->delete();

            $sql = "
                INSERT INTO stok_alats (alat_id, gudang_id, jumlah_baik, jumlah_rusak, created_at, updated_at)
                SELECT alat_id, gudang_id, SUM(baik_net), SUM(rusak_net), NOW(), NOW()
                FROM (
                    SELECT mad.alat_id, m.tujuan_id AS gudang_id,
                           COALESCE(mad.jumlah_baik, 0) AS baik_net,
                           COALESCE(mad.jumlah_rusak, 0) AS rusak_net
                    FROM mutasi_alat_details mad
                    JOIN mutasi_alats m ON m.id = mad.mutasi_alat_id
                    WHERE m.jenis_mutasi IN ('registrasi', 'transfer')
                      AND m.tujuan_type = 'gudang'
                      AND m.tujuan_id IS NOT NULL

                    UNION ALL

                    SELECT mad.alat_id, m.asal_id AS gudang_id,
                           -COALESCE(mad.jumlah_baik, 0),
                           -COALESCE(mad.jumlah_rusak, 0)
                    FROM mutasi_alat_details mad
                    JOIN mutasi_alats m ON m.id = mad.mutasi_alat_id
                    WHERE m.jenis_mutasi = 'transfer'
                      AND m.asal_type = 'gudang'
                      AND m.asal_id IS NOT NULL

                    UNION ALL

                    SELECT mad.alat_id, m.asal_id AS gudang_id,
                           -COALESCE(mad.jumlah, 0),
                           COALESCE(mad.jumlah, 0)
                    FROM mutasi_alat_details mad
                    JOIN mutasi_alats m ON m.id = mad.mutasi_alat_id
                    WHERE m.jenis_mutasi = 'rusak'
                      AND m.asal_type = 'gudang'
                      AND m.asal_id IS NOT NULL

                    UNION ALL

                    SELECT mad.alat_id, m.asal_id AS gudang_id,
                           COALESCE(mad.jumlah, 0),
                           -COALESCE(mad.jumlah, 0)
                    FROM mutasi_alat_details mad
                    JOIN mutasi_alats m ON m.id = mad.mutasi_alat_id
                    WHERE m.jenis_mutasi = 'perbaikan'
                      AND m.asal_type = 'gudang'
                      AND m.asal_id IS NOT NULL
                ) gabungan
                GROUP BY alat_id, gudang_id
            ";

            DB::statement($sql);
        });
    }
}