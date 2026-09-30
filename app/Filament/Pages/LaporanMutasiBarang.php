<?php

namespace App\Filament\Pages;

use App\Exports\LaporanMutasiExport;
use App\Models\Barang;
use App\Models\Gudang;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\TransaksiBarangDetail;
use App\Models\Vendor;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Maatwebsite\Excel\Facades\Excel;

class LaporanMutasiBarang extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationGroup = 'Laporan';
    protected static ?string $navigationLabel = 'Laporan Mutasi Barang';
    protected static ?string $title = 'Laporan Mutasi Barang';
    protected static ?int $navigationSort = 7;

    protected static string $view = 'filament.pages.laporan-mutasi-barang';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'dari_tanggal' => now()->startOfMonth()->format('Y-m-d'),
            'sampai_tanggal' => now()->format('Y-m-d'),
            'gudang_id' => '__all__',
            'jenis_transaksi' => '__all__',
            'barang_id' => '__all__',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('dari_tanggal')
                    ->label('Dari Tanggal')
                    ->live(),

                DatePicker::make('sampai_tanggal')
                    ->label('Sampai Tanggal')
                    ->live(),

                Select::make('gudang_id')
                    ->label('Gudang')
                    ->options(fn () => ['__all__' => 'Semua Gudang'] + Gudang::pluck('nama_gudang', 'id')->toArray())
                    ->default('__all__')
                    ->live()
                    ->searchable()
                    ->preload(),

                Select::make('jenis_transaksi')
                    ->label('Jenis Transaksi')
                    ->options([
                        '__all__' => 'Semua Jenis',
                        'masuk' => '📥 Barang Masuk',
                        'keluar' => '📤 Barang Keluar',
                        'transfer' => '🔄 Transfer Gudang',
                    ])
                    ->default('__all__')
                    ->live(),

                Select::make('barang_id')
                    ->label('Barang')
                    ->options(function () {
                        return ['__all__' => 'Semua Barang'] + Barang::query()
                            ->orderBy('nama_barang')
                            ->get()
                            ->mapWithKeys(fn ($b) => [$b->id => "[{$b->kode_barang}] {$b->nama_barang}"])
                            ->toArray();
                    })
                    ->default('__all__')
                    ->live()
                    ->searchable()
                    ->preload(),
            ])
            ->statePath('data')
            ->columns(3);
    }

    public function getRows(): array
    {
        $dariTanggal = $this->data['dari_tanggal'] ?? null;
        $sampaiTanggal = $this->data['sampai_tanggal'] ?? null;
        $gudangId = $this->data['gudang_id'] ?? '__all__';
        $jenisTransaksi = $this->data['jenis_transaksi'] ?? '__all__';
        $barangId = $this->data['barang_id'] ?? '__all__';

        $query = TransaksiBarangDetail::query()
            ->with([
                'transaksi', // 💡 Diubah dari transaksiBarang menjadi transaksi
                'barang.satuan',
                'barang.satuanBesar',
                'project',
            ])
            ->whereHas('transaksi', function ($q) use ($dariTanggal, $sampaiTanggal, $jenisTransaksi, $gudangId) { // 💡 Diubah
                if ($dariTanggal) {
                    $q->whereDate('tanggal_transaksi', '>=', $dariTanggal);
                }
                if ($sampaiTanggal) {
                    $q->whereDate('tanggal_transaksi', '<=', $sampaiTanggal);
                }
                if ($jenisTransaksi && $jenisTransaksi !== '__all__') {
                    $q->where('jenis_transaksi', $jenisTransaksi);
                }
                if ($gudangId && $gudangId !== '__all__') {
                    $q->where(function ($q2) use ($gudangId) {
                        $q2->where(function ($sub) use ($gudangId) {
                            $sub->where('tujuan_type', 'gudang')->where('tujuan_id', $gudangId);
                        })->orWhere(function ($sub) use ($gudangId) {
                            $sub->where('asal_type', 'gudang')->where('asal_id', $gudangId);
                        });
                    });
                }
            });

        if ($barangId && $barangId !== '__all__') {
            $query->where('barang_id', $barangId);
        }

        $details = $query->get()->sortByDesc(fn ($d) => $d->transaksi->tanggal_transaksi ?? null); // 💡 Diubah

        $result = [];
        foreach ($details as $detail) {
            $transaksi = $detail->transaksi; // 💡 Diubah
            if (! $transaksi) {
                continue;
            }

            $barang = $detail->barang;

            // Hitung Konversi Satuan
            $factor = 1;
            if ($barang && $detail->satuan_id == $barang->satuan_besar_id && $barang->isi_per_satuan_besar > 0) {
                $factor = $barang->isi_per_satuan_besar;
            }
            $qtyBasis = $detail->qty * $factor;

            // 💡 PENENTUAN TUJUAN SMART (Mendukung Alokasi Project Detail & Header Drop)
            $namaTujuan = $this->namaPihak($transaksi->tujuan_type, $transaksi->tujuan_id);
            if ($detail->project) {
                $namaTujuan .= " 📌 [{$detail->project->nama_project}]";
            }

            $result[] = [
                'tanggal' => $transaksi->tanggal_transaksi,
                'no_transaksi' => $transaksi->no_transaksi,
                'jenis_transaksi' => $transaksi->jenis_transaksi,
                'kode_barang' => $barang->kode_barang ?? '-',
                'nama_barang' => $barang->nama_barang ?? '-',
                'asal' => $this->namaPihak($transaksi->asal_type, $transaksi->asal_id),
                'tujuan' => $namaTujuan,
                'qty' => $qtyBasis,
            ];
        }

        return $result;
    }

    public function getSummary(): array
    {
        $rows = $this->getRows();

        $totalMasuk = 0;
        $totalKeluar = 0;
        $noTransaksiUnik = [];

        foreach ($rows as $row) {
            if ($row['jenis_transaksi'] === 'masuk') {
                $totalMasuk += $row['qty'];
            } elseif ($row['jenis_transaksi'] === 'keluar') {
                $totalKeluar += $row['qty'];
            }
            $noTransaksiUnik[$row['no_transaksi']] = true;
        }

        return [
            'total_masuk' => $totalMasuk,
            'total_keluar' => $totalKeluar,
            'total_transaksi' => count($noTransaksiUnik),
        ];
    }

    protected function namaPihak(?string $type, $id): string
    {
        if (! $type || ! $id) {
            return '-';
        }

        if ($type === 'vendor') {
            return once(fn () => Vendor::find($id))->nama_vendor ?? '-';
        }

        if ($type === 'gudang') {
            return once(fn () => Gudang::find($id))->nama_gudang ?? '-';
        }

        if ($type === 'purchase_order') {
            $po = once(fn () => PurchaseOrder::with('project')->find($id));
            if ($po) {
                $namaProject = $po->project->nama_project ?? '';
                return $namaProject ? "{$po->no_po} - {$namaProject}" : $po->no_po;
            }
        }

        if ($type === 'project') {
            $po = once(fn () => PurchaseOrder::with('project')->find($id));
            if ($po && $po->project) {
                return $po->project->nama_project;
            }

            $project = once(fn () => Project::find($id));
            if ($project) {
                return $project->nama_project;
            }
        }

        return '-';
    }

    public function formatQty($val): string
    {
        $val = (float) $val;
        $formatted = number_format($val, 2, ',', '.');
        if (str_contains($formatted, ',')) {
            $formatted = rtrim($formatted, '0');
            $formatted = rtrim($formatted, ',');
        }
        return $formatted;
    }

    public function export()
    {
        $rows = $this->getRows();

        return Excel::download(
            new LaporanMutasiExport($rows),
            'laporan-mutasi-barang-' . now()->format('Y-m-d-His') . '.xlsx'
        );
    }
}