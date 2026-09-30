<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BarangMasukResource\Pages;
use App\Models\Gudang;
use App\Models\Karyawan;
use App\Models\PurchaseOrder;
use App\Models\Satuan;
use App\Models\TransaksiBarang;
use App\Models\Vendor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;

class BarangMasukResource extends Resource
{
    protected static ?string $model = TransaksiBarang::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';
    protected static ?string $navigationLabel = 'Barang Masuk';
    protected static ?string $navigationGroup = 'Stok Material';
    protected static ?string $slug = 'barang-masuk';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Penerimaan Barang (Inbound dari Supplier)')
                    ->schema([
                        Forms\Components\Hidden::make('jenis_transaksi')->default('masuk'),
                        Forms\Components\Hidden::make('asal_type')->default('vendor'),

                        Forms\Components\Grid::make(3)->schema([
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

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('jasa_pengirim')
                                ->label('Ekspedisi / Jasa Pengirim')
                                ->placeholder('Contoh: Gopans, J&T, Kurir PT'),

                            Forms\Components\TextInput::make('no_resi')
                                ->label('No. Resi / Pelacak')
                                ->placeholder('Kosongkan jika tidak ada resi'),
                        ]),

                        Forms\Components\Grid::make(2)->schema([
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

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('purchase_order_id') // atau 'project_id' tergantung nama field header-mu
    ->label('Untuk Project / PO Mana?')
    ->options(function (): array {
        return PurchaseOrder::with('project')
            ->get()
            ->mapWithKeys(function (PurchaseOrder $po) {
                $namaProject = $po->project->nama_project ?? 'Tanpa Nama Project';
                return [$po->id => "{$po->no_po} - {$namaProject}"];
            })
            ->toArray();
    })
    ->searchable()
    ->preload()
    ->live()
    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
        if ($state) {
            $po = PurchaseOrder::with('project')->find($state);
            
            if ($po && $po->project) {
                // 1. Autofill data header lainnya jika ada
                $set('area', $po->project->area);
                $set('job_description', $po->project->nama_project);

                // 2. 💡 OTOMATIS MENGISI PROJECT_ID DI TIAP BARIS ITEM REPEATER
                $details = $get('details') ?? [];
                foreach ($details as $key => $item) {
                    $set("details.{$key}.project_id", $po->project->id);
                }
            }
        }
    })
    ->nullable(),

                            Forms\Components\TextInput::make('area')
                                ->label('Area / Site Location')
                                ->placeholder('Autofill dari Project Induk')
                                ->dehydrated(false)
                                ->nullable(),
                        ]),

