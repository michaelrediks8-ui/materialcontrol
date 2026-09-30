<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DepartemenResource\Pages;
use App\Models\Departemen;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DepartemenResource extends Resource
{
    protected static ?string $model = Departemen::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationLabel = 'Departemen / Divisi';
    protected static ?string $slug = 'departemens';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Data Departemen / Divisi')
                    ->schema([
                        Forms\Components\TextInput::make('kode_departemen')
                            ->label('Kode Departemen')
                            ->placeholder('Contoh: MC, ELE, CIV')
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('nama_departemen')
                            ->label('Nama Departemen / Tim')
                            ->placeholder('Contoh: Material Control, Electrical, Sipil')
                            ->required(),

                        Forms\Components\TextInput::make('keterangan')
                            ->label('Keterangan')
                            ->placeholder('Deskripsi singkat divisi')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kode_departemen')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('nama_departemen')
                    ->label('Nama Departemen / Tim')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->placeholder('-'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDepartemens::route('/'),
            'create' => Pages\CreateDepartemen::route('/create'),
            'edit' => Pages\EditDepartemen::route('/{record}/edit'),
        ];
    }
}