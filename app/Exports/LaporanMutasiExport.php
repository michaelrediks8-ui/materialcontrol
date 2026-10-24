<?php

namespace App\Exports;

use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LaporanMutasiExport implements FromArray, ShouldAutoSize, WithStyles, WithDrawings
{
    private const KOLOM_AKHIR = 'K';

    private int $barisHeaderMutasi = 0;
    private int $barisAkhirMutasi = 0;
    private int $barisJudulRingkasan = 0;
    private int $barisHeaderRingkasan = 0;
    private int $barisAkhirRingkasan = 0;

    public function __construct(
        private array $rows,
        private array $ringkasan = [],
        private string $periode = '',
        private string $gudang = '',
    ) {
    }

    /** Baris kosong harus berisi satu sel agar tidak dibuang oleh Excel export. */
    private const KOSONG = [''];

    public function array(): array
    {
        $out = [];

        // ===== KOP SURAT (baris 1-5): logo di kolom A, teks di tengah =====
        $out[] = ['', 'SARANA TEKNIK PERKASA'];
        $out[] = ['', 'Kantor Pusat : Jln. Pondok Cabe 3 Ilir'];
        $out[] = ['', 'Pamulang, Tangerang Selatan, Banten 15418'];
        $out[] = ['', 'Telp : 0622-7296905 / 081317060060'];
        $out[] = ['', 'Email : sarana.stp@gmail.com'];
        $out[] = self::KOSONG;

        // ===== JUDUL LAPORAN (baris 7-8) =====
        $out[] = ['LAPORAN MUTASI BARANG'];
        $out[] = ['Periode: ' . ($this->periode ?: '-') . '   |   Gudang: ' . ($this->gudang ?: '-')];
        $out[] = self::KOSONG;

        // ===== TABEL MUTASI =====
        $out[] = ['Tanggal & Jam', 'No. Transaksi', 'Jenis', 'Gudang', 'Kode', 'Barang', 'Satuan', 'Keterangan', 'Masuk', 'Keluar', 'Saldo'];
        $this->barisHeaderMutasi = count($out);

        foreach ($this->rows as $r) {
            $out[] = [
                $r['tanggal'] ? Carbon::parse($r['tanggal'])->format('d/m/Y H:i') : '-',
                $r['no_transaksi'],
                ucfirst($r['jenis_transaksi'] ?? ''),
                $r['gudang'] ?? '',
                $r['kode_barang'],
                $r['nama_barang'],
                $r['satuan'] ?? '',
                $r['keterangan'] ?? '',
                $r['masuk'] ?: '',
                $r['keluar'] ?: '',
                $r['saldo'] ?? '',
            ];
        }
        $this->barisAkhirMutasi = count($out);

        // ===== RINGKASAN PER BARANG =====
        $out[] = self::KOSONG;
        $out[] = ['RINGKASAN STOK PER BARANG (dari lalu lintas stok)'];
        $this->barisJudulRingkasan = count($out);

        $out[] = ['Kode', 'Barang', 'Satuan', 'Gudang', '', '', '', 'Saldo Awal', 'Total Masuk', 'Total Keluar', 'Sisa Stok'];
        $this->barisHeaderRingkasan = count($out);

        foreach ($this->ringkasan as $g) {
            $out[] = [
                $g['kode_barang'], $g['nama_barang'], $g['satuan'], $g['gudang'] ?? '', '', '', '',
                $g['saldo_awal'], $g['masuk'], $g['keluar'], $g['sisa'],
            ];
        }
        $this->barisAkhirRingkasan = count($out);

        return $out;
    }

    /** Logo di pojok kiri kop (sama seperti PDF). */
    public function drawings()
    {
        foreach (['logo-stp.png', 'logo-stp.PNG', 'logo-stp.jpg'] as $nama) {
            $path = public_path('images/' . $nama);

            if (file_exists($path)) {
                $logo = new Drawing();
                $logo->setName('Logo STP');
                $logo->setPath($path);
                $logo->setHeight(70);
                $logo->setCoordinates('A1');
                $logo->setOffsetX(5);
                $logo->setOffsetY(4);

                return $logo;
            }
        }

        return [];
    }

    public function styles(Worksheet $sheet)
    {
        $k = self::KOLOM_AKHIR;

        // ----- Kop surat -----
        foreach ([1, 2, 3, 4, 5] as $baris) {
            $sheet->mergeCells("B{$baris}:{$k}{$baris}");
            $sheet->getRowDimension($baris)->setRowHeight($baris === 1 ? 24 : 15);
        }
        $sheet->getStyle("B1:{$k}5")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('B1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('B2:B4')->getFont()->setSize(10);
        $sheet->getStyle('B5')->getFont()->setSize(10)->setItalic(true)->getColor()->setRGB('1A0DAB');
        $sheet->getStyle("A5:{$k}5")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_DOUBLE);

        // ----- Judul laporan -----
        foreach ([7, 8] as $baris) {
            $sheet->mergeCells("A{$baris}:{$k}{$baris}");
            $sheet->getStyle("A{$baris}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
        $sheet->getStyle('A7')->getFont()->setBold(true)->setSize(14)->setUnderline(true);
        $sheet->getStyle('A8')->getFont()->setBold(true)->setSize(10);

        // ----- Tabel -----
        $garis = ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]];
        $hijau = [
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '92D050']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];

        $this->tata($sheet, $this->barisHeaderMutasi, $this->barisAkhirMutasi, $garis, $hijau, 'I');

        $sheet->getStyle("A{$this->barisJudulRingkasan}")->getFont()->setBold(true)->setSize(12);
        $this->tata($sheet, $this->barisHeaderRingkasan, $this->barisAkhirRingkasan, $garis, $hijau, 'H');

        return [];
    }

    private function tata(Worksheet $sheet, int $header, int $akhir, array $garis, array $hijau, string $angkaDari): void
    {
        $k = self::KOLOM_AKHIR;

        if ($header < 1) {
            return;
        }

        $sheet->getStyle("A{$header}:{$k}{$akhir}")->applyFromArray($garis);
        $sheet->getStyle("A{$header}:{$k}{$header}")->applyFromArray($hijau);

        if ($akhir > $header) {
            $rentang = "{$angkaDari}" . ($header + 1) . ":{$k}{$akhir}";
            $sheet->getStyle($rentang)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle($rentang)->getNumberFormat()->setFormatCode('#,##0.##');
        }
    }
}