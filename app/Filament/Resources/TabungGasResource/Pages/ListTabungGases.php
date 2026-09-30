<?php

namespace App\Filament\Resources\TabungGasResource\Pages;

use App\Filament\Resources\TabungGasResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTabungGases extends ListRecords
{
    protected static string $resource = TabungGasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
