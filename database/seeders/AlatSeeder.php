<?php

namespace Database\Seeders;

use App\Models\Alat;
use Illuminate\Database\Seeder;

class AlatSeeder extends Seeder
{
    /**
     * Import alat dari Master Alat / Tools (Excel lama).
     * jumlah_total yang tadinya kosong di Excel diisi 0 dulu —
     * tolong cek & lengkapi lewat halaman Filament Alat setelah import.
     */
    public function run(): void
    {
        $data = [
            ['kode_alat' => 'EL01', 'nama_alat' => 'Mesin Bor', 'kategori' => 'Elektrik', 'merk_catatan' => null, 'lokasi_saat_ini' => null, 'jumlah_total' => 0, 'jumlah_rusak' => 0],
            ['kode_alat' => 'EL02', 'nama_alat' => 'Gerinda 4 Inch', 'kategori' => 'Elektrik', 'merk_catatan' => 'Bosch 710W', 'lokasi_saat_ini' => null, 'jumlah_total' => 5, 'jumlah_rusak' => 0],
            ['kode_alat' => 'EL03', 'nama_alat' => 'Gerinda Potong', 'kategori' => 'Elektrik', 'merk_catatan' => null, 'lokasi_saat_ini' => null, 'jumlah_total' => 0, 'jumlah_rusak' => 0],
            ['kode_alat' => 'MN01', 'nama_alat' => 'Kunci Pas Set', 'kategori' => 'Manual', 'merk_catatan' => null, 'lokasi_saat_ini' => null, 'jumlah_total' => 0, 'jumlah_rusak' => 0],
            ['kode_alat' => 'MN02', 'nama_alat' => 'Waterpass', 'kategori' => 'Manual', 'merk_catatan' => null, 'lokasi_saat_ini' => null, 'jumlah_total' => 0, 'jumlah_rusak' => 0],
            ['kode_alat' => 'UK01', 'nama_alat' => 'Tang Ampere', 'kategori' => 'Elektrik/Ukur', 'merk_catatan' => null, 'lokasi_saat_ini' => null, 'jumlah_total' => 0, 'jumlah_rusak' => 0],
            ['kode_alat' => 'EL04', 'nama_alat' => 'Mesin Las', 'kategori' => 'Elektrik', 'merk_catatan' => null, 'lokasi_saat_ini' => null, 'jumlah_total' => 0, 'jumlah_rusak' => 0],
            ['kode_alat' => 'EL05', 'nama_alat' => 'Kompressor', 'kategori' => 'Elektrik', 'merk_catatan' => null, 'lokasi_saat_ini' => null, 'jumlah_total' => 0, 'jumlah_rusak' => 0],
            ['kode_alat' => 'EL06', 'nama_alat' => 'Trafo Las', 'kategori' => 'Elektrik', 'merk_catatan' => null, 'lokasi_saat_ini' => null, 'jumlah_total' => 5, 'jumlah_rusak' => 0],
            ['kode_alat' => 'UK02', 'nama_alat' => 'Meteran Laser', 'kategori' => 'Ukur', 'merk_catatan' => null, 'lokasi_saat_ini' => null, 'jumlah_total' => 0, 'jumlah_rusak' => 0],
            ['kode_alat' => 'OP01', 'nama_alat' => 'Artco', 'kategori' => 'Operasional', 'merk_catatan' => null, 'lokasi_saat_ini' => null, 'jumlah_total' => 3, 'jumlah_rusak' => 0],
            ['kode_alat' => 'OP02', 'nama_alat' => 'Sekop', 'kategori' => 'Operasional', 'merk_catatan' => null, 'lokasi_saat_ini' => null, 'jumlah_total' => 2, 'jumlah_rusak' => 0],
            ['kode_alat' => 'OP03', 'nama_alat' => 'Tangga A', 'kategori' => 'Operasional', 'merk_catatan' => null, 'lokasi_saat_ini' => 'Gudang M4', 'jumlah_total' => 2, 'jumlah_rusak' => 0],
            ['kode_alat' => 'EL07', 'nama_alat' => 'Bor Baterai', 'kategori' => 'Elektrik', 'merk_catatan' => null, 'lokasi_saat_ini' => 'Gudang M4', 'jumlah_total' => 3, 'jumlah_rusak' => 0],
            ['kode_alat' => 'OP04', 'nama_alat' => 'Ember', 'kategori' => 'Operasional', 'merk_catatan' => null, 'lokasi_saat_ini' => 'Gudang M4', 'jumlah_total' => 10, 'jumlah_rusak' => 0],
            ['kode_alat' => 'OP05', 'nama_alat' => 'Cangkul + Gagang', 'kategori' => 'Operasional', 'merk_catatan' => null, 'lokasi_saat_ini' => 'Gudang M4', 'jumlah_total' => 3, 'jumlah_rusak' => 0],
            ['kode_alat' => 'EL08', 'nama_alat' => 'Mata Bor Besi', 'kategori' => 'Elektrik', 'merk_catatan' => null, 'lokasi_saat_ini' => 'Gudang M4', 'jumlah_total' => 1, 'jumlah_rusak' => 0],
            ['kode_alat' => 'OP06', 'nama_alat' => 'Mesin Pompa Air', 'kategori' => 'Operasional', 'merk_catatan' => null, 'lokasi_saat_ini' => 'Landbaw', 'jumlah_total' => 1, 'jumlah_rusak' => 0],
            ['kode_alat' => 'OP07', 'nama_alat' => 'Brush Gagang', 'kategori' => 'Operasional', 'merk_catatan' => null, 'lokasi_saat_ini' => 'Gudang M4', 'jumlah_total' => 2, 'jumlah_rusak' => 0],
            ['kode_alat' => 'OP08', 'nama_alat' => 'Kunci 14', 'kategori' => 'Operasional', 'merk_catatan' => null, 'lokasi_saat_ini' => 'Gudang M4', 'jumlah_total' => 1, 'jumlah_rusak' => 0],
            ['kode_alat' => 'OP09', 'nama_alat' => 'Body Harness', 'kategori' => 'Operasional', 'merk_catatan' => null, 'lokasi_saat_ini' => 'Gudang M4', 'jumlah_total' => 10, 'jumlah_rusak' => 0],
            ['kode_alat' => 'EL09', 'nama_alat' => 'Trafo Las', 'kategori' => 'Elektrik', 'merk_catatan' => null, 'lokasi_saat_ini' => 'Gudang M4', 'jumlah_total' => 0, 'jumlah_rusak' => 0],
            ['kode_alat' => 'MN03', 'nama_alat' => 'Kunci Inggris', 'kategori' => 'Manual', 'merk_catatan' => null, 'lokasi_saat_ini' => 'Gudang M4', 'jumlah_total' => 2, 'jumlah_rusak' => 0],
            ['kode_alat' => 'EL010', 'nama_alat' => 'Lampu', 'kategori' => 'Elektrik', 'merk_catatan' => null, 'lokasi_saat_ini' => 'Gudang M4', 'jumlah_total' => 1, 'jumlah_rusak' => 0],
            ['kode_alat' => 'OP010', 'nama_alat' => 'Kunci Ratchet', 'kategori' => 'Operasional', 'merk_catatan' => null, 'lokasi_saat_ini' => 'Gudang M4', 'jumlah_total' => 10, 'jumlah_rusak' => 0],
            ['kode_alat' => 'EL011', 'nama_alat' => 'Kabel Power Las', 'kategori' => 'Elektrik', 'merk_catatan' => null, 'lokasi_saat_ini' => 'Gudang M4', 'jumlah_total' => 1, 'jumlah_rusak' => 0],
            ['kode_alat' => 'OP011', 'nama_alat' => 'Stang Rivet', 'kategori' => 'Operasional', 'merk_catatan' => null, 'lokasi_saat_ini' => 'Gudang M4', 'jumlah_total' => 2, 'jumlah_rusak' => 0],
            ['kode_alat' => 'MN04', 'nama_alat' => 'Tali Tambang', 'kategori' => 'Manual', 'merk_catatan' => null, 'lokasi_saat_ini' => 'Gudang M4', 'jumlah_total' => 1, 'jumlah_rusak' => 0],
            ['kode_alat' => 'SC003', 'nama_alat' => 'Roda Scaffolding', 'kategori' => 'Operasional', 'merk_catatan' => null, 'lokasi_saat_ini' => 'Gudang M4', 'jumlah_total' => 20, 'jumlah_rusak' => 0],
            ['kode_alat' => 'MN05', 'nama_alat' => 'Kunci Inggris 8inch', 'kategori' => 'Manual', 'merk_catatan' => null, 'lokasi_saat_ini' => 'Gudang M4', 'jumlah_total' => 1, 'jumlah_rusak' => 0],
            ['kode_alat' => 'MN06', 'nama_alat' => 'Kunci Shock', 'kategori' => 'Manual', 'merk_catatan' => '13,14 Tidak ada', 'lokasi_saat_ini' => 'Gudang M4', 'jumlah_total' => 1, 'jumlah_rusak' => 0],
            ['kode_alat' => 'MN07', 'nama_alat' => 'Obeng Plus Merk Inco', 'kategori' => 'Manual', 'merk_catatan' => 'Merk Inco Kecil', 'lokasi_saat_ini' => 'Gudang M4', 'jumlah_total' => 1, 'jumlah_rusak' => 0],
            ['kode_alat' => 'MN08', 'nama_alat' => 'Obeng Plus Merk Inco', 'kategori' => 'Manual', 'merk_catatan' => 'Merk Inco Besar', 'lokasi_saat_ini' => 'Gudang M4', 'jumlah_total' => 1, 'jumlah_rusak' => 0],
            ['kode_alat' => 'MN09', 'nama_alat' => 'Obeng Min', 'kategori' => 'Manual', 'merk_catatan' => 'Inco', 'lokasi_saat_ini' => 'Gudang M4', 'jumlah_total' => 1, 'jumlah_rusak' => 0],
            ['kode_alat' => 'OP013', 'nama_alat' => 'Hole Saw 63mm', 'kategori' => 'Operasional', 'merk_catatan' => null, 'lokasi_saat_ini' => 'Gudang M4', 'jumlah_total' => 1, 'jumlah_rusak' => 0],
            ['kode_alat' => 'OP014', 'nama_alat' => 'Hole Saw 40mm', 'kategori' => 'Operasional', 'merk_catatan' => null, 'lokasi_saat_ini' => 'Gudang M4', 'jumlah_total' => 1, 'jumlah_rusak' => 0],
            ['kode_alat' => 'MN010', 'nama_alat' => 'Mata Obeng', 'kategori' => 'Manual', 'merk_catatan' => null, 'lokasi_saat_ini' => 'Gudang M4', 'jumlah_total' => 1, 'jumlah_rusak' => 0],
            ['kode_alat' => 'EL012', 'nama_alat' => 'Mesin Jack Shafer / Compresor Air', 'kategori' => 'Elektrik', 'merk_catatan' => null, 'lokasi_saat_ini' => 'Landbaw', 'jumlah_total' => 1, 'jumlah_rusak' => 0],
            ['kode_alat' => 'EL013', 'nama_alat' => 'Genset', 'kategori' => 'Elektrik', 'merk_catatan' => null, 'lokasi_saat_ini' => 'M3, Gudang M4', 'jumlah_total' => 2, 'jumlah_rusak' => 0],
            ['kode_alat' => 'MN011', 'nama_alat' => 'Stang Cing', 'kategori' => 'Manual', 'merk_catatan' => null, 'lokasi_saat_ini' => 'Landbaw', 'jumlah_total' => 2, 'jumlah_rusak' => 0],
            ['kode_alat' => 'EL014', 'nama_alat' => 'Lampu Lembur', 'kategori' => 'Elektrik', 'merk_catatan' => 'Makita', 'lokasi_saat_ini' => 'Gudang M4', 'jumlah_total' => 2, 'jumlah_rusak' => 0],
            ['kode_alat' => 'EL015', 'nama_alat' => 'Gerinda Baterai', 'kategori' => 'Elektrik', 'merk_catatan' => null, 'lokasi_saat_ini' => 'Gudang M4', 'jumlah_total' => 1, 'jumlah_rusak' => 0],
            ['kode_alat' => 'MN012', 'nama_alat' => 'Kunci L Set Tekiro', 'kategori' => 'Manual', 'merk_catatan' => null, 'lokasi_saat_ini' => 'Gudang M4', 'jumlah_total' => 1, 'jumlah_rusak' => 0],
            ['kode_alat' => 'MN013', 'nama_alat' => 'Bending Tubing 1/2', 'kategori' => 'Manual', 'merk_catatan' => null, 'lokasi_saat_ini' => 'Gudang M4', 'jumlah_total' => 1, 'jumlah_rusak' => 0],
            ['kode_alat' => 'DI001', 'nama_alat' => 'Genset Diesel', 'kategori' => 'Diesel', 'merk_catatan' => null, 'lokasi_saat_ini' => 'Landbaw', 'jumlah_total' => 1, 'jumlah_rusak' => 0],
            ['kode_alat' => 'TE001', 'nama_alat' => 'Phase Indicator', 'kategori' => null, 'merk_catatan' => null, 'lokasi_saat_ini' => 'Gudang M4', 'jumlah_total' => 1, 'jumlah_rusak' => 0],
            ['kode_alat' => 'TE002', 'nama_alat' => 'Earth Tester', 'kategori' => null, 'merk_catatan' => null, 'lokasi_saat_ini' => 'Gudang M4', 'jumlah_total' => 1, 'jumlah_rusak' => 0],
            ['kode_alat' => 'TE003', 'nama_alat' => 'Megger Test', 'kategori' => null, 'merk_catatan' => null, 'lokasi_saat_ini' => 'Gudang M4', 'jumlah_total' => 1, 'jumlah_rusak' => 0],
            ['kode_alat' => 'TE004', 'nama_alat' => 'Clamp Ampere', 'kategori' => null, 'merk_catatan' => null, 'lokasi_saat_ini' => 'Gudang M4', 'jumlah_total' => 1, 'jumlah_rusak' => 0],
            ['kode_alat' => 'TE005', 'nama_alat' => 'Multi Meter Tester', 'kategori' => null, 'merk_catatan' => null, 'lokasi_saat_ini' => 'Gudang M4', 'jumlah_total' => 1, 'jumlah_rusak' => 0],
            ['kode_alat' => 'TE006', 'nama_alat' => 'Gas Detector', 'kategori' => '(H2S, CO, CH4, 02)', 'merk_catatan' => null, 'lokasi_saat_ini' => 'Gudang M4', 'jumlah_total' => 1, 'jumlah_rusak' => 0],
            ['kode_alat' => 'EL015', 'nama_alat' => 'Jack Hammer Baterai', 'kategori' => null, 'merk_catatan' => null, 'lokasi_saat_ini' => 'M3', 'jumlah_total' => 1, 'jumlah_rusak' => 0],
            ['kode_alat' => 'MN014', 'nama_alat' => 'Selang Angin Kompressor', 'kategori' => null, 'merk_catatan' => null, 'lokasi_saat_ini' => 'M3', 'jumlah_total' => 1, 'jumlah_rusak' => 0],
            ['kode_alat' => 'MN015', 'nama_alat' => 'Regulator + Selang', 'kategori' => null, 'merk_catatan' => null, 'lokasi_saat_ini' => 'M3', 'jumlah_total' => 1, 'jumlah_rusak' => 0],
            ['kode_alat' => 'OP015', 'nama_alat' => 'Hole Saw 27mm', 'kategori' => null, 'merk_catatan' => null, 'lokasi_saat_ini' => 'Gudang M4', 'jumlah_total' => 1, 'jumlah_rusak' => 0],
            ['kode_alat' => 'OP016', 'nama_alat' => 'Chain Block 1 Ton', 'kategori' => null, 'merk_catatan' => null, 'lokasi_saat_ini' => 'Gudang M4', 'jumlah_total' => 1, 'jumlah_rusak' => 0],
            ['kode_alat' => 'OP017', 'nama_alat' => 'Kunci Inggris 24 Inch', 'kategori' => null, 'merk_catatan' => 'Tekiro', 'lokasi_saat_ini' => 'Gudang M4', 'jumlah_total' => 1, 'jumlah_rusak' => 0],
            ['kode_alat' => 'OP018', 'nama_alat' => 'Hole Saw 22mm', 'kategori' => null, 'merk_catatan' => null, 'lokasi_saat_ini' => 'Gudang M4', 'jumlah_total' => 1, 'jumlah_rusak' => 0],
            ['kode_alat' => 'OP019', 'nama_alat' => 'Kunci Inggris 12 Inch', 'kategori' => null, 'merk_catatan' => 'Tekira', 'lokasi_saat_ini' => 'Gudang M4', 'jumlah_total' => 2, 'jumlah_rusak' => 0],
            ['kode_alat' => 'BA001', 'nama_alat' => 'Lamput Lembur Baterai', 'kategori' => 'Baterai', 'merk_catatan' => null, 'lokasi_saat_ini' => 'Gudang M4', 'jumlah_total' => 5, 'jumlah_rusak' => 0],
            ['kode_alat' => 'EL016', 'nama_alat' => 'Hot Gun', 'kategori' => 'Baterai', 'merk_catatan' => null, 'lokasi_saat_ini' => 'Gudang M4', 'jumlah_total' => 1, 'jumlah_rusak' => 0],
            ['kode_alat' => 'EL017', 'nama_alat' => 'Bor Listrik', 'kategori' => null, 'merk_catatan' => null, 'lokasi_saat_ini' => 'Gudang M4', 'jumlah_total' => 5, 'jumlah_rusak' => 0],
            ['kode_alat' => 'SC001', 'nama_alat' => 'Yoyo 20meter', 'kategori' => 'Scaffolding Equipment', 'merk_catatan' => null, 'lokasi_saat_ini' => 'Gudang M4', 'jumlah_total' => 4, 'jumlah_rusak' => 0],
            ['kode_alat' => 'SC002', 'nama_alat' => 'Ratchet 17-19', 'kategori' => 'Scaffolding Equipment', 'merk_catatan' => null, 'lokasi_saat_ini' => 'Gudang M4', 'jumlah_total' => 1, 'jumlah_rusak' => 0],
            ['kode_alat' => 'MN016', 'nama_alat' => 'Bending Conduit 3/4', 'kategori' => null, 'merk_catatan' => null, 'lokasi_saat_ini' => 'Gudang M4', 'jumlah_total' => 2, 'jumlah_rusak' => 0],
            ['kode_alat' => 'MN017', 'nama_alat' => 'Bending Conduit PVC', 'kategori' => null, 'merk_catatan' => null, 'lokasi_saat_ini' => 'Gudang M4', 'jumlah_total' => 2, 'jumlah_rusak' => 0],
            ['kode_alat' => 'MN018', 'nama_alat' => 'Obeng', 'kategori' => null, 'merk_catatan' => null, 'lokasi_saat_ini' => 'Gudang M4', 'jumlah_total' => 1, 'jumlah_rusak' => 0],
            ['kode_alat' => 'EL018', 'nama_alat' => 'Lampu Led Lembur', 'kategori' => 'IP66 300 Watt', 'merk_catatan' => null, 'lokasi_saat_ini' => 'Gudang M4', 'jumlah_total' => 15, 'jumlah_rusak' => 0],
        ];

        foreach ($data as $row) {
            Alat::updateOrCreate(
                ['kode_alat' => $row['kode_alat']],
                $row
            );
        }
    }
}
