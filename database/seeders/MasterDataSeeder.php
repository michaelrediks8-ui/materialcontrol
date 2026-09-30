<?php

namespace Database\Seeders;

use App\Models\Gudang;
use App\Models\JenisGas;
use App\Models\Project;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        // --- Gudang ---
        foreach (['Gudang M4', 'Gudang M3', 'Gudang Landbaw'] as $nama) {
            Gudang::firstOrCreate(['nama_gudang' => $nama]);
        }

        // --- Jenis Gas (tabel master, bisa nambah sendiri lewat Filament nanti) ---
        foreach (['Acetylene', 'Oksigen'] as $nama) {
            JenisGas::firstOrCreate(['nama_gas' => $nama]);
        }

        // --- Vendor (diambil dari data histori Agustus-September, sudah dirapikan) ---
        // Catatan: banyak nama vendor histori punya variasi ejaan (CV. Verena Technic /
        // Cv. Verena / Cv. Verana Technic, dst) — di sini saya ambil satu nama baku per vendor.
        // Kalau perlu, kita bisa buat "vendor_alias" nanti seperti barang_alias.
        $vendors = [
            'Eka Surya', 'Pt. Elektrisindo', 'Pt. Elektriksindo Medan', 'CV. Verena Technic',
            'Pt. Control Systems Arena Para Nusa', 'Panglong Mangke', 'PT. Arkananta Hazardtek Integratama',
            'Canada Plastik', 'Pt. Sirijaya Persada', 'Unilever', 'Pt. Viriya Persada', 'Samator',
        ];
        foreach ($vendors as $nama) {
            Vendor::firstOrCreate(['nama_vendor' => $nama]);
        }

        // --- Project (diambil dari data histori — silakan tambah/edit lewat Filament) ---
        $projects = [
            'ISBL M4', 'Admin Office (C&S, Archi, MEP, M&E) M4', 'Upgrade Musholla & Toilet Gate F',
            'MCC', 'Workshop Landbaw', 'Modifikasi Keypad Access Control MCC Betaine', 'WWTP', 'FAL',
            'Aplan', 'Biomas', 'PKM', 'Booster Pump Fall', 'Reheat 360 Piping Installation',
        ];
        foreach ($projects as $nama) {
            Project::firstOrCreate(['nama_project' => $nama]);
        }
    }
}
