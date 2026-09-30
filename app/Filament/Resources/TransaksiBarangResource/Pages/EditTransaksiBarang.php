<?php

namespace App\Filament\Resources\TransaksiBarangResource\Pages;

use App\Filament\Resources\TransaksiBarangResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTransaksiBarang extends EditRecord
{
    protected static string $resource = TransaksiBarangResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function afterSave(): void
    {
        \App\Support\StokRebuilder::rebuild();
    }
}