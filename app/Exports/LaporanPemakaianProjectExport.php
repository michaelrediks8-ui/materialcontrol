<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class LaporanPemakaianProjectExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
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
                $row['project'],
                $row['kode_barang'],
                $row['nama_barang'],
                $row['total_qty'],
            ];
        })->toArray();
    }

    public function headings(): array
    {
        return ['Project', 'Kode Barang', 'Nama Barang', 'Total Qty Terpakai'];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:D1')->applyFromArray([
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
        return ['A' => 35, 'B' => 15, 'C' => 30, 'D' => 20];
    }
}