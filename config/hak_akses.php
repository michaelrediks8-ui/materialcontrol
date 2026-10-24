<?php

use App\Filament\Resources\BarangKeluarResource;
use App\Filament\Resources\BarangMasukResource;
use App\Filament\Resources\MutasiAlatResource;
use App\Filament\Resources\PeminjamanAlatResource;
use App\Filament\Resources\TransferGudangResource;

/*
|--------------------------------------------------------------------------
| Aturan Hak Akses per Resource (SATU TEMPAT)
|--------------------------------------------------------------------------
|
| 'izin'   : izin user yang dibutuhkan untuk membuat/mengedit data di Resource ini.
|            - 'input'  => user harus punya izin "Boleh Input Data"
|            - 'keluar' => user harus punya izin "Boleh Mengeluarkan Barang / Alat"
|            - array    => izin bergantung pada nilai sebuah kolom, contoh:
|                          ['dari_kolom' => 'jenis_mutasi',
|                           'peta'      => ['transfer' => 'keluar'],
|                           'default'   => 'input']
|
| 'gudang' : cara menentukan gudang pemilik sebuah data.
|            - ['mode' => 'asal_tujuan']
|                Pemilik = gudang asal (asal_type = gudang), kalau tidak ada
|                maka gudang tujuan (tujuan_type = gudang).
|            - ['mode' => 'kolom', 'kolom' => 'gudang_id']
|                Pemilik = isi kolom tersebut.
|
| Izin "Hapus" (can_delete) berlaku untuk semua Resource di bawah.
| Admin selalu lolos semua aturan.
|
| Modul baru cukup ditambahkan satu baris di sini, lalu pasang
| `use MembatasiGudang;` di Resource-nya.
|
*/

return [

    'resources' => [

        BarangMasukResource::class => [
            'izin' => 'input',
            'gudang' => ['mode' => 'asal_tujuan'],
        ],

        BarangKeluarResource::class => [
            'izin' => 'keluar',
            'gudang' => ['mode' => 'asal_tujuan'],
        ],

        TransferGudangResource::class => [
            'izin' => 'keluar',
            'gudang' => ['mode' => 'asal_tujuan'],
        ],

        MutasiAlatResource::class => [
            'izin' => [
                'dari_kolom' => 'jenis_mutasi',
                'peta' => ['transfer' => 'keluar'],
                'default' => 'input',
            ],
            'gudang' => ['mode' => 'asal_tujuan'],
        ],

        PeminjamanAlatResource::class => [
            'izin' => 'keluar',
            'gudang' => ['mode' => 'kolom', 'kolom' => 'gudang_id'],
        ],

    ],

];
