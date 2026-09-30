<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Satuan;
use App\Models\KategoriAlat;
use App\Models\KategoriBarang;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Account Admin
        User::updateOrCreate(
            ['email' => 'michaelrediks8@gmail.com'],
            [
                'name' => 'Michael',
                'password' => Hash::make('Daydreaming123'),
            ]
        );

        // 2. Master Data Satuan
        $satuans = [
            ['kode_satuan' => 'PCS', 'nama_satuan' => 'Pcs', 'keterangan' => 'Satuan Biji'],
            ['kode_satuan' => 'BOX', 'nama_satuan' => 'Box', 'keterangan' => 'Satuan Kotak'],
            ['kode_satuan' => 'MTR', 'nama_satuan' => 'Meter', 'keterangan' => 'Satuan Panjang'],
            ['kode_satuan' => 'ROL', 'nama_satuan' => 'Roll', 'keterangan' => 'Satuan Gulungan'],
            ['kode_satuan' => 'BKS', 'nama_satuan' => 'Bungkus', 'keterangan' => 'Satuan Bungkus'],
        ];
        foreach ($satuans as $satuan) {
            Satuan::updateOrCreate(['nama_satuan' => $satuan['nama_satuan']], $satuan);
        }

        // 3. Master Data Kategori Alat
        $kategoriAlats = [
            ['kode_kategori' => 'WLD', 'nama_kategori' => 'Welding & Las'],
            ['kode_kategori' => 'ELC', 'nama_kategori' => 'Electrical'],
            ['kode_kategori' => 'SFT', 'nama_kategori' => 'Safety & APD'],
            ['kode_kategori' => 'PWR', 'nama_kategori' => 'Power Tools'],
        ];
        foreach ($kategoriAlats as $kategori) {
            KategoriAlat::updateOrCreate(['nama_kategori' => $kategori['nama_kategori']], $kategori);
        }

        // 4. Master Data Kategori Barang
        $kategoriBarangs = [
            ['kode_kategori' => 'CSM', 'nama_kategori' => 'Consumables', 'keterangan' => 'Barang habis pakai'],
            ['kode_kategori' => 'MTR', 'nama_kategori' => 'Material Konstruksi', 'keterangan' => 'Besi, plat, pipa, cat, baut'],
            ['kode_kategori' => 'GAS', 'nama_kategori' => 'Gas Industri', 'keterangan' => 'Oxygen, Argon, Acetylene, CO2'],
            ['kode_kategori' => 'SPR', 'nama_kategori' => 'Sparepart & Komponen', 'keterangan' => 'Suku cadang mesin/alat'],
        ];
        foreach ($kategoriBarangs as $katBarang) {
            KategoriBarang::updateOrCreate(['nama_kategori' => $katBarang['nama_kategori']], $katBarang);
        }

        // 5. Seeder Eksternal / Seeder dari CSV
        $this->call([
            MasterDataSeeder::class, 
            BarangSeeder::class,     
            AlatSeeder::class,
            VendorSeeder::class,
            ImportCsvSeeder::class, 
        ]);
    }
}