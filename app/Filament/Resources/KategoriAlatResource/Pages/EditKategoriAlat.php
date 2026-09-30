<?php

namespace App\Filament\Resources\KategoriAlatResource\Pages;

use App\Filament\Resources\KategoriAlatResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKategoriAlat extends EditRecord
{
    protected static string $resource = KategoriAlatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
