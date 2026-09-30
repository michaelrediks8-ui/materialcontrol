<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TabungGasResource\Pages;
use App\Models\TabungGas;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TabungGasResource extends Resource
{
    protected static ?string $model = TabungGas::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static ?string $navigationGroup = 'Stok Material';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Tabung Gas (Samator)';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('vendor_id')
                    ->label('Vendor / Supplier')
                    ->relationship('vendor', 'nama_vendor')
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\Select::make('jenis_gas_id')
                    ->label('Jenis Gas')
                    ->relationship('jenisGas', 'nama_gas')
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\TextInput::make('nomor_serial')
                    ->label('Nomor Barcode / Serial Tabung')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->placeholder('Contoh: 5450018'),

                Forms\Components\TextInput::make('keperluan')
                    ->label('Keperluan / Penggunaan')
                    ->placeholder('Contoh: Pengelasan Argon M4'),

                Forms\Components\DateTimePicker::make('tanggal_masuk')
                    ->label('Tanggal & Jam Masuk')
                    ->default(now())
                    ->required(),

                Forms\Components\DateTimePicker::make('batas_kembali')
                    ->label('Batas Pemulangan (60 Hari)')
                    ->default(now()->addDays(60)),

                Forms\Components\Select::make('status')
                    ->label('Status Tabung')
                    ->options([
                        'Dipinjam / Di Lapangan' => 'Dipinjam / Di Lapangan',
                        'Sudah Dikembalikan' => 'Sudah Dikembalikan',
                        'Kosong' => 'Kosong',
                    ])
                    ->default('Dipinjam / Di Lapangan')
                    ->required(),

                Forms\Components\Select::make('project_id')
                    ->label('Project / Lokasi Penempatan')
                    ->relationship('project', 'nama_project')
                    ->searchable()
                    ->preload(),

                Forms\Components\Textarea::make('catatan')
                    ->label('Catatan Item')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nomor_serial')
                    ->label('Serial Tabung')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vendor.nama_vendor')
                    ->label('Vendor')
                    ->sortable(),
                Tables\Columns\TextColumn::make('jenisGas.nama_gas')
                    ->label('Jenis Gas')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Dipinjam / Di Lapangan' => 'warning',
                        'Sudah Dikembalikan' => 'success',
                        'Kosong' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('batas_kembali')
                    ->label('Batas Pemulangan')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTabungGases::route('/'),
            'create' => Pages\CreateTabungGas::route('/create'),
            'edit' => Pages\EditTabungGas::route('/{record}/edit'),
        ];
    }
}