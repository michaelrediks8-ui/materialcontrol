<?php

namespace App\Filament\Resources\MutasiAlatResource\Pages;

use App\Filament\Resources\MutasiAlatResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMutasiAlat extends EditRecord
{
    protected static string $resource = MutasiAlatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
