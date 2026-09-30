<?php

namespace App\Filament\Resources\MutasiAlatResource\Pages;

use App\Filament\Resources\MutasiAlatResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMutasiAlats extends ListRecords
{
    protected static string $resource = MutasiAlatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
