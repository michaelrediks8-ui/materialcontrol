<?php

namespace App\Filament\Resources\BarangMasukResource\Pages;

use App\Filament\Resources\BarangMasukResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBarangMasuk extends CreateRecord
{
    protected static string $resource = BarangMasukResource::class;

    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['jenis_transaksi'] = 'masuk';
        $data['asal_type'] = 'vendor';
        return $data;
    }

    
}