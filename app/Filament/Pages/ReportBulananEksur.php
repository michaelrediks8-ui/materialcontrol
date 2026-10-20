<?php

namespace App\Filament\Pages;

use App\Models\TransaksiBarang;
use App\Models\Vendor;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ReportBulananEksur extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationGroup = 'Laporan';
    protected static ?string $navigationLabel = 'Laporan Eka Surya';
    protected static ?string $title = 'Laporan Bulanan Eka Surya';
    protected static ?int $navigationSort = 9;

    protected static string $view = 'filament.pages.report-bulanan-eksur';

    public ?array $data = [];

    /** Preview baru dimuat setelah tombol "Tampilkan Data" ditekan. */
    public bool $sudahTampil = false;

    private const BATAS_PREVIEW = 300;

    public function mount(): void
    {
        // Default filter: Tanggal 15 bulan lalu s/d hari ini
        $vendorEkaSurya = Vendor::where('nama_vendor', 'like', '%Eka Surya%')->first();

        $this->form->fill([
            'vendor_id' => $vendorEkaSurya ? $vendorEkaSurya->id : '__all__',
            // Default per hari: hari ini saja, supaya ringan.
            'dari_tanggal' => now()->format('Y-m-d'),
            'sampai_tanggal' => now()->format('Y-m-d'),
        ]);
    }

    public function tampilkan(): void
    {
        $this->sudahTampil = true;
    }

    /** Filter berubah -> preview disembunyikan, harus ditampilkan ulang. */
    public function updatedData(): void
    {
        $this->sudahTampil = false;
    }

    public function getPreviewRows(): array
    {
        $rows = $this->getRows();

        return [
            'total' => count($rows),
            'rows' => array_slice($rows, 0, self::BATAS_PREVIEW),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('vendor_id')
                    ->label('Pilih Vendor')
                    ->options(function () {
                        return ['__all__' => 'Semua Vendor'] + Vendor::query()
                            ->pluck('nama_vendor', 'id')
                            ->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->live(),

                DatePicker::make('dari_tanggal')
                    ->label('Dari Tanggal')
                    ->default(now())
                    ->live(),

                DatePicker::make('sampai_tanggal')
                    ->label('Sampai Tanggal')
                    ->default(now())
                    ->live(),
            ])
            ->statePath('data')
            ->columns(3);
    }

    public function getRows(): array
    {
        $vendorId = $this->data['vendor_id'] ?? '__all__';
        $dariTanggal = $this->data['dari_tanggal'] ?? null;
        $sampaiTanggal = $this->data['sampai_tanggal'] ?? null;

        $query = TransaksiBarang::with([
        'details' => function ($q) {
            $q->orderBy('id', 'asc'); // 💡 Ini yang akan mengurutkan rincian barang dari atas ke bawah sesuai inputan
        }, 
        'details.barang', 
        'details.satuan', 
        'purchaseOrder.project',
        'details.project',
        'purchaseOrder.project'
    ])
    ->where('jenis_transaksi', 'masuk');

        if ($vendorId && $vendorId !== '__all__') {
            $query->where('asal_type', 'vendor')->where('asal_id', $vendorId);
        }

        if ($dariTanggal) {
            $query->whereDate('tanggal_transaksi', '>=', $dariTanggal);
        }
        if ($sampaiTanggal) {
            $query->whereDate('tanggal_transaksi', '<=', $sampaiTanggal);
        }

        $transaksis = $query->orderBy('tanggal_transaksi', 'asc')->orderBy('id', 'asc')->get();

        $rows = [];
        $no = 1;

        foreach ($transaksis as $transaksi) {
            $details = $transaksi->details;
            $totalItems = count($details);

            foreach ($details as $index => $detail) {
                $isLastItem = ($index === $totalItems - 1); // Penanda item terakhir dalam 1 bon

                $rows[] = [
                    'no' => $no++,
                    'no_transaksi' => $transaksi->no_transaksi,
                    'no_voucher' => $transaksi->no_surat_jalan ?? '-',
                    'tanggal' => $transaksi->tanggal_transaksi ? date('d/m/Y', strtotime($transaksi->tanggal_transaksi)) : '-',
                    'nama_barang' => $detail->barang->nama_barang ?? '-',
                    'qty' => $detail->qty,
                    'satuan' => $detail->satuan->nama_satuan ?? 'pcs',
                    'project' => $detail->project->nama_project ?? $transaksi->namaProjectTerkait() ?? '-',
                    'request_oleh' => $transaksi->request_oleh ?? $transaksi->diterima_oleh ?? '-',
                    'is_last_item' => $isLastItem,
                ];
            }
        }

        return $rows;
    }

    public function export()
    {
        if (! $this->sudahTampil) {
            return null;
        }

        $rows = $this->getRows();
        $dariTanggal = !empty($this->data['dari_tanggal']) ? date('d/m/Y', strtotime($this->data['dari_tanggal'])) : '';
        $sampaiTanggal = !empty($this->data['sampai_tanggal']) ? date('d/m/Y', strtotime($this->data['sampai_tanggal'])) : '';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // 1. Header Judul Laporan
        $sheet->setCellValue('A1', 'REPORT FOR PAYMENT MATERIAL EKA SURYA');
        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        if ($dariTanggal && $sampaiTanggal) {
            $sheet->setCellValue('A2', "PERIODE: {$dariTanggal} - {$sampaiTanggal}");
            $sheet->mergeCells('A2:I2');
            $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(10);
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // 2. Header Tabel Excel
        $headers = ['No', 'NO. OF VOUCHER', 'Date Request', 'DESCRIPTION MATERIAL', 'QTY', 'Satuan', 'Project', 'Request', 'AMOUNT / Total Harga'];
        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];

        $headerRow = ($dariTanggal && $sampaiTanggal) ? 4 : 3;

        foreach ($headers as $index => $header) {
            $col = $cols[$index];
            $sheet->setCellValue($col . $headerRow, $header);
            $sheet->getStyle($col . $headerRow)->getFont()->setBold(true);
            $sheet->getStyle($col . $headerRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($col . $headerRow)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('92D050'); 
        }

        // 3. Mengisi Data Baris
        $dataRow = $headerRow + 1;
        foreach ($rows as $r) {
            $sheet->setCellValue('A' . $dataRow, $r['no']);
            $sheet->setCellValue('B' . $dataRow, $r['no_voucher']);
            $sheet->setCellValue('C' . $dataRow, $r['tanggal']);
            $sheet->setCellValue('D' . $dataRow, $r['nama_barang']);
            $sheet->setCellValue('E' . $dataRow, $r['qty']);
            $sheet->setCellValue('F' . $dataRow, $r['satuan']);
            $sheet->setCellValue('G' . $dataRow, $r['project']);
            $sheet->setCellValue('H' . $dataRow, $r['request_oleh']);
            $sheet->setCellValue('I' . $dataRow, '');

            
            if ($r['is_last_item']) {
                $sheet->getStyle('I' . $dataRow)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('FFFF00'); 
            }

            // Alignment
            $sheet->getStyle('A' . $dataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . $dataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('C' . $dataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('E' . $dataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('F' . $dataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $dataRow++;
        }

        // Garis Border Tabel
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];
        $sheet->getStyle("A{$headerRow}:I" . ($dataRow - 1))->applyFromArray($styleArray);

        // Auto Lebar Kolom
        foreach ($cols as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'Report_Bulanan_Eka_Surya_' . date('Ymd_His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }
}