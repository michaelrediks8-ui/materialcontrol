<?php

namespace App\Filament\Resources\JenisGasResource\Pages;

use App\Filament\Resources\JenisGasResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJenisGas extends ListRecords
{
    protected static string $resource = JenisGasResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
