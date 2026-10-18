<?php

namespace App\Filament\Pages;

use App\Support\LaporanTransaksiBarangQuery;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Laporan Transaksi Barang.
 * Filter, pencarian, dan per page memakai query string (GET biasa), bukan Livewire,
 * jadi tidak ada request update yang bisa kena error "This page has expired".
 */
class LaporanTransaksiBarangPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationGroup = 'Laporan';
    protected static ?string $navigationLabel = 'Laporan Transaksi Barang';
    protected static ?string $title = 'Laporan Transaksi Barang';
    protected static ?string $slug = 'laporan-transaksi-barang';

    protected static string $view = 'filament.pages.laporan-transaksi-barang';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportExcel')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                // Link GET biasa, ikut filter yang sedang aktif
                ->url(fn () => route('laporan-transaksi-barang.export', $this->filterParams())),
        ];
    }

    /** Filter aktif, diambil dari query string dan dibersihkan. */
    public function filterParams(): array
    {
        $f = request()->only(['dari', 'sampai', 'status', 'project', 'search']);

        foreach (['dari', 'sampai'] as $key) {
            if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($f[$key] ?? ''))) {
                unset($f[$key]);
            }
        }

        return array_filter($f, fn ($v) => filled($v));
    }

    /** @return int|string angka, atau 'all' untuk menampilkan semua baris */
    public function perPage(): int|string
    {
        $p = request()->query('per_page', 25);

        if ($p === 'all') {
            return 'all';
        }

        return in_array((int) $p, [25, 50, 100, 200], true) ? (int) $p : 25;
    }

    public function getRows(): LengthAwarePaginator
    {
        $perPage = $this->perPage();

        return LaporanTransaksiBarangQuery::build($this->filterParams())
            ->paginate($perPage === 'all' ? 100000 : $perPage)
            ->withQueryString();
    }

    public function getStatusOptions(): array
    {
        return DB::table('transaksi_barangs')
            ->whereNotNull('jenis_transaksi')
            ->distinct()
            ->orderBy('jenis_transaksi')
            ->pluck('jenis_transaksi')
            ->mapWithKeys(fn ($v) => [$v => strtoupper($v)])
            ->all();
    }

    public function getProjectOptions(): array
    {
        return DB::table('projects')->orderBy('nama_project')->pluck('nama_project', 'id')->all();
    }
}