<?php

namespace App\Filament\Concerns;

use App\Support\HakAkses;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Pasang di Resource dengan `use MembatasiGudang;`
 * Aturannya dibaca dari config/hak_akses.php (lewat App\Support\HakAkses).
 */
trait MembatasiGudang
{
    public static function canCreate(): bool
    {
        return HakAkses::bolehBuat(static::class);
    }

    public static function canEdit(Model $record): bool
    {
        return HakAkses::bolehKelola(static::class, $record, 'edit');
    }

    public static function canDelete(Model $record): bool
    {
        return HakAkses::bolehKelola(static::class, $record, 'delete');
    }

    public static function canDeleteAny(): bool
    {
        return HakAkses::bolehIzin('hapus');
    }

    // Catatan: Resource yang punya getEloquentQuery() sendiri harus memanggil
    // HakAkses::batasiQuery(static::class, parent::getEloquentQuery()) di dalamnya.
    public static function getEloquentQuery(): Builder
    {
        return HakAkses::batasiQuery(static::class, parent::getEloquentQuery());
    }

    /** Hapus massal yang otomatis melewati data bukan milik gudang user */
    protected static function aksiHapusMassal(): DeleteBulkAction
    {
        return DeleteBulkAction::make()
            ->visible(fn () => static::canDeleteAny())
            ->action(function (Collection $records) {
                $terhapus = 0;
                $dilewati = 0;

                foreach ($records as $record) {
                    if (static::canDelete($record)) {
                        $record->delete();
                        $terhapus++;
                    } else {
                        $dilewati++;
                    }
                }

                Notification::make()
                    ->title("{$terhapus} data dihapus" . ($dilewati ? ", {$dilewati} dilewati (bukan gudang Anda)" : ''))
                    ->success()
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }
}
