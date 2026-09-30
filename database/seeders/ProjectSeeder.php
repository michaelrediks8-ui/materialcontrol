<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Project;
use App\Models\PurchaseOrder;
use Carbon\Carbon;
use Exception;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $filePath = database_path('data_po.csv');

        if (!file_exists($filePath)) {
            $this->command->error("File data_po.csv tidak ditemukan di folder database!");
            return;
        }

        $fileHeader = fopen($filePath, 'r');
        $firstLine = fgets($fileHeader);
        fclose($fileHeader);
        $delimiter = (strpos($firstLine, ';') !== false) ? ';' : ',';

        $file = fopen($filePath, 'r');

        while (($row = fgetcsv($file, 1000, $delimiter)) !== false) {
            if (empty($row[2]) || trim($row[2]) === 'DESCRIPTION PEKERJAAN' || trim($row[5]) === 'NO PO') {
                continue;
            }

            $tanggalMulai = null;
            if (!empty($row[6]) && trim($row[6]) !== '-') {
                try { $tanggalMulai = Carbon::parse($row[6]); } catch (Exception $e) {}
            }

            $targetSelesai = null;
            if (!empty($row[7]) && trim($row[7]) !== '-') {
                try { $targetSelesai = Carbon::parse($row[7]); } catch (Exception $e) {}
            }

            // 1. Simpan Data Project (Tanpa kolom no_po karena sudah tidak ada di tabel ini)
            $project = Project::updateOrCreate(
                [
                    'nama_project'   => trim($row[2]),
                ],
                [
                    // Baris 'no_po' DIHAPUS DARI SINI
                    'area'           => !empty($row[3]) ? trim($row[3]) : null,
                    'pic'            => !empty($row[4]) ? trim($row[4]) : null,
                    'status'         => !empty($row[8]) ? trim($row[8]) : 'aktif',
                    'tanggal_mulai'  => $tanggalMulai,
                    'target_selesai' => $targetSelesai,
                ]
            );

            // 2. Simpan Data Purchase Order (PO) 
            if (!empty($row[5]) && trim($row[5]) !== '-') {
                PurchaseOrder::updateOrCreate(
                    [
                        'no_po'      => trim($row[5]),
                    ],
                    [
                        'project_id' => $project->id, // Menyambungkan dengan ID project di atas
                        'tanggal_po' => $tanggalMulai,
                        'status'     => 'open', 
                    ]
                );
            }
        }
        
        fclose($file);
        $this->command->info("Data PO Project berhasil di-seed ke tabel Projects dan Purchase Orders!");
    }
}