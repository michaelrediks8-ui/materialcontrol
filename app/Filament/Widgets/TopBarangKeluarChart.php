<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TopBarangKeluarChart extends ChartWidget
{
    protected static ?string $heading = 'Top 5 Barang Paling Banyak Keluar (30 Hari Terakhir)';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $rows = DB::table('transaksi_barang_details as td')
            ->join('transaksi_barangs as t', 't.id', '=', 'td.transaksi_barang_id')
            ->join('barangs as b', 'b.id', '=', 'td.barang_id')
            ->where('t.asal_type', 'gudang')
            ->where('t.tanggal_transaksi', '>=', now()->subDays(30))
            ->select('b.nama_barang', DB::raw('SUM(td.qty) as total_qty'))
            ->groupBy('b.id', 'b.nama_barang')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Qty Keluar',
                    'data' => $rows->pluck('total_qty')->toArray(),
                    'backgroundColor' => '#3b82f6',
                ],
            ],
            'labels' => $rows->pluck('nama_barang')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}