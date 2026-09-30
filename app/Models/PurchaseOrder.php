<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $guarded = [];

    // Relasi ke tabel projects
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function transaksiBarang()
    {
        return $this->hasMany(TransaksiBarang::class, 'purchase_order_id');
    }
}