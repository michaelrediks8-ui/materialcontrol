<?php

namespace App\Filament\Resources\PeminjamanAlatResource\Pages;

use App\Filament\Resources\PeminjamanAlatResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPeminjamanAlats extends ListRecords
{
    protected static string $resource = PeminjamanAlatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
