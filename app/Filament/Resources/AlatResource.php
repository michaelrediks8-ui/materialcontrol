<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AlatResource\Pages;
use App\Models\Alat;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AlatResource extends Resource
{
    protected static ?string $model = Alat::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationLabel = 'Master Alat';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()->schema([
                    Forms\Components\TextInput::make('kode_alat')
                        ->label('Kode Alat')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->default(fn (string $operation) => $operation === 'create' ? Alat::generateNextCode() : null)
                        ->maxLength(255),

                    Forms\Components\TextInput::make('nama_alat')
                        ->label('Nama Alat')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\Select::make('kategori_alat_id')
                        ->label('Kategori Alat')
                        ->relationship('kategori', 'nama_kategori')
                        ->searchable()
                        ->preload(),

                    Forms\Components\TextInput::make('merk_catatan')
                        ->label('Merk / Catatan')
                        ->maxLength(255),

                    Forms\Components\Select::make('satuan')
                        ->label('Satuan')
                        ->options(fn (): array => \App\Models\Satuan::query()
                            ->pluck('nama_satuan', 'nama_satuan')
                            ->toArray())
                        ->searchable()
                        ->preload()
                        ->nullable(),

                    // Catatan: lokasi_saat_ini, jumlah_total, dan jumlah_rusak sudah TIDAK
                    // diisi manual lagi. Angka stok sekarang murni hasil hitungan otomatis
                    // dari mutasi_alats lewat AlatStokRebuilder, per gudang (tabel stok_alats).
                ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kode_alat')
                    ->label('Kode Alat')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('nama_alat')
                    ->label('Nama Alat')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('kategori.nama_kategori')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('merk_catatan')
                    ->label('Merk / Catatan')
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('total_baik')
                    ->label('Total Baik')
                    ->state(fn (Alat $record) => $record->totalBaik())
                    ->badge()
                    ->color('success')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('total_rusak')
                    ->label('Total Rusak')
                    ->state(fn (Alat $record) => $record->totalRusak())
                    ->badge()
                    ->color('danger')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('sedang_dipinjam')
                    ->label('Dipinjam')
                    ->state(fn (Alat $record) => $record->sedangDipinjam())
                    ->badge()
                    ->color('warning')
                    ->alignCenter(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAlats::route('/'),
            'create' => Pages\CreateAlat::route('/create'),
            'edit' => Pages\EditAlat::route('/{record}/edit'),
        ];
    }
}