<?php

namespace App\Filament\Resources\TransferGudangResource\Pages;

use App\Filament\Resources\TransferGudangResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTransferGudang extends EditRecord
{
    protected static string $resource = TransferGudangResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['tanggal']);

        return $data;
    }

    protected function afterSave(): void
    {
        \App\Support\StokRebuilder::rebuild();
    }
}