<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KaryawanResource\Pages;
use App\Models\Karyawan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class KaryawanResource extends Resource
{
    protected static ?string $model = Karyawan::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationLabel = 'Karyawan & Tim';
    protected static ?string $slug = 'karyawans';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Karyawan / Mandor')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
    Forms\Components\TextInput::make('nik')
        ->label('NIK / ID Karyawan')
        ->placeholder('Contoh: KRY-001 (Opsional)')
        ->nullable() // 
        ->unique(ignoreRecord: true), 
                            Forms\Components\TextInput::make('nama')
                                ->label('Nama Lengkap')
                                ->required()
                                ->placeholder('Contoh: Pak Budi Mandor'),
                        ]),

                        Forms\Components\Grid::make(3)->schema([
                            // Dropdown Dinamis dari Master Tabel Departemen + Modal Quick Create (+)
                            Forms\Components\Select::make('departemen_id')
                                ->label('Departemen / Tim')
                                ->relationship('departemen', 'nama_departemen')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->createOptionForm([
                                    Forms\Components\TextInput::make('nama_departemen')
                                        ->label('Nama Departemen / Tim Baru')
                                        ->placeholder('Contoh: Material Control')
                                        ->required(),
                                    Forms\Components\TextInput::make('kode_departemen')
                                        ->label('Kode Departemen')
                                        ->placeholder('Contoh: MC'),
                                ]),

                            Forms\Components\Select::make('jabatan')
    ->label('Jabatan / Peran')
    ->options([
        'Project Manager' => 'Project Manager (PM)',
        'Supervisor' => 'Supervisor / Site Engineer',
        'Mandor' => 'Mandor',
        'Pekerja / Tukang' => 'Pekerja / Tukang',
        'Driver' => 'Driver / Logistik',
        'Staff Admin' => 'Staff Admin / Material Control',
        'HSE / Safety' => 'HSE / Safety Officer',
    ])
    ->searchable()
    ->placeholder('Pilih atau ketik jabatan baru...')
    
    ->createOptionUsing(fn (string $value) => $value)
    ->default('Mandor')
    ->required(),

                            Forms\Components\TextInput::make('no_hp')
                                ->label('No. WhatsApp / HP')
                                ->tel()
                                ->placeholder('Contoh: 08123456789'),
                        ]),

                        Forms\Components\Textarea::make('alamat')
                            ->label('Alamat')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nik')
                    ->label('ID / NIK')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama Karyawan')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('departemen.nama_departemen')
                    ->label('Departemen / Tim')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('jabatan')
                    ->searchable(),

                Tables\Columns\TextColumn::make('no_hp')
                    ->label('No. WhatsApp')
                    ->icon('heroicon-o-phone')
                    ->searchable()
                    ->placeholder('-'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('departemen_id')
                    ->label('Filter Departemen')
                    ->relationship('departemen', 'nama_departemen'),
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
            'index' => Pages\ListKaryawans::route('/'),
            'create' => Pages\CreateKaryawan::route('/create'),
            'edit' => Pages\EditKaryawan::route('/{record}/edit'),
        ];
    }
}