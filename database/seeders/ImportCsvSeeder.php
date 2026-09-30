<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Departemen;
use App\Models\Karyawan;

class ImportCsvSeeder extends Seeder
{
    public function run(): void
    {
        // 1. IMPORT DATA DEPARTEMEN DARI CSV
        $deptPath = database_path('seeders/data/departemen.csv');
        if (file_exists($deptPath)) {
            $countDept = 0;
            $file = fopen($deptPath, 'r');
            $delimiter = $this->detectDelimiter($deptPath);
            $isHeader = true;

            while (($row = fgetcsv($file, 1000, $delimiter)) !== false) {
                // Bersihkan Karakter BOM UTF-8 jika ada
                if (isset($row[0])) {
                    $row[0] = preg_replace('/\x{FEFF}/u', '', $row[0]);
                }

                if ($isHeader) { $isHeader = false; continue; }
                
                if (!empty(trim($row[0] ?? ''))) {
                    Departemen::updateOrCreate(
                        ['nama_departemen' => trim($row[0])],
                        ['kode_departemen' => !empty($row[1]) ? trim($row[1]) : null]
                    );
                    $countDept++;
                }
            }
            fclose($file);
            $this->command->info("Data Departemen berhasil dimuat: {$countDept} data!");
        } else {
            $this->command->warn("File departemen.csv tidak ditemukan.");
        }

        // 2. IMPORT DATA KARYAWAN DARI CSV
        $karyawanPath = database_path('seeders/data/karyawan.csv');
        if (file_exists($karyawanPath)) {
            $countKaryawan = 0;
            $file = fopen($karyawanPath, 'r');
            $delimiter = $this->detectDelimiter($karyawanPath);
            $isHeader = true;

            while (($row = fgetcsv($file, 1000, $delimiter)) !== false) {
                // Bersihkan Karakter BOM UTF-8
                if (isset($row[0])) {
                    $row[0] = preg_replace('/\x{FEFF}/u', '', $row[0]);
                }

                if ($isHeader) { $isHeader = false; continue; }

                $nik = !empty($row[0]) ? trim($row[0]) : null;
                $nama = !empty($row[1]) ? trim($row[1]) : null;

                if ($nik || $nama) {
                    $deptId = null;
                    if (!empty($row[2])) {
                        $dept = Departemen::where('nama_departemen', trim($row[2]))->first();
                        $deptId = $dept?->id;
                    }

                    // Sesuaikan dengan nama kolom tabel 'karyawans' di SQLyog: 'nama'
                    Karyawan::updateOrCreate(
                        [
                            'nik' => $nik ?? $nama,
                        ],
                        [
                            'nama'          => $nama ?? $nik, // 💡 Kolom DB bernama 'nama'
                            'departemen_id' => $deptId,
                            'jabatan'       => !empty($row[3]) ? trim($row[3]) : 'Staf',
                            'no_hp'         => !empty($row[4]) ? trim($row[4]) : null,
                            'alamat'        => !empty($row[5]) ? trim($row[5]) : null,
                        ]
                    );
                    $countKaryawan++;
                }
            }
            fclose($file);
            $this->command->info("Data Karyawan berhasil dimuat: {$countKaryawan} data!");
        } else {
            $this->command->error("File karyawan.csv tidak ditemukan.");
        }
    }

    private function detectDelimiter($filePath)
    {
        $fileHeader = fopen($filePath, 'r');
        $firstLine = fgets($fileHeader);
        fclose($fileHeader);

        if (strpos($firstLine, ';') !== false) return ';';
        if (strpos($firstLine, "\t") !== false) return "\t";
        return ',';
    }
}