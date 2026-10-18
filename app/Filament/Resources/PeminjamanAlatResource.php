<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PeminjamanAlatResource\Pages;
use App\Models\PeminjamanAlat;
use App\Models\Alat;
use App\Models\StokAlat;
use App\Models\Gudang;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;

class PeminjamanAlatResource extends Resource
{
    protected static ?string $model = PeminjamanAlat::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Monitoring Alat';
    protected static ?string $navigationLabel = 'Peminjaman Alat';
    protected static ?string $slug = 'peminjaman-alat';
    protected static ?string $modelLabel = 'Peminjaman Alat';
    protected static ?string $pluralModelLabel = 'Peminjaman Alat';

    /** Hitung total pengembalian sekali di query (hindari N+1 di tabel). */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withSum('pengembalians as total_kembali', 'qty_kembali');
    }

    /** @return array<int|string,string> id => "[kode] nama" */
    protected static function alatOptions(): array
    {
        static $cache = null;

        return $cache ??= Alat::query()
            ->orderBy('nama_alat')
            ->get(['id', 'kode_alat', 'nama_alat'])
            ->mapWithKeys(fn ($a) => [$a->id => "[{$a->kode_alat}] {$a->nama_alat}"])
            ->all();
    }

    /** @return array<int|string,string> id => nama gudang */
    protected static function gudangOptions(): array
    {
        static $cache = null;

        return $cache ??= Gudang::query()
            ->orderBy('nama_gudang')
            ->pluck('nama_gudang', 'id')
            ->all();
    }

    protected static function tersediaText($alatId, $gudangId): ?string
    {
        if (! $alatId || ! $gudangId) {
            return null;
        }

        $alat = Alat::find($alatId);

        return $alat ? "Tersedia di gudang ini: {$alat->tersediaDiGudang((int) $gudangId)} {$alat->satuan}" : null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Peminjam & Jadwal')
                    ->description('Satu peminjam bisa meminjam beberapa alat sekaligus. Jadwal dan keperluan berlaku untuk semua alat.')
                    ->schema([
                        Forms\Components\Select::make('karyawan_id')
                            ->label('Peminjam')
                            ->relationship('karyawan', 'nama')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Grid::make(['default' => 1, 'md' => 2, 'xl' => 3])->schema([
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

                            // Diisi otomatis lewat aksi "Kembalikan" (syncStatusKembali),
                            // jadi tidak diedit manual dan tidak ikut disimpan dari form.
                            Forms\Components\DateTimePicker::make('tanggal_aktual_kembali')
                                ->label('Tanggal Aktual Dikembalikan')
                                ->helperText('Terisi otomatis saat seluruh unit sudah dikembalikan.')
                                ->disabled()
                                ->dehydrated(false)
                                ->visibleOn('edit'),
                        ]),

                        Forms\Components\TextInput::make('keperluan')
                            ->label('Keperluan / Area Kerja')
                            ->placeholder('Tulis Nama Keperluan')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('catatan')
                            ->label('Catatan Tambahan')
                            ->columnSpanFull(),
                    ]),

                // ── CREATE: banyak alat sekaligus (1 baris tabel = 1 baris peminjaman_alats)
                Forms\Components\Section::make('Alat yang Dipinjam')
                    ->description('Tambahkan semua alat yang dipinjam orang ini. Tiap alat dicatat sebagai satu peminjaman sendiri, jadi bisa dikembalikan terpisah.')
                    ->visibleOn('create')
                    ->schema([
                        TableRepeater::make('items')
                            ->hiddenLabel()
                            ->headers([
                                Header::make('alat_id')->label('Alat Kerja')->width('45%'),
                                Header::make('gudang_id')->label('Ambil dari Gudang')->width('35%'),
                                Header::make('qty')->label('Qty')->width('20%'),
                            ])
                            ->schema([
                                Forms\Components\Select::make('alat_id')
                                    ->label('Alat Kerja')
                                    ->options(fn () => static::alatOptions())
                                    ->searchable()
                                    ->required()
                                    ->live(),

                                Forms\Components\Select::make('gudang_id')
                                    ->label('Ambil dari Gudang')
                                    ->options(fn () => static::gudangOptions())
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->helperText(fn (Get $get) => static::tersediaText($get('alat_id'), $get('gudang_id'))),

                                Forms\Components\TextInput::make('qty')
                                    ->label('Qty')
                                    ->numeric()
                                    ->integer()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required(),
                            ])
                            ->defaultItems(1)
                            ->minItems(1)
                            ->reorderable(false)
                            ->addActionLabel('+ Tambah Alat')
                            // Alat + gudang yang sama di beberapa baris dijumlahkan sebelum dicek ke stok.
                            ->rule(function () {
                                return function (string $attribute, $value, \Closure $fail) {
                                    $perKombinasi = [];

                                    foreach ((array) $value as $item) {
                                        $alatId = $item['alat_id'] ?? null;
                                        $gudangId = $item['gudang_id'] ?? null;

                                        if (! $alatId || ! $gudangId) {
                                            continue;
                                        }

                                        $key = "{$alatId}|{$gudangId}";
                                        $perKombinasi[$key] = ($perKombinasi[$key] ?? 0) + (int) ($item['qty'] ?? 0);
                                    }

                                    foreach ($perKombinasi as $key => $total) {
                                        [$alatId, $gudangId] = array_map('intval', explode('|', $key));

                                        $alat = Alat::find($alatId);
                                        if (! $alat) {
                                            continue;
                                        }

                                        $sisa = $alat->tersediaDiGudang($gudangId);

                                        if ($total > $sisa) {
                                            $fail("Stok {$alat->nama_alat} tidak mencukupi di gudang ini. Total diminta {$total}, tersedia {$sisa} {$alat->satuan}.");
                                        }
                                    }
                                };
                            }),
                    ]),

                // ── EDIT: tetap satu alat per baris peminjaman (seperti sebelumnya)
                Forms\Components\Section::make('Alat yang Dipinjam')
                    ->visibleOn('edit')
                    ->schema([
                        Forms\Components\Grid::make(['default' => 1, 'md' => 2])->schema([
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
                                ->helperText(fn (callable $get) => static::tersediaText($get('alat_id'), $get('gudang_id'))),
                        ]),

                        Forms\Components\TextInput::make('qty')
                            ->label('Jumlah Pinjam (Qty)')
                            ->numeric()
                            ->default(1)
                            // Saat edit, qty tidak boleh lebih kecil dari yang sudah dikembalikan
                            ->minValue(fn (?PeminjamanAlat $record) => max(1, $record?->qtyKembali() ?? 1))
                            ->required()
                            ->rule(function (callable $get, string $operation, ?PeminjamanAlat $record) {
                                return function (string $attribute, $value, \Closure $fail) use ($get, $operation, $record) {
                                    $alatId = $get('alat_id');
                                    $gudangId = $get('gudang_id');
                                    if (! $alatId || ! $gudangId) return;

                                    $alat = Alat::find($alatId);
                                    if (! $alat) return;

                                    $sisa = $alat->tersediaDiGudang($gudangId);

                                    // Saat edit, unit milik peminjaman ini yang masih di luar
                                    // sudah terhitung "sedang dipinjam", jadi tambahkan kembali.
                                    if ($operation === 'edit' && $record && $record->alat_id == $alatId && $record->gudang_id == $gudangId) {
                                        $sisa += $record->sisaBelumKembali();
                                        $value = (int) $value - $record->qtyKembali();
                                    }

                                    if ($value > $sisa) {
                                        $fail("Stok tidak mencukupi di gudang ini! Maksimal yang bisa dipinjam saat ini adalah {$sisa} {$alat->satuan}.");
                                    }
                                };
                            }),
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
                    ->label('Qty Pinjam')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('qty_progress')
                    ->label('Kembali')
                    ->alignCenter()
                    ->badge()
                    ->state(fn (PeminjamanAlat $record): string => ((int) ($record->total_kembali ?? 0)) . ' / ' . $record->qty)
                    ->color(function (PeminjamanAlat $record): string {
                        $kembali = (int) ($record->total_kembali ?? 0);

                        if ($kembali >= $record->qty) return 'success';
                        if ($kembali > 0) return 'warning';

                        return 'gray';
                    }),

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

                        $diffInDays = (int) now()->diffInDays(Carbon::parse($record->tanggal_rencana_kembali), false);
                        $sisa = max(0, $record->qty - (int) ($record->total_kembali ?? 0));
                        $prefix = $sisa < $record->qty ? "Sisa {$sisa} unit · " : '';

                        if ($diffInDays < 0) {
                            return $prefix . '⚠️ Telat ' . abs($diffInDays) . ' Hari';
                        }

                        return $prefix . '⏳ ' . $diffInDays . ' Hari Lagi';
                    })
                    ->color(function (PeminjamanAlat $record): string {
                        if ($record->tanggal_aktual_kembali !== null || ! $record->tanggal_rencana_kembali) {
                            return 'gray';
                        }

                        $diffInDays = (int) now()->diffInDays(Carbon::parse($record->tanggal_rencana_kembali), false);

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
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['status_pinjam'],
                            function (Builder $query, $status) {
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
                    ->modalHeading(fn (PeminjamanAlat $record) => 'Pengembalian: ' . ($record->alat?->nama_alat ?? 'Alat'))
                    ->modalDescription(fn (PeminjamanAlat $record) => "Dipinjam {$record->qty}, sudah kembali {$record->qtyKembali()}, masih di luar {$record->sisaBelumKembali()}. Stok tersedia akan bertambah sesuai jumlah yang dikembalikan.")
                    ->modalSubmitActionLabel('Simpan Pengembalian')
                    ->form(fn (PeminjamanAlat $record) => [
                        Forms\Components\TextInput::make('qty_kembali')
                            ->label('Jumlah Alat yang Dikembalikan')
                            ->numeric()
                            ->integer()
                            ->required()
                            ->minValue(1)
                            ->maxValue($record->sisaBelumKembali())
                            ->default($record->sisaBelumKembali())
                            ->suffix($record->alat?->satuan)
                            ->helperText("Maksimal {$record->sisaBelumKembali()} (sisa yang belum dikembalikan). Isi lebih kecil untuk pengembalian sebagian."),

                        Forms\Components\DateTimePicker::make('tanggal_kembali')
                            ->label('Tanggal & Jam Dikembalikan')
                            ->default(now())
                            ->maxDate(now()->addMinute())
                            ->required(),

                        Forms\Components\Select::make('kondisi')
                            ->label('Kondisi Alat Saat Kembali')
                            ->options([
                                'baik'   => 'Baik',
                                'rusak'  => 'Rusak',
                                'hilang' => 'Hilang',
                            ])
                            ->default('baik')
                            ->required()
                            ->helperText('Rusak/hilang otomatis mengurangi stok baik di gudang asal. Rusak menambah stok rusak.'),

                        Forms\Components\TextInput::make('penerima')
                            ->label('Diterima Oleh')
                            ->default(fn () => auth()->user()?->name),

                        Forms\Components\Textarea::make('catatan')
                            ->label('Catatan Pengembalian'),
                    ])
                    ->action(function (PeminjamanAlat $record, array $data) {
                        $sisa = $record->sisaBelumKembali();
                        $qty = (int) $data['qty_kembali'];

                        if ($qty < 1 || $qty > $sisa) {
                            Notification::make()
                                ->title('Jumlah tidak valid')
                                ->body("Jumlah yang dikembalikan harus 1 sampai {$sisa}.")
                                ->danger()
                                ->send();
                            return;
                        }

                        // Model PengembalianAlat otomatis menghitung ulang status peminjaman
                        DB::transaction(function () use ($record, $data, $qty) {
                            $record->pengembalians()->create([
                                'qty_kembali'     => $qty,
                                'tanggal_kembali' => $data['tanggal_kembali'],
                                'kondisi'         => $data['kondisi'],
                                'penerima'        => $data['penerima'] ?? null,
                                'catatan'         => $data['catatan'] ?? null,
                            ]);

                            // Unit yang kembali rusak/hilang tidak boleh kembali jadi stok "baik"
                            if ($data['kondisi'] !== 'baik') {
                                $stok = StokAlat::where('alat_id', $record->alat_id)
                                    ->where('gudang_id', $record->gudang_id)
                                    ->lockForUpdate()
                                    ->first();

                                if ($stok) {
                                    $stok->jumlah_baik = max(0, $stok->jumlah_baik - $qty);

                                    if ($data['kondisi'] === 'rusak') {
                                        $stok->jumlah_rusak += $qty;
                                    }

                                    $stok->save();
                                }
                            }
                        });

                        $sisaBaru = $sisa - $qty;

                        Notification::make()
                            ->title($sisaBaru === 0 ? 'Semua Alat Sudah Dikembalikan' : 'Pengembalian Sebagian Tercatat')
                            ->body($sisaBaru === 0 ? null : "{$qty} unit kembali, masih tersisa {$sisaBaru} unit di luar.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('preview_pdf')
                    ->label('Cetak Bukti')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->url(fn (PeminjamanAlat $record) => route('peminjaman-alat.preview-pdf', $record))
                    ->openUrlInNewTab(),

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