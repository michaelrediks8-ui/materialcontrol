<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KategoriAlatResource\Pages;
use App\Models\KategoriAlat;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class KategoriAlatResource extends Resource
{
    protected static ?string $model = KategoriAlat::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationLabel = 'Kategori Alat';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()->schema([
                    Forms\Components\TextInput::make('kode_kategori')
                        ->label('Kode Kategori')
                        ->placeholder('Contoh: KGT-01')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('nama_kategori')
                        ->label('Nama Kategori Alat')
                        ->placeholder('Contoh: Power Tools')
                        ->required()
                        ->maxLength(255),
                ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kode_kategori')
                    ->label('Kode Kategori')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nama_kategori')
                    ->label('Nama Kategori')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListKategoriAlats::route('/'),
            'create' => Pages\CreateKategoriAlat::route('/create'),
            'edit' => Pages\EditKategoriAlat::route('/{record}/edit'),
        ];
    }
}