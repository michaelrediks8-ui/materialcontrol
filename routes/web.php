<?php

use Illuminate\Support\Facades\Route;
use App\Models\MutasiAlat;
use App\Models\PeminjamanAlat;
use App\Models\PoProject;
use App\Models\PemakaianProject;
use Barryvdh\DomPDF\Facade\Pdf;
 use App\Http\Controllers\LaporanTransaksiBarangExportController;
 use App\Models\TransaksiBarang;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->group(function () {
    // 1. Mutasi Alat
    Route::get('/admin/mutasi-alat/{record}/preview-pdf', function (MutasiAlat $record) {
        $record->load(['details.alat']);
        return Pdf::loadView('pdf.surat-jalan-alat', ['mutasi' => $record])
            ->setPaper('a4', 'portrait')
            ->stream('Surat-Jalan-' . ($record->no_surat_jalan ?? $record->id) . '.pdf');
    })->name('mutasi-alat.preview-pdf');

    // 2. Peminjaman Alat
    Route::get('/admin/peminjaman-alat/{record}/preview-pdf', function (PeminjamanAlat $record) {
        $record->load(['details.alat']);
        return Pdf::loadView('pdf.bukti-peminjaman-alat', ['peminjaman' => $record])
            ->setPaper('a4', 'portrait')
            ->stream('Bukti-Peminjaman-' . $record->id . '.pdf');
    })->name('peminjaman-alat.preview-pdf');

    // 3. PO Project
    Route::get('/admin/po-project/{record}/preview-pdf', function (PoProject $record) {
        $record->load(['details.barang', 'supplier', 'project']);
        return Pdf::loadView('pdf.po-project', ['po' => $record])
            ->setPaper('a4', 'portrait')
            ->stream('PO-' . ($record->no_po ?? $record->id) . '.pdf');
    })->name('po-project.preview-pdf');
Route::get('/peminjaman-alat/{record}/preview-pdf', [PeminjamanAlatController::class, 'previewPdf'])
    ->name('peminjaman-alat.preview-pdf');
    // 4. Pemakaian per Project
    Route::get('/admin/peminjaman-alat/{record}/preview-pdf', function (PeminjamanAlat $record) {
    // Load relasi agar data alat, gudang, dan peminjam terbaca
    $record->load(['alat', 'gudang', 'karyawan']);

    $pdf = Pdf::loadView('pdf.bukti-peminjaman-alat', ['peminjaman' => $record])
        ->setPaper('a4', 'portrait');

    return $pdf->stream('Bukti-Peminjaman-Alat-' . $record->id . '.pdf');
})->name('peminjaman-alat.preview-pdf')->middleware(['auth']);
 Route::middleware('auth')
       ->get('/laporan-transaksi-barang/export', LaporanTransaksiBarangExportController::class)
       ->name('laporan-transaksi-barang.export');
});
Route::get('/admin/transaksi-barangs/{record}/pdf', function (TransaksiBarang $record) {
    // Memuat relasi
    $record->load(['details.barang.satuan', 'details.satuan', 'details.project']);

    // Konversi logo ke Base64 (asumsi logo berada di public/images/logo.png atau public/storage/logo.png)
    // Sesuaikan path jika letak file logo Anda berbeda di folder public
    $logoPath = public_path('images/logo.png'); 
    $logoBase64 = null;
    
    if (file_exists($logoPath)) {
        $logoData = file_get_contents($logoPath);
        $logoMime = mime_content_type($logoPath);
        $logoBase64 = 'data:' . $logoMime . ';base64,' . base64_encode($logoData);
    }

    $pdf = Pdf::loadView('pdf.bukti-transfer-barang', [
        'transaksi' => $record,
        'logoBase64' => $logoBase64
    ]);

    $pdf->setPaper('a4', 'portrait');

    return $pdf->stream('Bukti-Transfer-' . $record->no_transaksi . '.pdf');
})->name('transaksi-barang.pdf')->middleware(['web', 'auth']);