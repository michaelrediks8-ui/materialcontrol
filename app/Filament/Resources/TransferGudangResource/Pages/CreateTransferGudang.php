<?php

namespace App\Filament\Resources\TransferGudangResource\Pages;

use App\Filament\Resources\TransferGudangResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTransferGudang extends CreateRecord
{
    protected static string $resource = TransferGudangResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        
        unset($data['tanggal']);

        // Pastikan tanggal_transaksi terisi
        if (empty($data['tanggal_transaksi'])) {
            $data['tanggal_transaksi'] = now();
        }

        return $data;
    }
}