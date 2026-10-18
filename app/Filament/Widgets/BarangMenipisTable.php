<?php

namespace App\Filament\Widgets;

use App\Models\StokBarang;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class BarangMenipisTable extends BaseWidget
{
    protected static ?string $heading = 'Barang Perlu Direstock';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                StokBarang::query()
                    ->with(['barang', 'gudang'])
                    ->where('stok', '<=', 10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('gudang.nama_gudang')
                    ->label('Gudang')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('barang.kode_barang')
                    ->label('Kode')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('barang.nama_barang')
                    ->label('Nama Barang'),

                Tables\Columns\TextColumn::make('stok')
                   ->label('Stok')
                   ->formatStateUsing(function ($state, $record) {
                    $val = (float) $state;
                    $formatted = number_format($val, 2, ',', '.');
                    if (str_contains($formatted, ',')) {
                      $formatted = rtrim($formatted, '0');
                      $formatted = rtrim($formatted, ',');}
                      $satuan = $record->barang?->satuan?->nama_satuan ?? 'Pcs';                            
                    return "{$formatted} {$satuan}";})    
                   ->weight('bold')
                   ->color(fn ($state) => $state <= 0 ? 'danger' : 'warning'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->state(fn ($record) => $record->stok <= 0 ? 'Habis' : 'Menipis')
                    ->color(fn ($state) => $state === 'Habis' ? 'danger' : 'warning'),
            ])
            ->defaultSort('stok', 'asc')
            ->paginated([5, 10, 25]);
    }
}