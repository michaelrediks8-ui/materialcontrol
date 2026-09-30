<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransaksiBarangDetail extends Model
{
    use HasFactory;

    protected $table = 'transaksi_barang_details';
    protected $guarded = ['id'];

    // Relasi ke Header Transaksi
    public function transaksi()
    {
        return $this->belongsTo(TransaksiBarang::class, 'transaksi_barang_id');
    }

    // Relasi ke Master Data Barang
    public function barang()
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }

    // Relasi ke Master Data Satuan (baru ditambahkan)
    public function satuan()
    {
        return $this->belongsTo(Satuan::class, 'satuan_id');
    }
    public function project()
    {
        return $this->belongsTo(\App\Models\Project::class, 'project_id');
    }
}