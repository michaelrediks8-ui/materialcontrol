<?php

namespace App\Filament\Resources\TabungGasResource\Pages;

use App\Filament\Resources\TabungGasResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTabungGas extends EditRecord
{
    protected static string $resource = TabungGasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
