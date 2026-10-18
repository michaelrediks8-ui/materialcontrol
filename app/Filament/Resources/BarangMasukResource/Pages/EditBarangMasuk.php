<?php

namespace App\Filament\Resources\BarangMasukResource\Pages;

use App\Filament\Resources\BarangMasukResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBarangMasuk extends EditRecord
{
    protected static string $resource = BarangMasukResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Hapus field helper header_project_id agar tidak ikut disimpan ke database
        unset($data['header_project_id']);

        // Jika langsung drop ke project, pastikan tujuan_id bernilai null
        if (($data['tujuan_type'] ?? null) === 'project') {
            $data['tujuan_id'] = null;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        \App\Support\StokRebuilder::rebuild();
    }
}