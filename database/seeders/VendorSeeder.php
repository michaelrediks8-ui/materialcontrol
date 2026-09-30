<?php

namespace Database\Seeders;

use App\Models\Vendor;
use Illuminate\Database\Seeder;

class VendorSeeder extends Seeder
{
    /**
     * Import vendor dari Rekap PO (Excel). Dijalankan sekali saat setup awal —
     * untuk nambah vendor baru setelah ini, pakai halaman Filament (Vendor
     * Resource), bukan edit seeder ini lagi.
     *
     * Aman dijalankan berkali-kali: nama yang sudah ada (case-insensitive)
     * otomatis dilewati, tidak dobel.
     */
    public function run(): void
    {
        $namaVendor = [
            'Agus Syahputra',
            'Andi Ricky Pramono',
            'Asnawi',
            'Cahaya Survey',
            'CV. Rajawali Samudra',
            'CV. Sentral Glassindo',
            'CV. Sigombo Berjaya',
            'CV. Surya Engineering',
            'CV. Verena Technic',
            'Dwi Maju Abadi',
            'Ferry Erikson Manullang',
            'Gaskindo Sentosa',
            'Informa',
            'Ion Wallpaper',
            'JZ Technology',
            'Kave CCTV & Security System',
            'Lindawati Tarigan',
            'M. Kemaludin',
            'Medan Pipe Fittings',
            'Medan Raya Baru',
            'Otto Sundoro Damanik',
            'Pak Rahmat',
            'Pak Santoso',
            'Pak Sofyan',
            'Panen Raya',
            'Panglong Bikem',
            'Panglong Simpang Jaya',
            'Pot Bunga Medan Xaquila',
            'PT. Airex Maju Indonesia',
            'PT. Andalas Citra Elektrindo',
            'PT. Arbrion Asia',
            'PT. Arkananta Hazardtex Integratama',
            'PT. Arkananta Hazardtex Integretama',
            'PT. Ascotama Cipta Era',
            'PT. Awi Jaya Indotama',
            'PT. Ayu Septa Perdana',
            'PT. Berca Niaga Medika',
            'PT. Bilah Baja Makmur Abadi',
            'PT. Bougenville Solusi Mandiri',
            'PT. Cakrabuana Caruban Nagari',
            'PT. Dasa Prakarsa Sejati',
            'PT. Dunia Pemadam Indonesia',
            'PT. Elektrisindo',
            'PT. Era Niaga Indonesia',
            'PT. Era Sentosa Abadi',
            'PT. Fanos Asia',
            'PT. Fisindo Instrumen Sukses',
            'PT. Flow Solution Indonesia',
            'PT. Fribeska Cipta Sejahtera',
            'PT. Gaskindo Sentosa',
            'PT. GLENZ Security Indonesia',
            'PT. GNS Niaga',
            'PT. Greatways Engineering Service',
            'PT. Hagamas Kimia Raya',
            'PT. Hervitama Indonesia',
            'PT. Hikari Penta Sarana',
            'PT. Indotara Persada',
            'PT. Indraprastha Teknika Utama',
            'PT. Japnas Kelangitan Tangguh',
            'PT. JJ-LAPP',
            'PT. Karya Adya Perkasa',
            'PT. Listrik Kita Indonesia',
            'PT. Mechtron Mastevi Indonesia',
            'PT. Metal Andalan Sukses',
            'PT. Mitra Kharisma Kontruksi Prima',
            'PT. Multi Daya Mitra',
            'PT. Multi Jasa Lestarindo',
            'PT. Nawasena Arial Abadi',
            'PT. Nusa Prima Architect',
            'PT. Panca Jiwa Manunggal',
            'PT. Panca Jiwa Manurung',
            'PT. Prima Mitra Elektrindo',
            'PT. Prokalindo Global Presisi',
            'PT. Quality Engineering Services',
            'PT. Samator Gas Industri',
            'PT. Security Solution Nusantara',
            'PT. Simentari Abdhi Bina',
            'PT. Sinar Surya Sentana',
            'PT. Solutec Daya Mandiri',
            'PT. Sucofindo',
            'PT. Sukses Maju Bangunan',
            'PT. Surya Maju Gemilang',
            'PT. Surya Mentari Indah',
            'PT. Surya Trias Gemilang',
            'PT. Tama Elevator Indonesia',
            'PT. Trikomindo Karunia Utama',
            'PT. Trisapta Eka Maju',
            'PT. Viriya Persada Netindo',
            'PT. Visiotek Global Indonesia',
            'PT. Wiyasa Jaya Perkasa',
            'Scaffolding Markus Girsang',
            'Semangat Teknik Medan',
            'Sentral Glassindo',
            'Sinar Deli Perkasa',
            'Studio Kamar Sempit',
            'Sunn Jaya',
            'Surya Darma Putra',
            'Swara Agung Electronic',
            'Toko Batu Bata Kisaran',
            'Toko Samudra Mandiri',
            'Toko Sumatera Jaya',
            'Toko Waja',
            'UD. Mega Menteng Baja',
            'UD. Sri Rezeki',
            'Wahyu AC',
        ];

        foreach ($namaVendor as $nama) {
            $sudahAda = Vendor::whereRaw('LOWER(nama_vendor) = ?', [strtolower($nama)])->exists();
            if ($sudahAda) {
                continue;
            }

            Vendor::create([
                'kode_vendor' => Vendor::generateNextCode(),
                'nama_vendor' => $nama,
            ]);
        }
    }
}
