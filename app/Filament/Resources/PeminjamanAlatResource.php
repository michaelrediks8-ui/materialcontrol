<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PeminjamanAlatResource\Pages;
use App\Models\PeminjamanAlat;
use App\Models\Alat;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PeminjamanAlatResource extends Resource
{
    protected static ?string $model = PeminjamanAlat::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Monitoring Alat';
    protected static ?string $navigationLabel = 'Peminjaman Alat';
    protected static ?string $slug = 'peminjaman-alat';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Barang & Peminjam')
                    ->description('Pilih alat, gudang asal, dan tentukan siapa karyawan/mandor yang meminjamnya.')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('alat_id')
                                ->label('Pilih Alat Kerja')
                                ->relationship('alat', 'nama_alat')
                                ->getOptionLabelFromRecordUsing(fn ($record) => "[{$record->kode_alat}] {$record->nama_alat}")
                                ->searchable(['kode_alat', 'nama_alat'])
                                ->preload()
                                ->required()
                                ->live(),

                            Forms\Components\Select::make('gudang_id')
                                ->label('Ambil dari Gudang')
                                ->relationship('gudang', 'nama_gudang')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->helperText(function (callable $get) {
                                    $alatId = $get('alat_id');
                                    $gudangId = $get('gudang_id');
                                    if ($alatId && $gudangId) {
                                        $alat = Alat::find($alatId);
                                        return $alat ? "Tersedia di gudang ini: {$alat->tersediaDiGudang($gudangId)} {$alat->satuan}" : null;
                                    }
                                    return null;
                                }),
                        ]),

                        Forms\Components\Select::make('karyawan_id')
                            ->label('Peminjam')
                            ->relationship('karyawan', 'nama')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\TextInput::make('qty')
                            ->label('Jumlah Pinjam (Qty)')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required()
                            ->rule(function (callable $get, string $operation, ?PeminjamanAlat $record) {
                                return function (string $attribute, $value, \Closure $fail) use ($get, $operation, $record) {
                                    $alatId = $get('alat_id');
                                    $gudangId = $get('gudang_id');
                                    if (!$alatId || !$gudangId) return;

                                    $alat = Alat::find($alatId);
                                    if (!$alat) return;

                                    $sisa = $alat->tersediaDiGudang($gudangId);

                                    if ($operation === 'edit' && $record && $record->alat_id == $alatId && $record->gudang_id == $gudangId) {
                                        $sisa += $record->qty;
                                    }

                                    if ($value > $sisa) {
                                        $fail("Stok tidak mencukupi di gudang ini! Maksimal yang bisa dipinjam saat ini adalah {$sisa} {$alat->satuan}.");
                                    }
                                };
                            }),
                    ]),

                Forms\Components\Section::make('Jadwal & Catatan Peminjaman')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\DateTimePicker::make('tanggal_pinjam')
                                ->label('Tanggal & Jam Pinjam')
                                ->default(now())
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function (callable $set, $state) {
                                    if ($state) {
                                        $set('tanggal_rencana_kembali', Carbon::parse($state)->addDays(7)->format('Y-m-d H:i:s'));
                                    }
                                }),

                            Forms\Components\DateTimePicker::make('tanggal_rencana_kembali')
                                ->label('Rencana Batas Kembali')
                                ->default(now()->addDays(7))
                                ->required(),

                            Forms\Components\DateTimePicker::make('tanggal_aktual_kembali')
                                ->label('Tanggal Aktual Dikembalikan')
                                ->nullable()
                                ->disabled(fn (string $operation) => $operation === 'create'),
                        ]),

                        Forms\Components\TextInput::make('keperluan')
                            ->label('Keperluan / Area Kerja')
                            ->placeholder('Tulis Nama Keperluan')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('catatan')
                            ->label('Catatan Tambahan')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('alat.nama_alat')
                    ->label('Nama Alat')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('gudang.nama_gudang')
                    ->label('Dari Gudang')
                    ->badge()
                    ->color('info')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('karyawan.nama')
                    ->label('Peminjam')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('qty')
                    ->label('Qty')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('tanggal_pinjam')
                    ->label('Tgl Pinjam')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('tanggal_rencana_kembali')
                    ->label('Target Kembali')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('sisa_hari')
                    ->label('Status Tracking')
                    ->badge()
                    ->state(function (PeminjamanAlat $record): string {
                        if ($record->tanggal_aktual_kembali !== null) {
                            return '✅ Dikembalikan';
                        }

                        if (! $record->tanggal_rencana_kembali) {
                            return '-';
                        }

                        $now = now();
                        $deadline = Carbon::parse($record->tanggal_rencana_kembali);
                        $diffInDays = (int) $now->diffInDays($deadline, false);

                        if ($diffInDays < 0) {
                            return '⚠️ Telat ' . abs($diffInDays) . ' Hari';
                        }

                        return '⏳ ' . $diffInDays . ' Hari Lagi';
                    })
                    ->color(function (PeminjamanAlat $record): string {
                        if ($record->tanggal_aktual_kembali !== null) {
                            return 'gray';
                        }

                        if (! $record->tanggal_rencana_kembali) {
                            return 'gray';
                        }

                        $now = now();
                        $deadline = Carbon::parse($record->tanggal_rencana_kembali);
                        $diffInDays = (int) $now->diffInDays($deadline, false);

                        if ($diffInDays < 0) return 'danger';
                        if ($diffInDays <= 2) return 'warning';

                        return 'success';
                    }),
            ])
            ->filters([
                Tables\Filters\Filter::make('status')
                    ->form([
                        Forms\Components\Select::make('status_pinjam')
                            ->label('Status Peminjaman')
                            ->options([
                                'dipinjam' => 'Masih Dipinjam',
                                'dikembalikan' => 'Sudah Dikembalikan',
                            ]),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data): \Illuminate\Database\Eloquent\Builder {
                        return $query->when(
                            $data['status_pinjam'],
                            function (\Illuminate\Database\Eloquent\Builder $query, $status) {
                                if ($status === 'dipinjam') {
                                    return $query->whereNull('tanggal_aktual_kembali');
                                }
                                if ($status === 'dikembalikan') {
                                    return $query->whereNotNull('tanggal_aktual_kembali');
                                }
                                return $query;
                            }
                        );
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('kembalikan')
                    ->label('Kembalikan')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (PeminjamanAlat $record) => $record->tanggal_aktual_kembali === null)
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Pengembalian Alat')
                    ->modalDescription('Apakah alat ini sudah diterima kembali dalam kondisi baik? Stok alat akan otomatis bertambah kembali.')
                    ->action(function (PeminjamanAlat $record) {
                        $record->update([
                            'tanggal_aktual_kembali' => now(),
                        ]);

                        Notification::make()
                            ->title('Alat Berhasil Dikembalikan')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('tanggal_pinjam', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPeminjamanAlats::route('/'),
            'create' => Pages\CreatePeminjamanAlat::route('/create'),
            'edit' => Pages\EditPeminjamanAlat::route('/{record}/edit'),
        ];
    }
}