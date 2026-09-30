<?php

namespace App\Filament\Resources\TransaksiBarangResource\Pages;

use App\Filament\Resources\TransaksiBarangResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTransaksiBarang extends CreateRecord
{
    protected static string $resource = TransaksiBarangResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        return $data;
    }
}
