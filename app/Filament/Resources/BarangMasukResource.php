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
                                    'project' => '🚚 Langsung Drop ke Project',
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
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn (callable $set) => $set('satuan_id', null)),

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
                                        $barang = \App\Models\Barang::find($get('barang_id'));
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
            ->modifyQueryUsing(fn (Builder $query) => $query->where('jenis_transaksi', 'masuk'))
            ->columns([
                Tables\Columns\TextColumn::make('no_transaksi')
                    ->label('No. Transaksi')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('tanggal_transaksi')
                    ->label('Tanggal & Jam')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),

                // Tersembunyi di HP, tampil mulai tablet
                Tables\Columns\TextColumn::make('no_surat_jalan')
                    ->label('No. Bon / SJ Supplier')
                    ->placeholder('-')
                    ->searchable()
                    ->visibleFrom('md'),

                // Tersembunyi di HP, tampil mulai tablet
                Tables\Columns\TextColumn::make('asal')
                    ->label('Dari Supplier')
                    ->state(fn (TransaksiBarang $record): string => $record->namaAsal() ?? '-')
                    ->limit(25)
                    ->visibleFrom('md'),

                Tables\Columns\TextColumn::make('tujuan')
                    ->label('Tujuan Utama')
                    ->state(function (TransaksiBarang $record): string {
                        if ($record->tujuan_type === 'project') {
                            return '🚚 Langsung Drop ke Project';
                        }
                        return $record->namaTujuan();
                    })
                    ->description(fn (TransaksiBarang $record): ?string => $record->purchaseOrder
                        ? '📌 PO: ' . $record->purchaseOrder->no_po
                        : null)
                    ->badge()
                    ->color(fn ($record) => $record->tujuan_type === 'project' ? 'success' : 'info'),

                Tables\Columns\TextColumn::make('details_count')
                    ->label('Total Item')
                    ->counts('details')
                    ->badge()
                    ->color('gray'),
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
                                fn (Builder $q, $date): Builder => $q->whereDate('tanggal_transaksi', '>=', $date),
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn (Builder $q, $date): Builder => $q->whereDate('tanggal_transaksi', '<=', $date),
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
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(['default' => 1, 'md' => 2])
            ->actions([
                Tables\Actions\ViewAction::make()
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
                                            return '🚚 Langsung Drop ke Project';
                                        }
                                        return 'Gudang: ' . $record->namaTujuan();
                                    })
                                    ->badge()
                                    ->color(fn ($record) => $record->tujuan_type === 'project' ? 'success' : 'info'),
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
                                                if (! $record->project) {
                                                    return '📦 Masuk Stok Gudang';
                                                }

                                                $namaProject = $record->project->nama_project;
                                                $parent = TransaksiBarang::find($record->transaksi_barang_id);

                                                // Prioritas: PO yang dipilih saat input.
                                                // Cadangan (data lama): PO milik project item ini.
                                                $noPo = $parent?->purchaseOrder?->no_po
                                                    ?: PurchaseOrder::where('project_id', $record->project_id)
                                                        ->pluck('no_po')->filter()->implode(', ');

                                                return $noPo
                                                    ? "🚚 Drop ke Project: {$noPo} - {$namaProject}"
                                                    : "🚚 Drop ke Project: {$namaProject}";
                                            })
                                            ->badge()
                                            ->color(fn ($state) => str_contains($state, 'Drop') ? 'success' : 'gray'),
                                    ])->columns(['default' => 2, 'md' => 4])
                                    ->columnSpanFull(),
                            ]),
                    ]),

                Tables\Actions\EditAction::make(),

                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->modalHeading('Hapus Transaksi')
                    ->modalDescription('Transaksi beserta rinciannya akan dihapus dan stok dihitung ulang. Lanjutkan?')
                    ->successNotificationTitle('Transaksi berhasil dihapus'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('tanggal_transaksi', 'desc');
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