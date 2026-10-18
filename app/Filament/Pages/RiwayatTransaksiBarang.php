<?php

namespace App\Filament\Pages;

use App\Models\Barang;
use App\Models\Gudang;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class RiwayatTransaksiBarang extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'Laporan';
    protected static ?string $navigationLabel = 'Riwayat Transaksi Barang';
    protected static ?string $title = 'Riwayat Transaksi Barang';
    protected static ?int $navigationSort = 6;

    protected static string $view = 'filament.pages.riwayat-transaksi-barang';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('barang_id')
                    ->label('Pilih Barang')
                    ->options(fn () => Barang::query()
                        ->orderBy('nama_barang')
                        ->get()
                        ->mapWithKeys(fn ($b) => [$b->id => "[{$b->kode_barang}] {$b->nama_barang}"])
                        ->toArray())
                    ->searchable()
                    ->preload()
                    ->live()
                    ->required(),

                Select::make('gudang_id')
                    ->label('Pilih Gudang')
                    ->options(function () {
                        return ['__all__' => '🏢 Semua Gudang'] + Gudang::pluck('nama_gudang', 'id')->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->live()
                    ->required(),
            ])
            ->statePath('data')
            ->columns(2);
    }

    public function getRows(): array
    {
        $barangId = $this->data['barang_id'] ?? null;
        $gudangId = $this->data['gudang_id'] ?? null;

        if (! $barangId || ! $gudangId) {
            return [];
        }

        $barang = Barang::find($barangId);
        if (! $barang) {
            return [];
        }

        $isSemuaGudang = $gudangId === '__all__';

        $query = DB::table('transaksi_barang_details as td')
            ->join('transaksi_barangs as t', 't.id', '=', 'td.transaksi_barang_id')
            ->where('td.barang_id', $barangId);

        if ($isSemuaGudang) {
            $query->where(function ($q) {
                $q->where('t.tujuan_type', 'gudang')
                    ->orWhere('t.asal_type', 'gudang');
            });
        } else {
            $query->where(function ($q) use ($gudangId) {
                $q->where(function ($q2) use ($gudangId) {
                    $q2->where('t.tujuan_type', 'gudang')->where('t.tujuan_id', $gudangId);
                })->orWhere(function ($q2) use ($gudangId) {
                    $q2->where('t.asal_type', 'gudang')->where('t.asal_id', $gudangId);
                });
            });
        }

        $rows = $query
            ->select(
                't.id as transaksi_id', // Menambahkan ID Transaksi untuk relasi Eloquent
                't.no_transaksi', 't.jenis_transaksi', 't.tanggal_transaksi',
                't.asal_type', 't.asal_id', 't.tujuan_type', 't.tujuan_id',
                'td.qty', 'td.satuan_id'
            )
            // MENGATASI SALDO NGAWUR: Pastikan Masuk/Saldo Awal diproses duluan sebelum Keluar di hari yang sama
            ->orderBy('t.tanggal_transaksi', 'asc')
            ->orderByRaw("CASE WHEN t.jenis_transaksi IN ('masuk', 'saldo_awal') THEN 1 ELSE 2 END ASC")
            ->orderBy('t.id', 'asc')
            ->get();

        $saldo = 0;
        $result = [];

        foreach ($rows as $row) {
            $factor = 1;
            if ($row->satuan_id == $barang->satuan_besar_id && $barang->isi_per_satuan_besar > 0) {
                $factor = $barang->isi_per_satuan_besar;
            }
            $qtyBasis = $row->qty * $factor;

            if ($isSemuaGudang) {
                $isMasuk = $row->tujuan_type === 'gudang';
                $isKeluar = $row->asal_type === 'gudang';
            } else {
                $isMasuk = $row->tujuan_type === 'gudang' && (int) $row->tujuan_id === (int) $gudangId;
                $isKeluar = $row->asal_type === 'gudang' && (int) $row->asal_id === (int) $gudangId;
            }

            $qtyMasuk = 0;
            $qtyKeluar = 0;

            $isTransferInternal = $isSemuaGudang
                && $row->asal_type === 'gudang'
                && $row->tujuan_type === 'gudang';

            if ($isTransferInternal) {
                $qtyMasuk = 0;
                $qtyKeluar = 0;
            } else {
                if ($isMasuk) {
                    $qtyMasuk = $qtyBasis;
                    $saldo += $qtyBasis;
                }
                if ($isKeluar) {
                    $qtyKeluar = $qtyBasis;
                    $saldo -= $qtyBasis;
                }
            }

            // Mengirimkan $row lengkap ke fungsi namaPihak
            $keterangan = $isSemuaGudang
                ? $this->keteranganSemuaGudang($row)
                : ($isMasuk
                    ? 'Dari: ' . $this->namaPihak($row->asal_type, $row->asal_id, $row)
                    : 'Ke: ' . $this->namaPihak($row->tujuan_type, $row->tujuan_id, $row));

            $result[] = [
                'tanggal' => $row->tanggal_transaksi,
                'no_transaksi' => $row->no_transaksi,
                'jenis_transaksi' => $row->jenis_transaksi,
                'keterangan' => $keterangan,
                'qty_masuk' => $qtyMasuk,
                'qty_keluar' => $qtyKeluar,
                'saldo' => $saldo,
            ];
        }

        return $result;
    }

    protected function keteranganSemuaGudang($row): string
    {
        $asal = $this->namaPihak($row->asal_type, $row->asal_id, $row);
        $tujuan = $this->namaPihak($row->tujuan_type, $row->tujuan_id, $row);
        return "{$asal} → {$tujuan}";
    }

    // MENGATASI NAMA PROJECT NGAWUR
    protected function namaPihak(?string $type, ?int $id, $row = null): string
    {
        if (! $type || ! $id) {
            return '-';
        }

        if ($type === 'vendor') {
            return Vendor::find($id)?->nama_vendor ?? '-';
        }
        
        if ($type === 'gudang') {
            return Gudang::find($id)?->nama_gudang ?? '-';
        }

        if ($type === 'project' || $type === 'purchase_order') {
            
            // 1. CARA PALING AKURAT: Panggil Model aslinya agar sama dengan halaman Barang Keluar
            if ($row && isset($row->transaksi_id)) {
                $trx = \App\Models\TransaksiBarang::find($row->transaksi_id);
                if ($trx) {
                    // Cek jika transaksi punya relasi purchase_order_id
                    if (!empty($trx->purchase_order_id) && $trx->purchaseOrder?->project) {
                        return $trx->purchaseOrder->project->nama_project;
                    }
                    // Cek jika transaksi punya relasi project_id langsung
                    if (!empty($trx->project_id) && $trx->project) {
                        return $trx->project->nama_project;
                    }
                }
            }

            // 2. FALLBACK UNTUK DATA LAMA: 
            // Jika ternyata tujuan_id malah diisi ID Purchase Order (salah simpan di masa lalu)
            $po = PurchaseOrder::with('project')->find($id);
            if ($po && $po->project) {
                return $po->project->nama_project; 
            }

            // 3. FALLBACK NORMAL: Ambil dari tabel Project
            return Project::find($id)?->nama_project ?? '-';
        }

        return '-';
    }

    public function getSatuanKecil(): string
    {
        $barangId = $this->data['barang_id'] ?? null;
        if (! $barangId) {
            return '';
        }
        $barang = Barang::find($barangId);
        return $barang?->satuan?->nama_satuan ?? '';
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
}