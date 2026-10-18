<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TabungGasResource\Pages;
use App\Models\TabungGas;
use App\Models\Gudang; // 💡 Pastikan model Gudang di-import
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class TabungGasResource extends Resource
{
    protected static ?string $model = TabungGas::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static ?string $navigationGroup = 'Stok Material';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Manage Gas Samator';
    protected static ?string $modelLabel = 'Gas Samator';
    protected static ?string $pluralModelLabel = 'Gas Samator';

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

                // 💡 TAMBAHAN: Pilihan Gudang / Lokasi Penyimpanan Fisik
                Forms\Components\Select::make('gudang_id')
                    ->label('Gudang Penyimpanan / Posisi')
                    ->relationship('gudang', 'nama_gudang')
                    ->searchable()
                    ->preload()
                    ->placeholder('Pilih Gudang')
                    ->required(),

                Forms\Components\TextInput::make('no_barcode')
                    ->label('Nomor Barcode')
                    ->unique(ignoreRecord: true)
                    ->nullable()
                    ->placeholder('Contoh: BC-5450018'),

                Forms\Components\TextInput::make('nomor_serial')
                    ->label('Nomor Serial Tabung')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->placeholder('Contoh: SN-5450018'),

                Forms\Components\TextInput::make('keperluan')
                    ->label('Penggunaan')
                    ->placeholder('Input Keperluan'),

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
                    ->label('No. Serial')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('jenisGas.nama_gas')
                    ->label('Jenis Gas')
                    ->searchable()
                    ->sortable(),

                // 💡 TAMBAHAN: Kolom Gudang di Tabel
                Tables\Columns\TextColumn::make('gudang.nama_gudang')
                    ->label('Gudang')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('project.nama_project')
                    ->label('Lokasi/Project')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'warning' => 'Dipinjam / Di Lapangan',
                        'success' => 'Sudah Dikembalikan',
                        'danger' => 'Kosong',
                    ]),

                Tables\Columns\TextColumn::make('batas_kembali')
                    ->label('Batas Kembali')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([
                // Filter tambahan jika diperlukan
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('kembalikan')
                    ->label('Kembalikan')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->visible(fn (TabungGas $record) => $record->status === 'Dipinjam / Di Lapangan')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Pengembalian')
                    ->modalDescription('Apakah Anda yakin tabung gas ini sudah dikembalikan ke Gudang/Vendor?')
                    ->modalSubmitActionLabel('Ya, Kembalikan')
                    ->action(function (TabungGas $record) {
                        $record->update([
                            'status' => 'Sudah Dikembalikan',
                        ]);

                        Notification::make()
                            ->title('Berhasil Diupdate')
                            ->body('Status tabung berhasil diubah menjadi Sudah Dikembalikan.')
                            ->success()
                            ->send();
                    }),
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
            'index' => Pages\ListTabungGases::route('/'),
            'create' => Pages\CreateTabungGas::route('/create'),
            'edit' => Pages\EditTabungGas::route('/{record}/edit'),
        ];
    }
}