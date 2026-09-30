<?php

namespace App\Filament\Resources\JenisGasResource\Pages;

use App\Filament\Resources\JenisGasResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJenisGas extends EditRecord
{
    protected static string $resource = JenisGasResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
