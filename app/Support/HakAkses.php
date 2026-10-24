<?php

namespace App\Support;

use App\Models\Gudang;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * Logika pusat hak akses. Aturan per Resource dibaca dari config/hak_akses.php.
 */
class HakAkses
{
    public static function user(): ?User
    {
        /** @var User|null $user */
        $user = auth()->user();

        return $user;
    }

    protected static function aturan(string $resource): array
    {
        $aturan = config("hak_akses.resources.{$resource}");

        if ($aturan === null) {
            throw new RuntimeException("Resource {$resource} belum didaftarkan di config/hak_akses.php");
        }

        return $aturan;
    }

    // =====================================================================
    // IZIN
    // =====================================================================

    /** Cek izin user: 'input' | 'keluar' | 'hapus' */
    public static function bolehIzin(string $izin): bool
    {
        $user = static::user();

        if (! $user) {
            return false;
        }

        return match ($izin) {
            'keluar' => $user->bolehKeluar(),
            'hapus' => $user->bolehHapus(),
            default => $user->bolehInput(),
        };
    }

    /** Izin yang dibutuhkan untuk sebuah nilai kolom (contoh: jenis_mutasi = 'transfer' => 'keluar') */
    public static function izinUntukNilai(string $resource, ?string $nilai): string
    {
        $izin = static::aturan($resource)['izin'] ?? 'input';

        if (is_array($izin)) {
            return $izin['peta'][$nilai] ?? ($izin['default'] ?? 'input');
        }

        return $izin;
    }

    /** Izin yang dibutuhkan untuk sebuah record */
    public static function izinRecord(string $resource, Model $record): string
    {
        $izin = static::aturan($resource)['izin'] ?? 'input';

        if (is_array($izin)) {
            return static::izinUntukNilai($resource, (string) $record->{$izin['dari_kolom']});
        }

        return $izin;
    }

    /** Boleh membuat data baru di Resource ini? */
    public static function bolehBuat(string $resource): bool
    {
        $user = static::user();

        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $izin = static::aturan($resource)['izin'] ?? 'input';

        if (is_array($izin)) {
            $semua = array_unique([...array_values($izin['peta'] ?? []), $izin['default'] ?? 'input']);

            foreach ($semua as $i) {
                if (static::bolehIzin($i)) {
                    return true;
                }
            }

            return false;
        }

        return static::bolehIzin($izin);
    }

    /** Boleh melakukan sebuah izin tertentu di sebuah gudang? (untuk aksi khusus, mis. pengembalian alat) */
    public static function bolehIzinDiGudang(string $izin, int|string|null $gudangId): bool
    {
        $user = static::user();

        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return static::bolehIzin($izin) && $user->bolehAksesGudang($gudangId);
    }

    // =====================================================================
    // GUDANG PEMILIK RECORD
    // =====================================================================

    public static function gudangPemilik(string $resource, Model $record): ?int
    {
        $gudang = static::aturan($resource)['gudang'] ?? ['mode' => 'asal_tujuan'];

        if (($gudang['mode'] ?? 'asal_tujuan') === 'kolom') {
            $nilai = $record->{$gudang['kolom']} ?? null;

            return $nilai ? (int) $nilai : null;
        }

        if ($record->asal_type === 'gudang' && $record->asal_id) {
            return (int) $record->asal_id;
        }

        if ($record->tujuan_type === 'gudang' && $record->tujuan_id) {
            return (int) $record->tujuan_id;
        }

        return null; // contoh: barang masuk langsung ke project (tanpa gudang)
    }

    /**
     * Boleh mengelola (edit / hapus) sebuah record?
     * Data tanpa gudang pemilik hanya bisa dikelola admin.
     */
    public static function bolehKelola(string $resource, Model $record, string $aksi): bool
    {
        $user = static::user();

        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $izin = $aksi === 'delete'
            ? $user->bolehHapus()
            : static::bolehIzin(static::izinRecord($resource, $record));

        if (! $izin) {
            return false;
        }

        $pemilik = static::gudangPemilik($resource, $record);

        return $pemilik !== null && $user->bolehAksesGudang($pemilik);
    }

    // =====================================================================
    // FILTER DATA (dipakai di getEloquentQuery milik Resource, BUKAN global scope,
    // supaya StokRebuilder / AlatStokRebuilder tetap membaca semua data)
    // =====================================================================

    public static function batasiQuery(string $resource, Builder $query): Builder
    {
        $user = static::user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isAdmin()) {
            return $query;
        }

        $ids = $user->gudangIds() ?? [];
        $model = $query->getModel();
        $gudang = static::aturan($resource)['gudang'] ?? ['mode' => 'asal_tujuan'];

        if (($gudang['mode'] ?? 'asal_tujuan') === 'kolom') {
            return $query->whereIn($model->qualifyColumn($gudang['kolom']), $ids);
        }

        $asalType = $model->qualifyColumn('asal_type');
        $asalId = $model->qualifyColumn('asal_id');
        $tujuanType = $model->qualifyColumn('tujuan_type');
        $tujuanId = $model->qualifyColumn('tujuan_id');

        // Tampil: keluar dari gudangnya, masuk ke gudangnya, atau data tanpa gudang sama sekali
        // (contoh: barang masuk langsung ke project) agar data yang dia input sendiri tidak hilang.
        return $query->where(function (Builder $q) use ($ids, $asalType, $asalId, $tujuanType, $tujuanId) {
            $q->where(fn (Builder $x) => $x->where($asalType, 'gudang')->whereIn($asalId, $ids))
                ->orWhere(fn (Builder $x) => $x->where($tujuanType, 'gudang')->whereIn($tujuanId, $ids))
                ->orWhere(function (Builder $x) use ($asalType, $tujuanType) {
                    $x->where(fn (Builder $y) => $y->whereNull($asalType)->orWhere($asalType, '!=', 'gudang'))
                        ->where(fn (Builder $y) => $y->whereNull($tujuanType)->orWhere($tujuanType, '!=', 'gudang'));
                });
        });
    }

    // =====================================================================
    // DROPDOWN GUDANG
    // =====================================================================

    /** Batasi query Gudang ke gudang milik user (admin = semua) */
    public static function batasiQueryGudang(Builder $query): Builder
    {
        $user = static::user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        $ids = $user->gudangIds();

        if ($ids === null) {
            return $query;
        }

        return $query->whereIn($query->getModel()->qualifyColumn('id'), $ids);
    }

    /** id => nama, hanya gudang milik user (admin = semua) */
    public static function gudangSaya(): array
    {
        return static::batasiQueryGudang(Gudang::query()->whereNotNull('nama_gudang'))
            ->orderBy('nama_gudang')
            ->pluck('nama_gudang', 'id')
            ->map(fn ($nama) => (string) $nama)
            ->toArray();
    }

    /** id => nama, semua gudang (untuk tujuan transfer) */
    public static function gudangSemua(): array
    {
        return Gudang::query()
            ->whereNotNull('nama_gudang')
            ->orderBy('nama_gudang')
            ->pluck('nama_gudang', 'id')
            ->map(fn ($nama) => (string) $nama)
            ->toArray();
    }

    /** Aturan validasi (server-side): gudang yang dipilih harus milik user. Pakai di ->rules([...]) */
    public static function ruleGudangSaya(): Closure
    {
        return fn (): Closure => function (string $attribute, $value, Closure $fail): void {
            if (blank($value)) {
                return;
            }

            if (! self::user()?->bolehAksesGudang($value)) {
                $fail('Anda tidak punya akses ke gudang yang dipilih.');
            }
        };
    }
}
