<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Exports\LaporanMutasiExport;
use Maatwebsite\Excel\Facades\Excel;

class LaporanMutasiExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    protected array $rows;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function array(): array
    {
        return collect($this->rows)->map(function ($row) {
            return [
                \Carbon\Carbon::parse($row['tanggal'])->format('d/m/Y H:i'),
                $row['no_transaksi'],
                match ($row['jenis_transaksi']) {
                    'masuk' => 'Barang Masuk',
                    'keluar' => 'Barang Keluar',
                    'transfer' => 'Transfer Gudang',
                    default => $row['jenis_transaksi'],
                },
                $row['kode_barang'],
                $row['nama_barang'],
                $row['asal'],
                $row['tujuan'],
                $row['qty'],
            ];
        })->toArray();
    }

    public function headings(): array
    {
        return [
            'Tanggal', 'No. Transaksi', 'Jenis Transaksi', 'Kode Barang',
            'Nama Barang', 'Asal', 'Tujuan', 'Qty',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1F2937'],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18, 'B' => 20, 'C' => 16, 'D' => 14,
            'E' => 30, 'F' => 20, 'G' => 20, 'H' => 12,
        ];
    }
}