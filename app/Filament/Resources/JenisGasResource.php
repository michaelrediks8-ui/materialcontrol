<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JenisGasResource\Pages;
use App\Models\JenisGas;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class JenisGasResource extends Resource
{
    protected static ?string $model = JenisGas::class;

    protected static ?string $navigationIcon = 'heroicon-o-fire';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationLabel = 'Jenis Gas';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nama_gas')
                    ->label('Nama Jenis Gas')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->placeholder('Contoh: OKSIGEN, ARGON, ACETYLENE'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_gas')
                    ->label('Nama Jenis Gas')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJenisGas::route('/'),
            'create' => Pages\CreateJenisGas::route('/create'),
            'edit' => Pages\EditJenisGas::route('/{record}/edit'),
        ];
    }
}