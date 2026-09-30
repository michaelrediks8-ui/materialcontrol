<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JenisGas extends Model
{
    use HasFactory;

    // Mengunci nama tabel agar Laravel tidak mencari 'jenis_gases'
    protected $table = 'jenis_gas';

    protected $guarded = [];

    public function tabungGases()
    {
        return $this->hasMany(TabungGas::class, 'jenis_gas_id');
    }
}