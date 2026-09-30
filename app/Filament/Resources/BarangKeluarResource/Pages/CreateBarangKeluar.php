<?php

namespace App\Filament\Resources\BarangKeluarResource\Pages;

use App\Filament\Resources\BarangKeluarResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBarangKeluar extends CreateRecord
{
    protected static string $resource = BarangKeluarResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Jika tujuan_type bernilai 'purchase_order', ubah otomatis menjadi 'project'
        // agar sesuai dengan ENUM di database (hanya menerima 'gudang' atau 'project')
        if (isset($data['tujuan_type']) && $data['tujuan_type'] === 'purchase_order') {
            $data['tujuan_type'] = 'project';
        }

        return $data;
    }
}