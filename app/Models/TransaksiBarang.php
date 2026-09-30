<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class TransaksiBarang extends Model
{
    use HasFactory;
    use Searchable;

    protected $guarded = [];

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'no_transaksi' => $this->no_transaksi,
            'no_surat_jalan' => $this->no_surat_jalan,
            'request_oleh' => $this->request_oleh,
            'diterima_oleh' => $this->diterima_oleh,
        ];
    }

    protected static function booted()
    {
        static::creating(function ($transaksi) {
            if (empty($transaksi->no_transaksi)) {
                // 💡 UBAH DI SINI: Ambil dari inputan tanggal_transaksi, fallback ke now() jika kosong
                $tanggal = \Carbon\Carbon::parse($transaksi->tanggal_transaksi ?? now())->format('Ymd');
                $prefix = "TRX-{$tanggal}-";

                $lastRecord = self::where('no_transaksi', 'like', "{$prefix}%")
                    ->orderBy('id', 'desc')
                    ->first();

                $nextNumber = 1;
                if ($lastRecord) {
                    $lastNumber = (int) str_replace($prefix, '', $lastRecord->no_transaksi);
                    $nextNumber = $lastNumber + 1;
                }

                $transaksi->no_transaksi = $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            }
        });

        static::deleted(function ($transaksi) {
            if (class_exists('\App\Support\StokRebuilder')) {
                \App\Support\StokRebuilder::rebuild();
            }
        });
    }

    /* =========================================
       RELASI ELOQUENT
    ========================================== */
    public function details()
    {
        return $this->hasMany(TransaksiBarangDetail::class, 'transaksi_barang_id')->orderBy('id', 'asc');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    /* =========================================
       HELPER METHODS (Gudang / Project / Vendor)
    ========================================== */
    public function namaAsal(): string
    {
        if ($this->asal_type === 'vendor') {
            $vendor = once(fn () => Vendor::find($this->asal_id));
            return $vendor ? $vendor->nama_vendor : '-';
        }

        if ($this->asal_type === 'gudang') {
            $gudang = once(fn () => Gudang::find($this->asal_id));
            return $gudang ? $gudang->nama_gudang : '-';
        }

        return '-';
    }

    /**
     * Menampilkan lokasi fisik tempat barang diturunkan (Gudang / Project / PO)
     */
    public function namaTujuan(): string
    {
        if ($this->tujuan_type === 'gudang') {
            $gudang = once(fn () => Gudang::find($this->tujuan_id));
            return $gudang ? $gudang->nama_gudang : '-';
        }

        if ($this->tujuan_type === 'purchase_order') {
            $po = once(fn () => PurchaseOrder::with('project')->find($this->tujuan_id));
            if ($po) {
                $namaProject = $po->project->nama_project ?? '';
                return $namaProject ? "{$po->no_po} - {$namaProject}" : $po->no_po;
            }
        }

        if ($this->tujuan_type === 'project') {
            $po = once(fn () => PurchaseOrder::with('project')->find($this->tujuan_id));
            if ($po && $po->project) {
                return $po->project->nama_project;
            }

            $project = once(fn () => Project::find($this->tujuan_id));
            if ($project) {
                return $project->nama_project;
            }
        }

        return '-';
    }

    /**
     * Menampilkan Peruntukan Project / PO terkait transaksi ini (Header Level)
     */
    public function namaProjectTerkait(): string
    {
        // 1. Cek dari purchase_order_id (Biasa diisi saat Barang Masuk)
        if ($this->purchase_order_id) {
            $po = once(fn () => PurchaseOrder::with('project')->find($this->purchase_order_id));
            if ($po && $po->project) {
                return $po->project->nama_project;
            }
        }

        // 2. Cek jika tujuan_type = 'purchase_order'
        if ($this->tujuan_type === 'purchase_order') {
            $po = once(fn () => PurchaseOrder::with('project')->find($this->tujuan_id));
            if ($po && $po->project) {
                return $po->project->nama_project;
            }
        }

        // 3. Cek jika tujuan_type = 'project'
        if ($this->tujuan_type === 'project') {
            $po = once(fn () => PurchaseOrder::with('project')->find($this->tujuan_id));
            if ($po && $po->project) {
                return $po->project->nama_project;
            }

            $project = once(fn () => Project::find($this->tujuan_id));
            if ($project) {
                return $project->nama_project;
            }
        }

        return '-';
    }

    /**
     * Helper untuk mengambil nama project milik suatu detail item (dengan fallback ke Header)
     */
    public function namaProjectItem($detail): string
{
    // 1. Jika di level detail barang diisi project khusus
    if (!empty($detail->project)) {
        return $detail->project->nama_project;
    }

    if (!empty($detail->project_id)) {
        $projectDetail = once(fn () => Project::find($detail->project_id));
        if ($projectDetail) {
            return $projectDetail->nama_project;
        }
    }

    // 2. Jika detail KOSONG, cek apakah Header terisi
    $projectHeader = $this->namaProjectTerkait();

    if ($projectHeader !== '-') {
        // Berhasil mengambil dari PO / Project Header
        return "{$projectHeader} (Header)";
    }

    // 3. Jika Detail KOSONG dan Header juga KOSONG
    return '📦 Stok Transit Gudang';
}
}