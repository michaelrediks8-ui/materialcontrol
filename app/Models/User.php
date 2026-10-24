<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'no_hp',
        'is_active',
        'can_input',
        'can_keluar',
        'can_delete',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'can_input' => 'boolean',
            'can_keluar' => 'boolean',
            'can_delete' => 'boolean',
        ];
    }

    // Mengontrol hak akses login ke panel Filament (hanya yang aktif)
    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) $this->is_active;
    }

    // ---------- Relasi ----------

    public function gudangs(): BelongsToMany
    {
        return $this->belongsToMany(Gudang::class, 'gudang_user');
    }

    // ---------- Helper role & izin ----------

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    // Boleh input data (registrasi, barang masuk, dll.)
    public function bolehInput(): bool
    {
        return $this->isAdmin() || $this->can_input;
    }

    // Boleh mengeluarkan barang/alat (transfer, pemakaian, peminjaman)
    public function bolehKeluar(): bool
    {
        return $this->isAdmin() || $this->can_keluar;
    }

    // Boleh menghapus data
    public function bolehHapus(): bool
    {
        return $this->isAdmin() || $this->can_delete;
    }

    // ---------- Helper gudang ----------

    // Daftar ID gudang yang boleh diakses. null = semua gudang (admin)
    public function gudangIds(): ?array
    {
        if ($this->isAdmin()) {
            return null;
        }

        return $this->gudangs()->pluck('gudangs.id')->map(fn ($id) => (int) $id)->all();
    }

    public function bolehAksesGudang(int|string|null $gudangId): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if (empty($gudangId)) {
            return false;
        }

        return in_array((int) $gudangId, $this->gudangIds(), true);
    }
}