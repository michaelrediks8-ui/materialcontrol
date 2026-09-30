<?php

namespace App\Filament\Resources\TransferGudangResource\Pages;

use App\Filament\Resources\TransferGudangResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTransferGudangs extends ListRecords
{
    protected static string $resource = TransferGudangResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}