<?php

namespace App\Filament\Resources\KategoriAlatResource\Pages;

use App\Filament\Resources\KategoriAlatResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKategoriAlats extends ListRecords
{
    protected static string $resource = KategoriAlatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
