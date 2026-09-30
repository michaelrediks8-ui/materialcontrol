<?php

namespace App\Filament\Widgets;

use App\Models\TransaksiBarang;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class TrenTransaksiChart extends ChartWidget
{
    protected static ?string $heading = 'Tren Transaksi 7 Hari Terakhir';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $labels = [];
        $dataMasuk = [];
        $dataKeluar = [];

        for ($i = 6; $i >= 0; $i--) {
            $tanggal = Carbon::today()->subDays($i);
            $labels[] = $tanggal->format('d M');

            $dataMasuk[] = TransaksiBarang::whereDate('tanggal_transaksi', $tanggal)
                ->where('jenis_transaksi', 'masuk')
                ->count();

            $dataKeluar[] = TransaksiBarang::whereDate('tanggal_transaksi', $tanggal)
                ->where('jenis_transaksi', 'keluar')
                ->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Barang Masuk',
                    'data' => $dataMasuk,
                    'borderColor' => '#22c55e',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                ],
                [
                    'label' => 'Barang Keluar',
                    'data' => $dataKeluar,
                    'borderColor' => '#ef4444',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}