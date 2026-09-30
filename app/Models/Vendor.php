<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected static function booted()
    {
        static::creating(function ($vendor) {
            // Trim dan cek apakah kode_vendor kosong atau hanya berisi spasi/string kosong
            $kode = trim($vendor->kode_vendor ?? '');

            if (blank($kode)) {
                $prefix = 'VND-';

                // Ambil record terakhir berdasarkan ID terbesar
                $lastRecord = self::where('kode_vendor', 'like', "{$prefix}%")
                    ->orderBy('id', 'desc')
                    ->first();

                $nextNumber = 1;

                if ($lastRecord && !empty($lastRecord->kode_vendor)) {
                    // Ambil angka dari kode_vendor (misal VND-0105 -> 105)
                    $rawNumber = preg_replace('/[^0-9]/', '', $lastRecord->kode_vendor);
                    if (is_numeric($rawNumber) && (int)$rawNumber > 0) {
                        $nextNumber = ((int)$rawNumber) + 1;
                    }
                }

                // Generates VND-0106, VND-0107, dst.
                $vendor->kode_vendor = $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}