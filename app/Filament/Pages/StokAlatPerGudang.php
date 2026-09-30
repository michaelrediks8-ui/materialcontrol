<?php

namespace App\Filament\Pages;

use App\Models\Gudang;
use App\Models\StokAlat;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StokAlatPerGudang extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Monitoring Alat';
    protected static ?string $navigationLabel = 'Stok Alat per Gudang';
    protected static ?string $title = 'Stok Alat per Gudang';
    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.stok-alat-per-gudang';

    public function table(Table $table): Table
    {
        return $table
            ->query(StokAlat::query()->with(['alat', 'gudang']))
            ->columns([
                TextColumn::make('gudang.nama_gudang')
                    ->label('Gudang')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('alat.kode_alat')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('alat.nama_alat')
                    ->label('Nama Alat')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('jumlah_baik')
                    ->label('Baik')
                    ->badge()
                    ->color('success')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('jumlah_rusak')
                    ->label('Rusak')
                    ->badge()
                    ->color('danger')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('total_milik')
                    ->label('Total')
                    ->state(fn (StokAlat $record) => $record->jumlah_baik + $record->jumlah_rusak)
                    ->alignCenter(),

                TextColumn::make('sedang_dipinjam')
                    ->label('Dipinjam')
                    ->state(function (StokAlat $record) {
                        return $record->alat?->sedangDipinjam($record->gudang_id) ?? 0;
                    })
                    ->badge()
                    ->color('warning')
                    ->alignCenter(),

                TextColumn::make('tersedia')
                    ->label('Bisa Dipinjam')
                    ->state(function (StokAlat $record) {
                        return $record->alat?->tersediaDiGudang($record->gudang_id) ?? 0;
                    })
                    ->weight('bold')
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('gudang_id')
                    ->label('Pilih Gudang')
                    ->options(fn () => Gudang::pluck('nama_gudang', 'id')),
            ])
            ->defaultSort('updated_at', 'desc');
    }
}