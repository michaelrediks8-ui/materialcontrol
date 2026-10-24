<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BarangMasukResource\Pages;
use App\Models\Gudang;
use App\Models\Karyawan;
use App\Models\PurchaseOrder;
use App\Models\TransaksiBarang;
use App\Models\Vendor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\Indicator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;

class BarangMasukResource extends Resource
{
    protected static ?string $model = TransaksiBarang::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';
    protected static ?string $navigationLabel = 'Barang Masuk';
    protected static ?string $navigationGroup = 'Stok Material';
    protected static ?string $slug = 'barang-masuk';
    protected static ?string $modelLabel = 'Transaksi Barang';
    protected static ?string $pluralModelLabel = 'Transaksi Barang';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Penerimaan Barang (Inbound dari Supplier)')
                    ->schema([
                        Forms\Components\Hidden::make('jenis_transaksi')->default('masuk'),
                        Forms\Components\Hidden::make('asal_type')->default('vendor'),

                        // Baris 1: Tanggal, No Surat Jalan, Vendor Asal
                        Forms\Components\Grid::make(['default' => 1, 'md' => 2, 'xl' => 3])->schema([
                            Forms\Components\DateTimePicker::make('tanggal_transaksi')
                                ->label('Tanggal & Jam Terima')
                                ->displayFormat('d/m/Y H:i:s')
                                ->format('Y-m-d H:i:s')
                                ->seconds(true)
                                ->default(now())
                                ->required(),

                            Forms\Components\TextInput::make('no_surat_jalan')
                                ->label('No. Surat Jalan / Bon Supplier')
                                ->placeholder('Kosongkan jika tanpa bon fisik')
                                ->maxLength(255),

                            Forms\Components\Select::make('asal_id')
                                ->label('Vendor / Supplier Asal')
                                ->options(fn () => Vendor::query()->whereNotNull('nama_vendor')->pluck('nama_vendor', 'id')->map(fn ($val) => (string) $val)->toArray())
                                ->searchable()
                                ->preload()
                                ->required(),
                        ]),

                        // Baris 2: Ekspedisi & No Resi
                        Forms\Components\Grid::make(['default' => 1, 'md' => 2])->schema([
                            Forms\Components\TextInput::make('jasa_pengirim')
                                ->label('Ekspedisi / Jasa Pengirim')
                                ->placeholder('Contoh: Gopans, J&T, Kurir PT'),

                            Forms\Components\TextInput::make('no_resi')
                                ->label('No. Resi / Pelacak')
                                ->placeholder('Kosongkan jika tidak ada resi'),
                        ]),

                        // Baris 3: Pemohon & Penerima Fisik
                        Forms\Components\Grid::make(['default' => 1, 'md' => 2])->schema([
                            Forms\Components\Select::make('request_oleh')
                                ->label('Diminta Oleh')
                                ->options(fn () => Karyawan::query()->pluck('nama', 'nama')->toArray())
                                ->searchable()
                                ->preload()
                                ->placeholder('Pilih Karyawan (Opsional)')
                                ->nullable(),

                            Forms\Components\Select::make('diterima_oleh')
                                ->label('Diterima Oleh (Penerima Fisik)')
                                ->options(fn () => Karyawan::query()->pluck('nama', 'nama')->toArray())
                                ->searchable()
                                ->preload()
                                ->placeholder('Pilih Karyawan')
                                ->required(),
                        ]),

                        // Baris 4: Tujuan Penerimaan & Gudang Penerima
                        Forms\Components\Grid::make(['default' => 1, 'md' => 2])->schema([
                            Forms\Components\Select::make('tujuan_type')
                                ->label('Tujuan Penerimaan')
                                ->options([
                                    'gudang' => '🏢 Masuk ke Gudang',
                                    'project' => '🚚 Langsung ke Project (Tanpa Singgah Gudang)',
                                ])
                                ->default('gudang')
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (callable $set) {
                                    $set('tujuan_id', null);
                                }),

                            Forms\Components\Select::make('tujuan_id')
                                ->label('Pilih Gudang Penerima')
                                ->options(fn () => Gudang::query()->whereNotNull('nama_gudang')->pluck('nama_gudang', 'id')->map(fn ($val) => (string) $val)->toArray())
                                ->searchable()
                                ->preload()
                                ->required(fn (callable $get) => $get('tujuan_type') === 'gudang')
                                ->visible(fn (callable $get) => $get('tujuan_type') === 'gudang'),
                        ]),

                        // Baris 5: PO / Project Utama (tersimpan di header, autofill tabel rincian)
                        Forms\Components\Select::make('purchase_order_id')
                            ->label('Untuk Project / PO Mana? (Opsional - Autofill Tabel)')
                            ->helperText('Jika diisi, seluruh item di tabel rincian di bawah akan otomatis terisi project dari PO ini.')
                            ->options(fn () => PurchaseOrder::with('project')
                                ->get()
                                ->mapWithKeys(fn (PurchaseOrder $po) => [
                                    $po->id => "{$po->no_po} - " . ($po->project->nama_project ?? 'Tanpa Nama Project'),
                                ])
                                ->toArray())
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                if (! $state) {
                                    return;
                                }

                                $po = PurchaseOrder::find($state);
                                if (! $po?->project_id) {
                                    return;
                                }

                                foreach (array_keys($get('details') ?? []) as $key) {
                                    $set("details.{$key}.project_id", $po->project_id);
                                }
                            }),
                    ]),

                Forms\Components\Section::make('Rincian Barang yang Diterima')
                    ->description('Tentukan rincian barang dan alokasi project-nya pada daftar di bawah.')
                    ->schema([
                        TableRepeater::make('details')
                            ->hiddenLabel()
                            ->relationship('details')
                            ->headers([
                                Header::make('barang_id')->label('Nama Barang')->width('35%'),
                                Header::make('qty')->label('Qty')->width('15%'),
                                Header::make('satuan_id')->label('Satuan')->width('20%'),
                                Header::make('project_id')->label('Alokasi Project')->width('30%'),
                            ])
                            ->schema([
                                Forms\Components\Select::make('barang_id')
                                    ->label('Nama Barang')
                                    ->relationship('barang', 'nama_barang')
                                    ->getOptionLabelFromRecordUsing(fn ($record) => "[{$record->kode_barang}] {$record->nama_barang}")
                                    ->searchable(['kode_barang', 'nama_barang'])
                                    // Tanpa preload: daftar barang TIDAK dimuat ke halaman, hanya dicari saat mengetik.
                                    ->searchDebounce(300)
                                    ->optionsLimit(30)
                                    ->noSearchResultsMessage('Barang tidak ditemukan')
                                    ->searchPrompt('Ketik kode atau nama barang…')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        // Satuan otomatis terisi satuan dasar barang
                                        $set('satuan_id', $state ? \App\Models\Barang::whereKey($state)->value('satuan_id') : null);
                                    }),

                                Forms\Components\TextInput::make('qty')
                                    ->label('Qty')
                                    ->numeric()
                                    ->step('any')
                                    ->minValue(0.01)
                                    ->default(null)
                                    ->placeholder('0')
                                    ->required(),

                                Forms\Components\Select::make('satuan_id')
                                    ->label('Satuan')
                                    ->options(function (callable $get) {
                                        $barang = \App\Models\Barang::with(['satuan', 'satuanBesar'])->find($get('barang_id'));
                                        if (! $barang) {
                                            return [];
                                        }

                                        $options = [];
                                        if ($barang->satuan_id) {
                                            $options[$barang->satuan_id] = $barang->satuan->nama_satuan ?? '-';
                                        }
                                        if ($barang->satuan_besar_id) {
                                            $options[$barang->satuan_besar_id] = $barang->satuanBesar->nama_satuan ?? '-';
                                        }

                                        return $options;
                                    })
                                    ->required(),

                                Forms\Components\Select::make('project_id')
                                    ->label('Alokasi Project')
                                    ->relationship('project', 'nama_project')
                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->default(function (Forms\Get $get) {
                                        $poId = $get('../../purchase_order_id');

                                        return $poId ? PurchaseOrder::find($poId)?->project_id : null;
                                    })
                                    ->required(fn (callable $get) => $get('../../tujuan_type') === 'project')
                                    ->placeholder('Pilih Project (Opsional)'),
                            ])
                            ->defaultItems(1)
                            ->addActionLabel('＋ Tambah Item Masuk')
                            ->reorderable(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['purchaseOrder.project', 'details.project'])->where('jenis_transaksi', 'masuk'))
            ->columns([
                // No. transaksi + tanggal dalam satu sel
                Tables\Columns\TextColumn::make('no_transaksi')
                    ->label('Transaksi')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->description(fn (TransaksiBarang $record): ?string => $record->tanggal_transaksi
                        ? Carbon::parse($record->tanggal_transaksi)->format('d M Y, H:i')
                        : null)
                    ->extraAttributes(['class' => 'whitespace-nowrap']),

                // Dipakai untuk mengurutkan berdasarkan tanggal (disembunyikan; aktifkan lewat ikon kolom)
                Tables\Columns\TextColumn::make('tanggal_transaksi')
                    ->label('Tanggal')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Supplier + No. Bon/SJ dalam satu sel (pencarian meliputi nama vendor & no. bon)
                Tables\Columns\TextColumn::make('asal')
                    ->label('Supplier / No. Bon')
                    ->state(fn (TransaksiBarang $record): string => $record->namaAsal() ?? '-')
                    ->description(fn (TransaksiBarang $record): ?string => filled($record->no_surat_jalan)
                        ? '📄 ' . $record->no_surat_jalan
                        : null)
                    ->limit(30)
                    ->tooltip(fn (TransaksiBarang $record): ?string => $record->namaAsal())
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $q) use ($search) {
                            $q->where('no_surat_jalan', 'like', "%{$search}%")
                                ->orWhereIn('asal_id', Vendor::query()
                                    ->where('nama_vendor', 'like', "%{$search}%")
                                    ->select('id'));
                        });
                    })
                    ->visibleFrom('md'),

                // Gudang tujuan (badge) + ringkasan project (satu baris, sisanya "+N")
                Tables\Columns\TextColumn::make('tujuan')
                    ->label('Tujuan')
                    ->state(fn (TransaksiBarang $record): string => $record->tujuan_type === 'project'
                        ? '🚚 Langsung ke Project'
                        : ($record->namaTujuan() ?: '-'))
                    ->description(function (TransaksiBarang $record): ?string {
                        $nama = static::namaProject($record);
                        if (empty($nama)) {
                            return null;
                        }

                        $teks = '📌 ' . Str::limit($nama[0], 38);

                        return count($nama) > 1 ? $teks . ' +' . (count($nama) - 1) : $teks;
                    })
                    ->tooltip(function (TransaksiBarang $record): ?string {
                        $nama = static::namaProject($record);

                        return $nama ? implode(', ', $nama) : null;
                    })
                    ->badge()
                    ->color(fn (TransaksiBarang $record) => $record->tujuan_type === 'project' ? 'success' : 'info'),

                Tables\Columns\TextColumn::make('details_count')
                    ->label('Item')
                    ->counts('details')
                    ->badge()
                    ->color('gray')
                    ->alignCenter(),

                // Opsional: aktifkan lewat ikon kolom di kanan atas tabel
                Tables\Columns\TextColumn::make('request_oleh')
                    ->label('Diminta Oleh')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('diterima_oleh')
                    ->label('Diterima Oleh')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('tanggal_transaksi')
                    ->form([
                        Forms\Components\Grid::make(['default' => 1, 'sm' => 2])->schema([
                            Forms\Components\DatePicker::make('dari_tanggal')
                                ->label('Dari Tanggal')
                                ->default(now()),

                            Forms\Components\DatePicker::make('sampai_tanggal')
                                ->label('Sampai Tanggal')
                                ->default(now()),
                        ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari_tanggal'],
                                fn (Builder $q, $date): Builder => $q->where('tanggal_transaksi', '>=', Carbon::parse($date)->startOfDay()),
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn (Builder $q, $date): Builder => $q->where('tanggal_transaksi', '<=', Carbon::parse($date)->endOfDay()),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['dari_tanggal'] ?? null) {
                            $indicators[] = Indicator::make('Dari: ' . Carbon::parse($data['dari_tanggal'])->format('d/m/Y'))
                                ->removeField('dari_tanggal');
                        }
                        if ($data['sampai_tanggal'] ?? null) {
                            $indicators[] = Indicator::make('Sampai: ' . Carbon::parse($data['sampai_tanggal'])->format('d/m/Y'))
                                ->removeField('sampai_tanggal');
                        }
                        return $indicators;
                    }),
            ])
            // Filter tanggal di atas tabel, responsif: 1 kolom di HP, 2 kolom di tablet ke atas
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(['default' => 1, 'md' => 2])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->iconButton()
                    ->tooltip('Lihat rincian')
                    ->label('View')
                    ->modalHeading('Rincian Transaksi Penerimaan Barang Masuk')
                    ->infolist([
                        \Filament\Infolists\Components\Section::make('Informasi Penerimaan')
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('no_transaksi')
                                    ->label('No. Transaksi')
                                    ->weight('bold')
                                    ->copyable(),

                                \Filament\Infolists\Components\TextEntry::make('tanggal_transaksi')
                                    ->label('Tanggal & Jam Terima')
                                    ->dateTime('d M Y, H:i'),

                                \Filament\Infolists\Components\TextEntry::make('request_oleh')
                                    ->label('Diminta Oleh')
                                    ->placeholder('-'),

                                \Filament\Infolists\Components\TextEntry::make('asal_view')
                                    ->label('Supplier / Vendor')
                                    ->state(fn ($record) => $record->namaAsal())
                                    ->weight('bold'),

                                \Filament\Infolists\Components\TextEntry::make('diterima_oleh')
                                    ->label('Diterima Oleh (Fisik)')
                                    ->placeholder('-'),

                                \Filament\Infolists\Components\TextEntry::make('tujuan_view')
                                    ->label('Tujuan Penerimaan')
                                    ->state(function ($record) {
                                        if ($record->tujuan_type === 'project') {
                                            return '🚚 Langsung ke Project (Tanpa Singgah Gudang)';
                                        }
                                        return '🏢 Gudang: ' . $record->namaTujuan();
                                    })
                                    ->badge()
                                    ->color(fn ($record) => $record->tujuan_type === 'project' ? 'success' : 'info'),

                                \Filament\Infolists\Components\TextEntry::make('no_bon_view')
                                    ->label('No. Bon / Surat Jalan')
                                    ->state(fn ($record) => $record->no_surat_jalan ?: '-'),

                                \Filament\Infolists\Components\TextEntry::make('project_view')
                                    ->label('Project / PO')
                                    ->icon('heroicon-o-map-pin')
                                    ->weight('bold')
                                    ->state(fn ($record) => static::projectDenganPo($record) ?: ['-'])
                                    ->badge()
                                    ->color('success')
                                    ->columnSpanFull(),
                            ])->columns(['default' => 1, 'md' => 3]),

                        \Filament\Infolists\Components\Section::make('Daftar Rincian Barang & Alokasi Project')
                            ->schema([
                                \Filament\Infolists\Components\RepeatableEntry::make('details')
                                    ->label('')
                                    ->schema([
                                        \Filament\Infolists\Components\TextEntry::make('barang.nama_barang')
                                            ->label('Nama Barang')
                                            ->weight('bold'),

                                        \Filament\Infolists\Components\TextEntry::make('qty')
                                            ->label('Jumlah')
                                            ->formatStateUsing(function ($state) {
                                                $formatted = number_format((float) $state, 2, ',', '.');
                                                return rtrim(rtrim($formatted, '0'), ',');
                                            }),

                                        \Filament\Infolists\Components\TextEntry::make('satuan.nama_satuan')
                                            ->label('Satuan')
                                            ->formatStateUsing(function ($state) {
                                                if (is_string($state) && str_contains($state, '"nama_satuan"')) {
                                                    return json_decode($state, true)['nama_satuan'] ?? $state;
                                                }
                                                return data_get($state, 'nama_satuan', $state);
                                            }),

                                        \Filament\Infolists\Components\TextEntry::make('alokasi_project')
                                            ->label('Alokasi Project')
                                            ->state(function ($record) {
                                                $parent = TransaksiBarang::find($record->transaksi_barang_id);

                                                if (! $record->project) {
                                                    return '📦 Stok Umum Gudang';
                                                }

                                                $namaProject = $record->project->nama_project;

                                                // Prioritas: PO yang dipilih saat input.
                                                // Cadangan (data lama): PO milik project item ini.
                                                $noPo = $parent?->purchaseOrder?->no_po
                                                    ?: PurchaseOrder::where('project_id', $record->project_id)
                                                        ->pluck('no_po')->filter()->implode(', ');

                                                $teks = $noPo ? "{$noPo} - {$namaProject}" : $namaProject;

                                                // Barang singgah di gudang -> hanya dialokasikan.
                                                // Barang tidak singgah gudang -> dikirim langsung.
                                                return $parent?->tujuan_type === 'project'
                                                    ? "🚚 Langsung ke Project: {$teks}"
                                                    : "🏷️ Untuk Project: {$teks}";
                                            })
                                            ->badge()
                                            ->color(function ($record) {
                                                if (! $record->project) {
                                                    return 'gray';
                                                }

                                                return TransaksiBarang::find($record->transaksi_barang_id)?->tujuan_type === 'project'
                                                    ? 'success'
                                                    : 'info';
                                            }),
                                    ])->columns(['default' => 2, 'md' => 4])
                                    ->columnSpanFull(),
                            ]),
                    ]),

                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->tooltip('Edit'),

                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->modalHeading('Hapus Transaksi')
                    ->modalDescription('Transaksi beserta rinciannya akan dihapus dan stok dihitung ulang. Lanjutkan?')
                    ->tooltip('Hapus')
                    ->successNotificationTitle('Transaksi berhasil dihapus'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->defaultSort('tanggal_transaksi', 'desc');
    }

    /**
     * Nama project yang terkait transaksi (project PO + project tiap item), tanpa duplikat.
     */
    protected static function namaProject(TransaksiBarang $record): array
    {
        return collect([$record->purchaseOrder?->project?->nama_project])
            ->merge($record->details->map(fn ($d) => $d->project?->nama_project))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Daftar "No. PO - Nama Project" untuk transaksi ini (tanpa duplikat).
     * PO dari header dipakai dulu; project item lain dicarikan PO-nya dari data PO.
     */
    protected static function projectDenganPo(TransaksiBarang $record): array
    {
        $hasil = [];
        $po = $record->purchaseOrder;

        if ($po?->project) {
            $hasil[$po->project->id] = trim(($po->no_po ? $po->no_po . ' - ' : '') . $po->project->nama_project);
        }

        foreach ($record->details as $detail) {
            $project = $detail->project;
            if (! $project || isset($hasil[$project->id])) {
                continue;
            }

            $noPo = PurchaseOrder::where('project_id', $project->id)->pluck('no_po')->filter()->implode(', ');
            $hasil[$project->id] = trim(($noPo ? $noPo . ' - ' : '') . $project->nama_project);
        }

        return array_values($hasil);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->orderBy('no_transaksi', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBarangMasuks::route('/'),
            'create' => Pages\CreateBarangMasuk::route('/create'),
            'edit' => Pages\EditBarangMasuk::route('/{record}/edit'),
        ];
    }
}