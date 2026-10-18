<?php

namespace App\Filament\Resources\PeminjamanAlatResource\Pages;

use App\Filament\Resources\PeminjamanAlatResource;
use App\Models\Alat;
use App\Models\PeminjamanAlat;
use App\Models\StokAlat;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreatePeminjamanAlat extends CreateRecord
{
    protected static string $resource = PeminjamanAlatResource::class;

    protected int $jumlahAlat = 0;

    /**
     * Satu submit form = banyak baris peminjaman_alats
     * (satu per item repeater, semuanya dengan peminjam, jadwal, dan keperluan yang sama).
     */
    protected function handleRecordCreation(array $data): Model
    {
        $items = array_values($data['items'] ?? []);
        unset($data['items']);

        return DB::transaction(function () use ($items, $data) {
            // Cek ulang stok di dalam transaksi (baris stok dikunci) supaya dua admin
            // yang meminjam bersamaan tidak sama-sama lolos validasi form.
            $perKombinasi = [];

            foreach ($items as $item) {
                $key = $item['alat_id'] . '|' . $item['gudang_id'];
                $perKombinasi[$key] = ($perKombinasi[$key] ?? 0) + (int) $item['qty'];
            }

            foreach ($perKombinasi as $key => $total) {
                [$alatId, $gudangId] = array_map('intval', explode('|', $key));

                StokAlat::where('alat_id', $alatId)
                    ->where('gudang_id', $gudangId)
                    ->lockForUpdate()
                    ->first();

                $alat = Alat::findOrFail($alatId);
                $sisa = $alat->tersediaDiGudang($gudangId);

                if ($total > $sisa) {
                    throw ValidationException::withMessages([
                        'data.items' => "Stok {$alat->nama_alat} tidak mencukupi. Total diminta {$total}, tersedia {$sisa} {$alat->satuan}.",
                    ]);
                }
            }

            $pertama = null;

            foreach ($items as $item) {
                $record = PeminjamanAlat::create($data + [
                    'alat_id'   => $item['alat_id'],
                    'gudang_id' => $item['gudang_id'],
                    'qty'       => $item['qty'],
                ]);

                $pertama ??= $record;
                $this->jumlahAlat++;
            }

            return $pertama;
        });
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title($this->jumlahAlat > 1
                ? "{$this->jumlahAlat} alat berhasil dicatat dipinjam"
                : 'Peminjaman berhasil dicatat');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}