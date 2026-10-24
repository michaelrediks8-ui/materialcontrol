<?php

namespace App\Filament\Pages;

use App\Exports\LaporanMutasiExport;
use App\Models\Barang;
use App\Models\Gudang;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\TransaksiBarangDetail;
use App\Models\Vendor;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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

    /** Data baru dimuat setelah tombol "Tampilkan Data" ditekan. */
    public bool $sudahTampil = false;

    private const BATAS_PREVIEW = 300;

    /** Hasil hitung untuk request ini (tidak dikirim ke browser). */
    protected ?array $hasil = null;

    /** Cache nama pihak (vendor/gudang/project/PO) selama request. */
    protected array $memo = [];

    protected function getHeaderActions(): array
    {
        return [
            Action::make('tampilkan')
                ->label('Tampilkan Data')
                ->icon('heroicon-o-eye')
                ->action(fn () => $this->sudahTampil = true),
        ];
    }

    /** Filter berubah -> data disembunyikan sampai ditampilkan ulang. */
    public function updatedData(): void
    {
        $this->sudahTampil = false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'dari_tanggal' => now()->format('Y-m-d'),
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

    /* =========================================================
       PERHITUNGAN  (kartu stok per gudang)
       Setiap transaksi dipecah menjadi "sisi" gudang:
         - barang PERGI dari gudang asal  -> kolom Keluar di gudang asal
         - barang TIBA di gudang tujuan   -> kolom Masuk di gudang tujuan
       Jadi transfer Workshop -> Marvel 3 mengurangi saldo Workshop
       dan menambah saldo Marvel 3. Saldo berjalan per gudang + barang.
       Barang yang langsung ke project tidak menyentuh stok gudang.
       Qty selalu dalam satuan dasar barang.
    ========================================================= */

    protected function hitung(): array
    {
        if ($this->hasil !== null) {
            return $this->hasil;
        }

        if (! $this->sudahTampil) {
            return $this->hasil = ['rows' => [], 'ringkasan' => []];
        }

        $dari = $this->data['dari_tanggal'] ?? null;
        $sampai = $this->data['sampai_tanggal'] ?? null;
        $gudangId = $this->data['gudang_id'] ?? '__all__';
        $jenis = $this->data['jenis_transaksi'] ?? '__all__';
        $barangId = $this->data['barang_id'] ?? '__all__';

        $gudangDipilih = $gudangId && $gudangId !== '__all__';
        $barangDipilih = $barangId && $barangId !== '__all__';
        $jenisDipilih = $jenis && $jenis !== '__all__';

        $namaGudang = Gudang::pluck('nama_gudang', 'id')->toArray();

        /* ---- Saldo awal per gudang + barang: lalu lintas sebelum "Dari Tanggal" ---- */
        $saldoAwal = [];
        if ($dari) {
            $qtyDasar = 'td.qty * CASE WHEN td.satuan_id = b.satuan_besar_id AND b.isi_per_satuan_besar > 0 THEN b.isi_per_satuan_besar ELSE 1 END';
            $batas = Carbon::parse($dari)->startOfDay()->format('Y-m-d H:i:s');

            $sisi = function (string $kolomGudang, string $tipe, string $tanda) use ($qtyDasar, $barangDipilih, $barangId, $gudangDipilih, $gudangId, $batas) {
                $sql = "SELECT t.{$kolomGudang} AS gudang_id, td.barang_id, {$tanda}({$qtyDasar}) AS qty
                        FROM transaksi_barang_details td
                        JOIN transaksi_barangs t ON t.id = td.transaksi_barang_id
                        JOIN barangs b ON b.id = td.barang_id
                        WHERE t.{$tipe} = 'gudang' AND t.{$kolomGudang} IS NOT NULL
                          AND t.tanggal_transaksi < ?";
                $bind = [$batas];

                if ($barangDipilih) {
                    $sql .= ' AND td.barang_id = ?';
                    $bind[] = $barangId;
                }
                if ($gudangDipilih) {
                    $sql .= " AND t.{$kolomGudang} = ?";
                    $bind[] = $gudangId;
                }

                return [$sql, $bind];
            };

            [$sqlMasuk, $bindMasuk] = $sisi('tujuan_id', 'tujuan_type', '');
            [$sqlKeluar, $bindKeluar] = $sisi('asal_id', 'asal_type', '-');

            $hasilAwal = DB::select(
                "SELECT gudang_id, barang_id, SUM(qty) AS saldo FROM ({$sqlMasuk} UNION ALL {$sqlKeluar}) x GROUP BY gudang_id, barang_id",
                array_merge($bindMasuk, $bindKeluar)
            );

            foreach ($hasilAwal as $r) {
                $saldoAwal[$r->gudang_id . '|' . $r->barang_id] = (float) $r->saldo;
            }
        }

        /* ---- Transaksi pada periode ---- */
        $details = TransaksiBarangDetail::query()
            ->with(['transaksi', 'barang.satuan', 'barang.satuanBesar', 'project'])
            ->whereHas('transaksi', function ($q) use ($dari, $sampai, $gudangDipilih, $gudangId) {
                if ($dari) {
                    $q->where('tanggal_transaksi', '>=', Carbon::parse($dari)->startOfDay());
                }
                if ($sampai) {
                    $q->where('tanggal_transaksi', '<=', Carbon::parse($sampai)->endOfDay());
                }
                if ($gudangDipilih) {
                    $q->where(function ($q2) use ($gudangId) {
                        $q2->where(function ($sub) use ($gudangId) {
                            $sub->where('tujuan_type', 'gudang')->where('tujuan_id', $gudangId);
                        })->orWhere(function ($sub) use ($gudangId) {
                            $sub->where('asal_type', 'gudang')->where('asal_id', $gudangId);
                        });
                    });
                }
            })
            ->when($barangDipilih, fn ($q) => $q->where('barang_id', $barangId))
            ->get()
            ->filter(fn ($d) => $d->transaksi);

        /* ---- Pecah jadi entri per sisi gudang ---- */
        $entri = [];
        foreach ($details as $detail) {
            $t = $detail->transaksi;
            $barang = $detail->barang;

            $factor = 1;
            if ($barang && $detail->satuan_id == $barang->satuan_besar_id && $barang->isi_per_satuan_besar > 0) {
                $factor = $barang->isi_per_satuan_besar;
            }
            $qty = (float) $detail->qty * $factor;

            $proyek = $detail->project ? " 📌 [{$detail->project->nama_project}]" : '';
            $dariNama = $this->namaPihak($t->asal_type, $t->asal_id);
            $keNama = $this->namaPihak($t->tujuan_type, $t->tujuan_id);

            $sisiList = [];

            if ($t->asal_type === 'gudang' && (! $gudangDipilih || (string) $t->asal_id === (string) $gudangId)) {
                $sisiList[] = ['gudang' => $t->asal_id, 'masuk' => 0.0, 'keluar' => $qty, 'ket' => "Ke: {$keNama}{$proyek}", 'stok' => true];
            }
            if ($t->tujuan_type === 'gudang' && (! $gudangDipilih || (string) $t->tujuan_id === (string) $gudangId)) {
                $sisiList[] = ['gudang' => $t->tujuan_id, 'masuk' => $qty, 'keluar' => 0.0, 'ket' => "Dari: {$dariNama}{$proyek}", 'stok' => true];
            }
            if (! $sisiList) {
                $sisiList[] = ['gudang' => null, 'masuk' => 0.0, 'keluar' => 0.0, 'ket' => "Dari: {$dariNama} → Langsung ke Project: {$keNama}{$proyek}", 'stok' => false];
            }

            foreach ($sisiList as $sisi) {
                $entri[] = $sisi + [
                    'detail_id' => $detail->id,
                    'tanggal' => $t->tanggal_transaksi,
                    'no_transaksi' => $t->no_transaksi,
                    'jenis_transaksi' => $t->jenis_transaksi,
                    'barang_id' => $detail->barang_id,
                    'kode_barang' => $barang->kode_barang ?? '-',
                    'nama_barang' => $barang->nama_barang ?? '-',
                    'satuan' => $barang?->satuan?->nama_satuan ?? '-',
                ];
            }
        }

        // Urut: gudang -> barang -> tanggal (kronologis)
        usort($entri, function ($a, $b) use ($namaGudang) {
            $ga = $a['gudang'] ? ($namaGudang[$a['gudang']] ?? '') : '~';
            $gb = $b['gudang'] ? ($namaGudang[$b['gudang']] ?? '') : '~';

            return [$ga, $a['nama_barang'], (string) $a['tanggal'], $a['detail_id']]
                <=> [$gb, $b['nama_barang'], (string) $b['tanggal'], $b['detail_id']];
        });

        /* ---- Saldo berjalan ---- */
        $rows = [];
        $ringkasan = [];
        $saldo = [];

        foreach ($entri as $e) {
            $gudangLabel = $e['gudang'] ? ($namaGudang[$e['gudang']] ?? '-') : 'Langsung ke Project';

            if ($e['stok']) {
                $kunci = $e['gudang'] . '|' . $e['barang_id'];

                if (! isset($saldo[$kunci])) {
                    $awal = $saldoAwal[$kunci] ?? 0.0;
                    $saldo[$kunci] = $awal;
                    $ringkasan[$kunci] = [
                        'gudang' => $gudangLabel,
                        'kode_barang' => $e['kode_barang'],
                        'nama_barang' => $e['nama_barang'],
                        'satuan' => $e['satuan'],
                        'saldo_awal' => $awal,
                        'masuk' => 0.0,
                        'keluar' => 0.0,
                        'sisa' => $awal,
                    ];

                    if ($dari && abs($awal) > 0) {
                        $rows[] = [
                            'tanggal' => Carbon::parse($dari)->startOfDay(),
                            'no_transaksi' => '-',
                            'jenis_transaksi' => 'awal',
                            'gudang' => $gudangLabel,
                            'kode_barang' => $e['kode_barang'],
                            'nama_barang' => $e['nama_barang'],
                            'satuan' => $e['satuan'],
                            'keterangan' => 'Saldo awal periode',
                            'tanpa_gudang' => false,
                            'masuk' => 0.0,
                            'keluar' => 0.0,
                            'saldo' => $awal,
                        ];
                    }
                }

                $saldo[$kunci] += $e['masuk'] - $e['keluar'];
                $ringkasan[$kunci]['masuk'] += $e['masuk'];
                $ringkasan[$kunci]['keluar'] += $e['keluar'];
                $ringkasan[$kunci]['sisa'] = $saldo[$kunci];
            }

            // Filter jenis hanya menyaring baris tampil; saldo tetap dari semua lalu lintas.
            if ($jenisDipilih && $e['jenis_transaksi'] !== $jenis) {
                continue;
            }

            $rows[] = [
                'tanggal' => $e['tanggal'],
                'no_transaksi' => $e['no_transaksi'],
                'jenis_transaksi' => $e['jenis_transaksi'],
                'gudang' => $gudangLabel,
                'kode_barang' => $e['kode_barang'],
                'nama_barang' => $e['nama_barang'],
                'satuan' => $e['satuan'],
                'keterangan' => $e['ket'],
                'tanpa_gudang' => ! $e['stok'],
                'masuk' => $e['masuk'],
                'keluar' => $e['keluar'],
                'saldo' => $e['stok'] ? $saldo[$e['gudang'] . '|' . $e['barang_id']] : null,
            ];
        }

        return $this->hasil = [
            'rows' => $rows,
            'ringkasan' => array_values($ringkasan),
        ];
    }

    /** Baris untuk preview (dibatasi supaya ringan). */
    public function getRows(): array
    {
        return array_slice($this->hitung()['rows'], 0, self::BATAS_PREVIEW);
    }

    public function totalBaris(): int
    {
        return count($this->hitung()['rows']);
    }

    /** Ringkasan per barang: saldo awal, total masuk, total keluar, sisa stok. */
    public function getRingkasan(): array
    {
        return $this->hitung()['ringkasan'];
    }

    public function getSummary(): array
    {
        $rows = $this->hitung()['rows'];

        $noTransaksiUnik = [];
        foreach ($rows as $row) {
            $noTransaksiUnik[$row['no_transaksi']] = true;
        }

        return [
            'total_masuk' => array_sum(array_column($rows, 'masuk')),
            'total_keluar' => array_sum(array_column($rows, 'keluar')),
            'total_transaksi' => count($noTransaksiUnik),
        ];
    }

    protected function namaPihak(?string $type, $id): string
    {
        if (! $type || ! $id) {
            return '-';
        }

        $key = $type . '|' . $id;
        if (isset($this->memo[$key])) {
            return $this->memo[$key];
        }

        $nama = '-';

        if ($type === 'vendor') {
            $nama = Vendor::find($id)?->nama_vendor ?? '-';
        } elseif ($type === 'gudang') {
            $nama = Gudang::find($id)?->nama_gudang ?? '-';
        } elseif ($type === 'purchase_order') {
            $po = PurchaseOrder::with('project')->find($id);
            if ($po) {
                $namaProject = $po->project->nama_project ?? '';
                $nama = $namaProject ? "{$po->no_po} - {$namaProject}" : $po->no_po;
            }
        } elseif ($type === 'project') {
            $po = PurchaseOrder::with('project')->find($id);
            if ($po && $po->project) {
                $nama = $po->project->nama_project;
            } else {
                $nama = Project::find($id)?->nama_project ?? '-';
            }
        }

        return $this->memo[$key] = $nama;
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
        if (! $this->sudahTampil) {
            return null;
        }

        $hasil = $this->hitung();

        $periode = collect([
            ! empty($this->data['dari_tanggal']) ? Carbon::parse($this->data['dari_tanggal'])->format('d/m/Y') : null,
            ! empty($this->data['sampai_tanggal']) ? Carbon::parse($this->data['sampai_tanggal'])->format('d/m/Y') : null,
        ])->filter()->implode(' - ');

        $gudang = ($this->data['gudang_id'] ?? '__all__') === '__all__'
            ? 'Semua Gudang'
            : (Gudang::find($this->data['gudang_id'])?->nama_gudang ?? '-');

        return Excel::download(
            new LaporanMutasiExport($hasil['rows'], $hasil['ringkasan'], $periode, $gudang),
            'laporan-mutasi-barang-' . now()->format('Y-m-d-His') . '.xlsx'
        );
    }
}