                        Forms\Components\Textarea::make('job_description')
                            ->label('Job Description / Uraian Pekerjaan')
                            ->placeholder('Autofill dari Project Induk')
                            ->rows(2)
                            ->columnSpanFull()
                            ->nullable(),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('tujuan_type')
                                ->label('Tujuan Penurunan Barang (Fisik)')
                                ->options([
                                    'gudang' => '🏢 Masuk Gudang (Transit/Stok)',
                                    'project' => '🚚 Langsung Drop ke Site/Project',
                                ])
                                ->default('gudang')
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                    $set('tujuan_id', null);
                                    if ($state === 'project' && $get('purchase_order_id')) {
                                        $set('tujuan_id', $get('purchase_order_id'));
                                    }
                                }),

                            Forms\Components\Select::make('tujuan_id')
                                ->label(fn (callable $get) => $get('tujuan_type') === 'gudang' ? 'Pilih Gudang Penerima' : 'Pilih Site / Project Tujuan')
                                ->options(function (callable $get): array {
                                    $type = $get('tujuan_type') ?? 'gudang';
                                    if ($type === 'gudang') {
                                        return Gudang::query()
                                            ->whereNotNull('nama_gudang')
                                            ->pluck('nama_gudang', 'id')
                                            ->map(fn ($val) => (string) $val)
                                            ->toArray();
                                    }
                                    
                                    return PurchaseOrder::with('project')
                                        ->get()
                                        ->mapWithKeys(function (PurchaseOrder $po) {
                                            $namaProject = $po->project->nama_project ?? 'Tanpa Nama Project';
                                            return [$po->id => "{$po->no_po} - {$namaProject}"];
                                        })
                                        ->toArray();
                                })
                                ->searchable()
                                ->preload()
                                ->required(),
                        ]),
                    ]),

                Forms\Components\Section::make('Rincian Barang yang Diterima')
                    ->schema([
                        TableRepeater::make('details')
                            ->relationship('details')
                            ->headers([
                                Header::make('barang_id')->label('Nama Barang')->width('35%'),
                                Header::make('qty')->label('Qty')->width('15%'),
                                Header::make('satuan_id')->label('Satuan')->width('20%'),
                                Header::make('project_id')->label('Alokasi Project (Per Item)')->width('30%'),
                            ])
                            ->schema([
                                Forms\Components\Select::make('barang_id')
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
    ->step('any')               // 💡 WAJIB 'any' atau '0.01' agar bisa input 0.5
    ->minValue(0.01)            // 💡 Minimal angka 0.01
    ->default(null)             // 💡 Tetap dikosongkan saat tambah baru (solusi agar tidak auto 1)
    ->placeholder('0')
    ->required(),

                                Forms\Components\Select::make('satuan_id')
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
    ->placeholder('Ikut project induk')
    // 💡 Mengambil nilai project header jika baris baru ditambahkan
    ->default(function (Forms\Get $get) {
        $poId = $get('../../purchase_order_id');
        if ($poId) {
            $po = PurchaseOrder::find($poId);
            return $po->project_id ?? null;
        }
        return null;
    }),
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

                Tables\Columns\TextColumn::make('no_surat_jalan')
                    ->label('No. Bon / SJ Supplier')
                    ->placeholder('-')
                    ->searchable(),

                Tables\Columns\TextColumn::make('asal')
                    ->label('Dari Supplier')
                    ->state(fn (TransaksiBarang $record): string => $record->namaAsal() ?? '-')
                    ->limit(25),

                Tables\Columns\TextColumn::make('tujuan')
                    ->label('Lokasi Penurunan')
                    ->state(fn (TransaksiBarang $record): string => $record->namaTujuan())
                    ->description(function (TransaksiBarang $record): string {
                        if ($record->tujuan_type === 'gudang') {
                            $project = $record->namaProjectTerkait();
                            return $project !== '-' ? "📦 Transit Gudang | 📌 {$project}" : '📦 Transit Gudang';
                        }
                        return '🚚 Langsung Drop ke Project';
                    }),

                Tables\Columns\TextColumn::make('details_count')
                    ->label('Total Item')
                    ->counts('details')
                    ->badge()
                    ->color('info'),
            ])
            ->filters([
                Tables\Filters\Filter::make('tanggal_transaksi')
                    ->form([
                        Forms\Components\DatePicker::make('dari_tanggal')
                            ->label('Dari Tanggal')
                            ->default(now()),
                        Forms\Components\DatePicker::make('sampai_tanggal')
                            ->label('Sampai Tanggal')
                            ->default(now()),
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
                    }),
            ])
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
                                    ->label('Diminta Oleh / Peminta')
                                    ->placeholder('-')
                                    ->weight('bold'),

                                \Filament\Infolists\Components\TextEntry::make('asal_view')
                                    ->label('Asal Dari (Supplier / Vendor)')
                                    ->state(fn ($record) => $record->namaAsal())
                                    ->weight('bold'),

                                \Filament\Infolists\Components\TextEntry::make('diterima_oleh')
                                    ->label('Diterima Oleh (Penerima Fisik)')
                                    ->placeholder('-'),

                                \Filament\Infolists\Components\TextEntry::make('tujuan_view')
                                    ->label('Lokasi Penurunan Fisik')
                                    ->state(fn ($record) => $record->namaTujuan())
                                    ->badge()
                                    ->color('info'),
                            ])->columns(3),

                        \Filament\Infolists\Components\Section::make('Daftar Rincian Barang & Alokasi Project')
                            ->schema([
                                \Filament\Infolists\Components\RepeatableEntry::make('details')
                                    ->label('')
                                    ->schema([
                                        \Filament\Infolists\Components\TextEntry::make('barang.nama_barang')
                                            ->label('Nama Barang & Spesifikasi')
                                            ->weight('bold'),

                                        \Filament\Infolists\Components\TextEntry::make('qty')
    ->label('Jumlah')
    ->formatStateUsing(function ($state) {
        // 💡 Memformat angka: 0.5 tampil "0,5" | 5.00 tampil "5"
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
                                            ->label('Project')
                                            ->state(function ($record) {
                                                if ($record->project) {
                                                    return $record->project->nama_project;
                                                }

                                                $parent = $record->transaksiBarang ?? $record->transaksi;
                                                if ($parent) {
                                                    $namaHeader = $parent->namaProjectTerkait();
                                                    if ($namaHeader !== '-') {
                                                        return "{$namaHeader} (Header)";
                                                    }
                                                }

                                                return '📦 Stok Transit Gudang';
                                            })
                                            ->badge()
                                            ->color(function ($state) {
                                                if (str_contains($state, 'Stok Transit')) {
                                                    return 'gray';
                                                }
                                                if (str_contains($state, '(Header)')) {
                                                    return 'info';
                                                }
                                                return 'success';
                                            }),
                                    ])
                                    ->columns(4)
                                    ->columnSpanFull(),
                            ]),
                    ]),

                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('tanggal_transaksi', 'desc');
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