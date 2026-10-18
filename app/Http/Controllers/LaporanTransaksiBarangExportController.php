<?php

namespace App\Http\Controllers;

use App\Exports\LaporanTransaksiBarangExport;
use App\Support\LaporanTransaksiBarangQuery;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LaporanTransaksiBarangExportController extends Controller
{
    public function __invoke(Request $request)
    {
        $filters = $request->only(['dari', 'sampai', 'status', 'project', 'search']);

        $rows = LaporanTransaksiBarangQuery::build($filters)->get();

        return LaporanTransaksiBarangExport::download(
            $rows,
            $this->periodeLabel($filters),
            'Laporan-Transaksi-Barang-' . now()->format('Ymd-His') . '.xlsx',
        );
    }

    private function periodeLabel(array $f): string
    {
        $dari = $f['dari'] ?? null;
        $sampai = $f['sampai'] ?? null;

        if (! $dari && ! $sampai) {
            return 'Semua tanggal';
        }

        $fmt = fn ($d) => Carbon::parse($d)->format('d/m/Y');

        return ($dari ? $fmt($dari) : 'awal') . ' s/d ' . ($sampai ? $fmt($sampai) : 'sekarang');
    }
}