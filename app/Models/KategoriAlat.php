<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KategoriAlat extends Model
{
    use HasFactory;

    // 💡 Tambahkan baris ini agar form Filament bisa menyimpan data ke semua kolom
    protected $guarded = [];
}