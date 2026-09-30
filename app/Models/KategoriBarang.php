<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KategoriBarang extends Model
{
    use HasFactory;

    // Hapus $fillable dan ganti dengan $guarded yang dikosongkan
    protected $guarded = []; 
}