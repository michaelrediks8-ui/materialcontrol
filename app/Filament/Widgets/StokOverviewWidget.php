<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Barang;
use App\Models\StokBarang;
use App\Models\Gudang;
use App\Models\TransaksiBarang;

class StokOverviewWidget extends BaseWidget // <--- Ubah di sini agar sama dengan nama file
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        // Hitung data
        $totalBarang = Barang::count();
        $barangMenipis = StokBarang::where('stok', '>', 0)->where('stok', '<=', 10)->count();
        $barangHabis = StokBarang::where('stok', '<=', 0)->count();
        $transaksiHariIni = TransaksiBarang::whereDate('created_at', now())->count();
        $totalGudang = Gudang::count();

        return [
            Stat::make('Total Jenis Barang', number_format($totalBarang, 0, ',', '.'))
                ->description('Master material terdaftar')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary'),

            Stat::make('Barang Menipis', $barangMenipis)
                ->description('Stok ≤ 10 unit')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning'),

            Stat::make('Barang Habis', $barangHabis)
                ->description('Stok 0 / Kosong')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make('Transaksi Hari Ini', $transaksiHariIni)
                ->description('Masuk + Keluar + Transfer')
                ->descriptionIcon('heroicon-m-arrows-right-left')
                ->color('success'),

            Stat::make('Gudang Aktif', $totalGudang)
                ->description('Lokasi penyimpan terdaftar')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('info'),

            Stat::make('Status Sistem', 'Normal')
                ->description('Database terhubung')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('gray'),
        ];
    }
}