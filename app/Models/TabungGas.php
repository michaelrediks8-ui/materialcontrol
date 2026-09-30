<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TabungGas extends Model
{
    use HasFactory;

    // Mengunci nama tabel agar Laravel tidak mencari 'tabung_gases'
    protected $table = 'tabung_gas';

    protected $guarded = [];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function jenisGas()
    {
        return $this->belongsTo(JenisGas::class, 'jenis_gas_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}