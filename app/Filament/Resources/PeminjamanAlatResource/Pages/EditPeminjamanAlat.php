<?php

namespace App\Filament\Resources\PeminjamanAlatResource\Pages;

use App\Filament\Resources\PeminjamanAlatResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPeminjamanAlat extends EditRecord
{
    protected static string $resource = PeminjamanAlatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
