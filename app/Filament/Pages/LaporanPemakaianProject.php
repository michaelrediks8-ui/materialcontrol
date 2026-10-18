<?php

namespace App\Filament\Pages;

use App\Exports\LaporanPemakaianProjectExport;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\TransaksiBarangDetail;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Maatwebsite\Excel\Facades\Excel;

class LaporanPemakaianProject extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup = 'Laporan';
    protected static ?string $navigationLabel = 'Pemakaian per Project';
    protected static ?string $title = 'Laporan Pemakaian Material per Project';
    protected static ?int $navigationSort = 8;

    protected static string $view = 'filament.pages.laporan-pemakaian-project';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'project_id' => '__all__',
            'dari_tanggal' => null,
            'sampai_tanggal' => null,
        ]);
    }

    /**
     * Memindahkan tombol Export ke Header Action Native Filament (Pojok Kanan Atas)
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Ekspor Excel (.xlsx)')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action('export'),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('project_id')
                    ->label('Pilih Project')
                    ->options(function () {
                        return ['__all__' => 'Semua Project'] + Project::query()
                            ->pluck('nama_project', 'id')
                            ->map(fn ($nama) => (string) $nama)
                            ->toArray();
                    })
                    ->default('__all__')
                    ->live()
                    ->searchable()
                    ->preload(),

                DatePicker::make('dari_tanggal')
                    ->label('Dari Tanggal (Opsional)')
                    ->live(),

                DatePicker::make('sampai_tanggal')
                    ->label('Sampai Tanggal (Opsional)')
                    ->live(),
            ])
            ->statePath('data')
            ->columns(3);
    }

    public function getRows(): array
    {
        $selectedProjectId = $this->data['project_id'] ?? '__all__';
        $dariTanggal = $this->data['dari_tanggal'] ?? null;
        $sampaiTanggal = $this->data['sampai_tanggal'] ?? null;

        $query = TransaksiBarangDetail::query()
            ->with([
                'transaksi.purchaseOrder.project',
                'barang.satuan',
                'barang.satuanBesar',
                'project',
            ])
            ->whereHas('transaksi', function ($q) use ($dariTanggal, $sampaiTanggal) {
                $q->where('jenis_transaksi', 'keluar');

                if ($dariTanggal) {
                    $q->whereDate('tanggal_transaksi', '>=', $dariTanggal);
                }
                if ($sampaiTanggal) {
                    $q->whereDate('tanggal_transaksi', '<=', $sampaiTanggal);
                }
            });

        $details = $query->get();

        $grouped = [];
        foreach ($details as $detail) {
            $transaksi = $detail->transaksi;
            if (! $transaksi) {
                continue;
            }

            $projectId = null;
            $namaProject = '-';

            if ($detail->project_id && $detail->project) {
                $projectId = $detail->project_id;
                $namaProject = $detail->project->nama_project;
                
                if ($transaksi->purchase_order_id && $transaksi->purchaseOrder) {
                    $namaProject = "{$transaksi->purchaseOrder->no_po} - {$namaProject}";
                } elseif ($transaksi->tujuan_type === 'purchase_order') {
                    $po = PurchaseOrder::find($transaksi->tujuan_id);
                    if ($po) {
                        $namaProject = "{$po->no_po} - {$namaProject}";
                    }
                }
            } else {
                if ($transaksi->purchase_order_id && $transaksi->purchaseOrder && $transaksi->purchaseOrder->project) {
                    $projectId = $transaksi->purchaseOrder->project->id;
                    $namaProject = "{$transaksi->purchaseOrder->no_po} - " . $transaksi->purchaseOrder->project->nama_project;
                } elseif ($transaksi->tujuan_type === 'project' || $transaksi->tujuan_type === 'purchase_order') {
                    $po = PurchaseOrder::with('project')->find($transaksi->tujuan_id);
                    if ($po && $po->project) {
                        $projectId = $po->project->id;
                        $namaProject = "{$po->no_po} - " . $po->project->nama_project;
                    } else {
                        $proj = Project::find($transaksi->tujuan_id);
                        if ($proj) {
                            $projectId = $proj->id;
                            $namaProject = $proj->nama_project;
                        }
                    }
                }
            }

            if (! $projectId) {
                continue;
            }

            if ($selectedProjectId !== '__all__' && (string) $projectId !== (string) $selectedProjectId) {
                continue;
            }

            $barang = $detail->barang;
            $factor = 1;
            if ($barang && $detail->satuan_id == $barang->satuan_besar_id && $barang->isi_per_satuan_besar > 0) {
                $factor = $barang->isi_per_satuan_besar;
            }
            $qtyBasis = $detail->qty * $factor; 

            $key = $projectId . '-' . $detail->barang_id;

            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'project' => $namaProject,
                    'kode_barang' => $barang->kode_barang ?? '-',
                    'nama_barang' => $barang->nama_barang ?? '-',
                    'total_qty' => 0,
                    'satuan' => $barang->satuan->nama_satuan ?? '-',
                    'tanggal_terakhir' => $transaksi->tanggal_transaksi,
                    'request_oleh' => $transaksi->request_oleh ?? '-',
                ];
            } else {
                if ($transaksi->tanggal_transaksi > $grouped[$key]['tanggal_terakhir']) {
                    $grouped[$key]['tanggal_terakhir'] = $transaksi->tanggal_transaksi;
                    $grouped[$key]['request_oleh'] = $transaksi->request_oleh ?? '-';
                }
            }

            $grouped[$key]['total_qty'] += $qtyBasis;
        }

        $result = array_values($grouped);

        usort($result, fn ($a, $b) => strcmp($a['project'], $b['project']) ?: strcmp($a['nama_barang'], $b['nama_barang']));

        return $result;
    }

    /**
     * Helper Method untuk mengelompokkan baris berdasarkan nama Project
     */
    public function getGroupedRows(): array
    {
        $rows = $this->getRows();
        $grouped = [];

        foreach ($rows as $row) {
            $projectName = $row['project'];
            if (! isset($grouped[$projectName])) {
                $grouped[$projectName] = [];
            }
            $grouped[$projectName][] = $row;
        }

        return $grouped;
    }

    public function getSummary(): array
    {
        $rows = $this->getRows();

        return [
            'total_item' => count($rows),
            'total_project' => collect($rows)->pluck('project')->unique()->count(),
            'total_barang' => collect($rows)->pluck('nama_barang')->unique()->count(),
        ];
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
            new LaporanPemakaianProjectExport($rows),
            'laporan-pemakaian-project-' . now()->format('Y-m-d-His') . '.xlsx'
        );
    }
}