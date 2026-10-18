<?php

namespace App\Filament\Pages;

use App\Models\Gudang;
use App\Models\StokBarang;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;


use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class KartuStockGudang extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationGroup = 'Stok Material';
    protected static ?string $navigationLabel = 'Kartu Stock per Gudang';
    protected static ?string $title = 'Kartu Stock Gudang';
    protected static ?int $navigationSort = 5;

    protected static string $view = 'filament.pages.kartu-stock-gudang';


    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->label('Export Excel')
                ->color('success')
                ->icon('heroicon-o-document-arrow-down')
                ->exports([
                    ExcelExport::make()
                        ->fromTable()
                        ->withFilename('Kartu_Stock_Gudang_' . date('Y-m-d')),
                ]),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(StokBarang::query()->with(['barang.satuan', 'gudang']))
            ->columns([
                TextColumn::make('gudang.nama_gudang')
                    ->label('Gudang')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('barang.kode_barang')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('barang.nama_barang')
                    ->label('Nama Barang')
                    ->searchable()
                    ->sortable(),

                // STOK UTAMA
                Tables\Columns\TextColumn::make('stok')
                    ->label('Stok Utama')
                    ->formatStateUsing(function ($state, $record) {
                        $val = (float) $state;
                        $formatted = number_format($val, 2, ',', '.');

                        // Hapus nol trailing HANYA di bagian desimal (setelah koma)
                        if (str_contains($formatted, ',')) {
                            $formatted = rtrim($formatted, '0');
                            $formatted = rtrim($formatted, ',');
                        }

                        $namaSatuan = $record->barang?->satuan?->nama_satuan ?? 'Pcs';
                        
                        return "{$formatted} {$namaSatuan}";
                    })
                    ->weight('bold')
                    ->sortable(),

                // KOLOM STATUS
                TextColumn::make('status')
                    ->label('Status Stok')
                    ->badge()
                    ->state(function ($record) {
                        $stok = (float) $record->stok;

                        if ($stok <= 0) {
                            return 'Habis / Minus';
                        } elseif ($stok <= 10) {
                            return 'Menipis';
                        }

                        return 'Aman';
                    })
                    ->color(function (string $state): string {
                        return match ($state) {
                            'Habis / Minus' => 'danger',
                            'Menipis' => 'warning',
                            'Aman' => 'success',
                            default => 'gray',
                        };
                    }),
            ])
            ->filters([
                SelectFilter::make('gudang_id')
                    ->label('Pilih Gudang')
                    ->options(Gudang::pluck('nama_gudang', 'id')),
            ])
         
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->label('Export Terpilih')
                        ->exports([
                            ExcelExport::make()
                                ->fromTable()
                                ->withFilename('Kartu_Stock_Terpilih_' . date('Y-m-d')),
                        ]),
                ]),
            ])
            ->defaultSort('updated_at', 'desc');
    }
}