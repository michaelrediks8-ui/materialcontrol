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

        // Hapus field helper header_project_id agar tidak ikut disimpan ke database
        unset($data['header_project_id']);

        // Jika langsung drop ke project, pastikan tujuan_id bernilai null
        if (($data['tujuan_type'] ?? null) === 'project') {
            $data['tujuan_id'] = null;
        }

        return $data;
    }
}