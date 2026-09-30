<?php

namespace App\Filament\Resources\PenerimaanGasResource\Pages;

use App\Filament\Resources\PenerimaanGasResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPenerimaanGases extends ListRecords
{
    protected static string $resource = PenerimaanGasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
