<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Karyawan extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected static function booted(): void
    {
        // Sinkronkan kolom lama `jabatan` (teks) dengan master jabatan.
        static::saving(function (Karyawan $karyawan) {
            if ($karyawan->isDirty('jabatan_id')) {
                $karyawan->jabatan = $karyawan->jabatan_id
                    ? (Jabatan::whereKey($karyawan->jabatan_id)->value('nama_jabatan') ?? '')
                    : '';
            }
        });
    }

    public function departemen(): BelongsTo
    {
        return $this->belongsTo(Departemen::class, 'departemen_id');
    }

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class, 'jabatan_id');
    }

    public function peminjaman()
    {
        return $this->hasMany(PeminjamanAlat::class);
    }

    /**
     * Nama jabatan berdasarkan NAMA karyawan (dipakai PDF surat jalan dll).
     */
    public static function jabatanDari(?string $nama): ?string
    {
        if (blank($nama)) {
            return null;
        }

        $karyawan = static::query()->with('jabatan')->where('nama', $nama)->first();

        return $karyawan?->getRelation('jabatan')?->nama_jabatan
            ?: ($karyawan?->getRawOriginal('jabatan') ?: null);
    }
}