<?php

namespace App\Filament\Resources;
use App\Filament\Resources\GudangResource\Pages;
use App\Models\Gudang;
use App\Models\Karyawan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GudangResource extends Resource
{
    protected static ?string $model = Gudang::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationLabel = 'Gudang';
    protected static ?string $pluralModelLabel = 'Gudang';
    protected static ?string $modelLabel = 'Gudang';
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('kode_gudang')
                ->label('Kode Gudang')
                ->placeholder('Contoh: GDG-M4')
                ->unique(ignoreRecord: true)
                ->maxLength(255)
                ->nullable(),

            Forms\Components\TextInput::make('nama_gudang')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),

            Forms\Components\TextInput::make('alamat_lokasi')
                ->label('Alamat / Lokasi')
                ->maxLength(255)
                ->nullable(),

            Forms\Components\Select::make('pic_gudang') 
                ->label('PIC Gudang')
                ->options(\App\Models\Karyawan::pluck('nama', 'nama'))
                ->searchable()
                ->preload()
                ->placeholder('Pilih Karyawan')
                ->nullable(), 

            Forms\Components\TextInput::make('keterangan')
                ->maxLength(255)
                ->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('kode_gudang')
                ->label('Kode')
                ->searchable()
                ->placeholder('-'),
            Tables\Columns\TextColumn::make('nama_gudang')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('alamat_lokasi')
                ->label('Alamat')
                ->searchable()
                ->placeholder('-'),
            Tables\Columns\TextColumn::make('pic_gudang')
                ->label('PIC')
                ->searchable()
                ->placeholder('-'),
            Tables\Columns\TextColumn::make('keterangan')->placeholder('-'),
        ])->actions([Tables\Actions\EditAction::make()])
          ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGudangs::route('/'),
            'create' => Pages\CreateGudang::route('/create'),
            'edit' => Pages\EditGudang::route('/{record}/edit'),
        ];
    }
}