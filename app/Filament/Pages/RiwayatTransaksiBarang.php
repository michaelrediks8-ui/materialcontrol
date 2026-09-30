<?php

namespace App\Filament\Pages;

use App\Models\Barang;
use App\Models\Gudang;
use App\Models\Project;
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
                't.no_transaksi', 't.jenis_transaksi', 't.tanggal_transaksi',
                't.asal_type', 't.asal_id', 't.tujuan_type', 't.tujuan_id',
                'td.qty', 'td.satuan_id'
            )
            ->orderBy('t.tanggal_transaksi')
            ->orderBy('t.id')
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

            $keterangan = $isSemuaGudang
                ? $this->keteranganSemuaGudang($row)
                : ($isMasuk
                    ? 'Dari: ' . $this->namaPihak($row->asal_type, $row->asal_id)
                    : 'Ke: ' . $this->namaPihak($row->tujuan_type, $row->tujuan_id));

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
        $asal = $this->namaPihak($row->asal_type, $row->asal_id);
        $tujuan = $this->namaPihak($row->tujuan_type, $row->tujuan_id);
        return "{$asal} → {$tujuan}";
    }

    protected function namaPihak(?string $type, ?int $id): string
    {
        if (! $type || ! $id) {
            return '-';
        }

        return match ($type) {
            'vendor' => Vendor::find($id)?->nama_vendor ?? '-',
            'gudang' => Gudang::find($id)?->nama_gudang ?? '-',
            'project' => Project::find($id)?->nama_project ?? '-',
            default => '-',
        };
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