<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MutasiAlatResource\Pages;
use App\Models\Gudang;
use App\Models\Karyawan;
use App\Models\MutasiAlat;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MutasiAlatResource extends Resource
{
    protected static ?string $model = MutasiAlat::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'Monitoring Alat';
    protected static ?string $navigationLabel = 'Perpindahan Alat';
    protected static ?string $slug = 'mutasi-alat';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Mutasi Alat')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('no_surat_jalan')
                                ->label('No. Surat Jalan (Opsional)')
                                ->default(function () {
                                    $tanggal = now()->format('Ymd');
                                    $prefix = "SJ-ALT-{$tanggal}-";

                                    $lastRecord = MutasiAlat::where('no_surat_jalan', 'like', "{$prefix}%")
                                        ->orderBy('id', 'desc')
                                        ->first();

                                    $nextNumber = 1;
                                    if ($lastRecord && !empty($lastRecord->no_surat_jalan)) {
                                        $rawNumber = str_replace($prefix, '', $lastRecord->no_surat_jalan);
                                        if (is_numeric($rawNumber) && (int) $rawNumber > 0) {
                                            $nextNumber = ((int) $rawNumber) + 1;
                                        }
                                    }

                                    return $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
                                })
                                ->maxLength(255),

                            Forms\Components\DateTimePicker::make('tanggal_mutasi')
                                ->label('Tanggal & Jam')
                                ->default(now())
                                ->required(),
                        ]),

                        Forms\Components\Select::make('alat_id')
                            ->label('Pilih Alat Kerja')
                            ->relationship('alat', 'nama_alat')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "[{$record->kode_alat}] {$record->nama_alat}")
                            ->searchable(['kode_alat', 'nama_alat'])
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('jenis_mutasi')
                            ->label('Jenis Mutasi')
                            ->options([
                                'registrasi' => '📋 Registrasi / Stok Awal (Hasil Hitung Fisik)',
                                'transfer' => '🔄 Transfer Antar Gudang',
                                'rusak' => '❌ Tandai Rusak (di gudang yang sama)',
                                'perbaikan' => '✅ Tandai Selesai Diperbaiki',
                            ])
                            ->default('registrasi')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (callable $set) {
                                $set('asal_type', 'gudang');
                                $set('tujuan_type', 'gudang');
                                $set('asal_id', null);
                                $set('tujuan_id', null);
                            })
                            ->columnSpanFull(),

                        Forms\Components\Hidden::make('asal_type')->default('gudang'),
                        Forms\Components\Hidden::make('tujuan_type')->default('gudang'),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('asal_id')
                                ->label('Dari Gudang')
                                ->options(fn () => Gudang::pluck('nama_gudang', 'id'))
                                ->searchable()
                                ->required()
                                ->visible(fn (callable $get) => $get('jenis_mutasi') === 'transfer'),

                            Forms\Components\Select::make('tujuan_id')
                                ->label(fn (callable $get) => match ($get('jenis_mutasi')) {
                                    'transfer' => 'Ke Gudang',
                                    default => 'Di Gudang',
                                })
                                ->options(fn () => Gudang::pluck('nama_gudang', 'id'))
                                ->searchable()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (callable $get, callable $set) {
                                    // Untuk registrasi/rusak/perbaikan, asal_id disamakan
                                    // dengan tujuan_id di background (satu gudang saja),
                                    // supaya AlatStokRebuilder (yang baca dari asal_id
                                    // untuk kasus rusak/perbaikan) tetap dapat nilainya.
                                    if ($get('jenis_mutasi') !== 'transfer') {
                                        $set('asal_id', $get('tujuan_id'));
                                    }
                                }),
                        ]),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('jumlah_baik')
                                ->label('Jumlah Kondisi Baik')
                                ->numeric()
                                ->minValue(0)
                                ->default(0)
                                ->required(),

                            Forms\Components\TextInput::make('jumlah_rusak')
                                ->label('Jumlah Kondisi Rusak')
                                ->numeric()
                                ->minValue(0)
                                ->default(0)
                                ->required(),
                        ])->visible(fn (callable $get) => in_array($get('jenis_mutasi'), ['registrasi', 'transfer'])),

                        Forms\Components\TextInput::make('jumlah')
                            ->label(fn (callable $get) => $get('jenis_mutasi') === 'rusak'
                                ? 'Jumlah Unit yang Rusak'
                                : 'Jumlah Unit yang Selesai Diperbaiki')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->visible(fn (callable $get) => in_array($get('jenis_mutasi'), ['rusak', 'perbaikan'])),

                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\Select::make('kondisi_alat')
                                ->label('Kondisi Fisik Alat')
                                ->options([
                                    'Bagus' => '✅ Bagus / Layak Pakai',
                                    'Rusak Ringan' => '⚠️ Rusak Ringan',
                                    'Rusak Berat' => '❌ Rusak Berat',
                                ])
                                ->default('Bagus')
                                ->required(),

                            Forms\Components\Select::make('disetujui_oleh')
                                ->label('Penanggung Jawab / PM')
                                ->options(fn () => Karyawan::query()->pluck('nama', 'nama')->toArray())
                                ->searchable()
                                ->preload()
                                ->nullable(),

                            Forms\Components\TextInput::make('pengangkut_driver')
                                ->label('Driver / Ekspedisi')
                                ->placeholder('Nama Driver / No. Plat'),
                        ]),

                        Forms\Components\Textarea::make('catatan')
                            ->label('Catatan Mutasi')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('no_surat_jalan')
                    ->label('No. Surat Jalan')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('tanggal_mutasi')
                    ->label('Tanggal')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('alat.nama_alat')
                    ->label('Nama Alat')
                    ->searchable(),

                Tables\Columns\TextColumn::make('jenis_mutasi')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'registrasi' => '📋 Registrasi',
                        'transfer' => '🔄 Transfer',
                        'rusak' => '❌ Rusak',
                        'perbaikan' => '✅ Perbaikan',
                        default => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'registrasi' => 'gray',
                        'transfer' => 'info',
                        'rusak' => 'danger',
                        'perbaikan' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('asal')
                    ->label('Dari')
                    ->state(fn (MutasiAlat $record) => $record->jenis_mutasi === 'transfer' ? $record->namaAsal() : '-'),

                Tables\Columns\TextColumn::make('tujuan')
                    ->label('Di / Ke Gudang')
                    ->state(fn (MutasiAlat $record) => $record->namaTujuan()),

                Tables\Columns\TextColumn::make('jumlah_baik')
                    ->label('Baik')
                    ->placeholder('-')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('jumlah_rusak')
                    ->label('Rusak')
                    ->placeholder('-')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('jumlah')
                    ->label('Qty')
                    ->placeholder('-')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('disetujui_oleh')
                    ->label('Penanggung Jawab')
                    ->placeholder('-')
                    ->searchable(),
            ])
            ->defaultSort('tanggal_mutasi', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListMutasiAlats::route('/'),
            'create' => Pages\CreateMutasiAlat::route('/create'),
            'edit' => Pages\EditMutasiAlat::route('/{record}/edit'),
        ];
    }
}