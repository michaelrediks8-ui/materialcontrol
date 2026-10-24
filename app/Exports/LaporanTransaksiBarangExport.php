<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;


class LaporanTransaksiBarangExport
{
    /** [judul kolom, field, lebar] */
    private const COLUMNS = [
        ['No',            null,               6],
        ['Tanggal',       'tanggal',          13],
        ['Nama Barang',   'nama_barang',      38],
        ['Qty',           'qty',              10],
        ['Satuan',        'satuan',           10],
        ['Status',        'status_barang',    12],
        ['Asal',          'asal',             24],
        ['Nama Req',      'nama_req',         18],
        ['Nama Terima',   'nama_terima',      18],
        ['Ke Project',    'ke_project',       26],
        ['No PO',         'no_po',            18],
        ['Area',          'area',             18],
        ['No Surat Jalan', 'no_surat_jalan',  20],
    ];

    public static function download(Collection $rows, string $periode, string $fileName): StreamedResponse
    {
        $spreadsheet = self::build($rows, $periode);

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public static function build(Collection $rows, string $periode): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Transaksi');

        $jumlahKolom = count(self::COLUMNS);
        $lastCol = Coordinate::stringFromColumnIndex($jumlahKolom);
        $headerRow = 4;

        // Judul
        $sheet->setCellValue('A1', 'LAPORAN TRANSAKSI BARANG');
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(15);

        $sheet->setCellValue('A2', 'Periode: ' . $periode);
        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->setCellValue('A3', 'Dicetak: ' . now()->format('d/m/Y H:i') . ' · ' . $rows->count() . ' baris');
        $sheet->mergeCells("A3:{$lastCol}3");
        $sheet->getStyle('A2:A3')->getFont()->setItalic(true)->getColor()->setRGB('595959');

        // Header kolom
        foreach (self::COLUMNS as $i => [$judul, , $lebar]) {
            $col = Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue("{$col}{$headerRow}", $judul);
            $sheet->getColumnDimension($col)->setWidth($lebar);
        }

        $headerRange = "A{$headerRow}:{$lastCol}{$headerRow}";
        $sheet->getStyle($headerRange)->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E78']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($headerRow)->setRowHeight(22);

        // Data
        $row = $headerRow + 1;
        $no = 1;

        foreach ($rows as $r) {
            foreach (self::COLUMNS as $i => [, $field]) {
                $col = Coordinate::stringFromColumnIndex($i + 1);
                $cell = "{$col}{$row}";

                if ($field === null) {
                    $sheet->setCellValue($cell, $no);
                    continue;
                }

                $value = $r->{$field};

                if ($field === 'tanggal') {
                    if ($value) {
                        $sheet->setCellValue($cell, ExcelDate::PHPToExcel(\Carbon\Carbon::parse($value)));
                        $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                    }
                    continue;
                }

                if ($field === 'qty') {
                    $angka = is_numeric($value) ? $value + 0 : $value;
                    $sheet->setCellValue($cell, $angka);
                    $sheet->getStyle($cell)->getNumberFormat()
                        ->setFormatCode(is_float($angka) && floor($angka) != $angka ? '#,##0.00' : '#,##0');
                    continue;
                }

                // Paksa teks (no surat jalan / no PO bisa berupa angka berawalan 0)
                $sheet->setCellValueExplicit($cell, (string) ($value ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }

            // Warna status
            $status = (string) $r->status_barang;
            $warna = str_contains($status, 'MASUK') ? 'E2EFDA' : (str_contains($status, 'KELUAR') ? 'FCE4D6' : null);
            if ($warna) {
                $sheet->getStyle("F{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($warna);
            }

            $row++;
            $no++;
        }

        $lastDataRow = max($row - 1, $headerRow);

        // Format tabel
        $tableRange = "A{$headerRow}:{$lastCol}{$lastDataRow}";
        $sheet->getStyle($tableRange)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('BFBFBF');

        if ($lastDataRow > $headerRow) {
            $sheet->getStyle("A" . ($headerRow + 1) . ":A{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B" . ($headerRow + 1) . ":B{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D" . ($headerRow + 1) . ":F{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $sheet->freezePane('A' . ($headerRow + 1));
        $sheet->setAutoFilter($headerRange);

        // Siap cetak: landscape, muat 1 halaman lebar, header berulang
        $sheet->getPageSetup()
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        $sheet->getSheetView()->setZoomScale(90);
        $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd($headerRow, $headerRow);

        return $spreadsheet;
    }
}