<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VendorResource\Pages;
use App\Models\Vendor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VendorResource extends Resource
{
    protected static ?string $model = Vendor::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationLabel = 'Supplier';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('kode_vendor')
    ->label('Kode Vendor')
    ->default(function () {
        $prefix = 'VND-';
        
        // Cari record terakhir dari database
        $lastRecord = \App\Models\Vendor::where('kode_vendor', 'like', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = 1;

        if ($lastRecord && !empty($lastRecord->kode_vendor)) {
            // Ambil angka dari kode terakhir (misal VND-0105 -> 105)
            $rawNumber = preg_replace('/[^0-9]/', '', $lastRecord->kode_vendor);
            if (is_numeric($rawNumber) && (int)$rawNumber > 0) {
                $nextNumber = ((int)$rawNumber) + 1;
            }
        }

        // Return VND-0106 dan langsung tampilkan di form
        return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    })
    ->maxLength(255)
    ->disabledOn('edit') // Cegah pengubahan saat halaman edit
    ->required(), // Karena sudah pasti ada isinya, kita buat required

                Forms\Components\TextInput::make('nama_vendor')
                    ->label('Nama Vendor / Supplier')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('kontak') 
    ->label('No. Telepon / WA')
    ->tel()
    ->maxLength(255)
    ->nullable(),

                Forms\Components\Textarea::make('alamat')
                    ->label('Alamat')
                    ->rows(3)
                    ->columnSpanFull()
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kode_vendor')
                    ->label('Kode Vendor')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('nama_vendor')
                    ->label('Nama Vendor')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('kontak') 
    ->label('No. Telepon')
    ->searchable()
    ->placeholder('-'),

                Tables\Columns\TextColumn::make('alamat')
                    ->label('Alamat')
                    ->limit(50)
                    ->placeholder('-'),
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
            'index' => Pages\ListVendors::route('/'),
            'create' => Pages\CreateVendor::route('/create'),
            'edit' => Pages\EditVendor::route('/{record}/edit'),
        ];
    }
}