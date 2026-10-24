<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jabatan extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected static function booted(): void
    {
        // Kolom lama karyawans.jabatan dijaga tetap sama dengan master,
        // supaya kode lain yang masih membaca kolom itu tidak rusak.
        static::updated(function (Jabatan $jabatan) {
            if ($jabatan->wasChanged('nama_jabatan')) {
                Karyawan::where('jabatan_id', $jabatan->id)->update(['jabatan' => $jabatan->nama_jabatan]);
            }
        });
    }

    public function karyawans(): HasMany
    {
        return $this->hasMany(Karyawan::class, 'jabatan_id');
    }
}