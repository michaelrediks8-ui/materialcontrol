<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransaksiBarangResource\Pages;
use App\Models\Gudang;
use App\Models\Karyawan; 
use App\Models\Project;
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
use Carbon\Carbon;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;

class TransaksiBarangResource extends Resource
{
    protected static ?string $model = TransaksiBarang::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';
    protected static ?string $navigationGroup = 'Stok Material';
    protected static ?string $navigationLabel = 'Transaksi Harian';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Transaksi')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\DateTimePicker::make('tanggal_transaksi')
                                ->label('Tanggal & Waktu Transaksi')
                                ->displayFormat('d/m/Y H:i:s')
                                ->format('Y-m-d H:i:s')
                                ->seconds(true)
                                ->default(now())
                                ->required(),

                            Forms\Components\Select::make('jenis_transaksi')
                                ->label('Jenis Alur Transaksi')
                                ->options([
                                    'masuk' => '📥 Barang Masuk (Inbound)',
                                    'keluar' => '📤 Barang Keluar (Outbound)',
                                    'transfer' => '🔄 Transfer Antar Gudang',
                                ])
                                ->default('masuk')
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (callable $set, $state) {
                                    if ($state === 'masuk') {
                                        $set('asal_type', 'vendor');
                                        $set('tujuan_type', 'gudang');
                                    } elseif ($state === 'keluar') {
                                        $set('asal_type', 'gudang');
                                        $set('tujuan_type', 'purchase_order');
                                    } else {
                                        $set('asal_type', 'gudang');
                                        $set('tujuan_type', 'gudang');
                                    }

                                    $set('asal_id', null);
                                    $set('tujuan_id', null);
                                    $set('purchase_order_id', null);
                                })
                                ->columnSpanFull(),
                        ]),

                        /* ASAL BARANG */
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('asal_type')
                                ->label('Dari (Asal Barang)')
                                ->options([
                                    'vendor' => 'Vendor / Supplier',
                                    'gudang' => 'Gudang Internal',
                                ])
                                ->default('vendor')
                                ->required()
                                ->live(),

                            Forms\Components\Select::make('asal_id')
                                ->label('Pilih Asal')
                                ->options(function (callable $get): array {
                                    $type = $get('asal_type') ?? 'vendor';
                                    if ($type === 'vendor') {
                                        return Vendor::query()->whereNotNull('nama_vendor')->pluck('nama_vendor', 'id')->map(fn ($val) => (string) $val)->toArray();
                                    }
                                    return Gudang::query()->whereNotNull('nama_gudang')->pluck('nama_gudang', 'id')->map(fn ($val) => (string) $val)->toArray();
                                })
                                ->searchable()
                                ->preload()
                                ->required(),
                        ]),

                        /* TUJUAN PENURUNAN BARANG */
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('tujuan_type')
                                ->label('Opsi Penurunan Barang')
                                ->options([
                                    'gudang' => '🏢 Masuk Gudang (Transit/Stok)',
                                    'purchase_order' => '🚚 Langsung Drop ke Project',
                                ])
                                ->default('gudang')
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (callable $set) {
                                    $set('tujuan_id', null);
                                }),

                            Forms\Components\Select::make('tujuan_id')
                                ->label(fn (callable $get) => $get('tujuan_type') === 'gudang' ? 'Pilih Gudang Penerima' : 'Pilih Project / PO Tujuan')
                                ->options(function (callable $get): array {
                                    $type = $get('tujuan_type') ?? 'gudang';
                                    if ($type === 'purchase_order' || $type === 'project') {
                                        return PurchaseOrder::with('project')
                                            ->get()
                                            ->mapWithKeys(function (PurchaseOrder $po) {
                                                $namaProject = $po->project->nama_project ?? 'Tanpa Nama Project';
                                                return [$po->id => "{$po->no_po} - {$namaProject}"];
                                            })
                                            ->toArray();
                                    }
                                    return Gudang::query()->whereNotNull('nama_gudang')->pluck('nama_gudang', 'id')->map(fn ($val) => (string) $val)->toArray();
                                })
                                ->searchable()
                                ->preload()
                                ->required(),
                        ]),

                        /* PERUNTUKAN PROJECT (KHUSUS BARANG MASUK KE GUDANG TRANSIT) */
                        Forms\Components\Grid::make(1)->schema([
                            Forms\Components\Select::make('purchase_order_id')
                                ->label('Untuk Project / PO Mana? (Opsional)')
                                ->helperText('Isi field ini jika barang diturunkan di gudang transit tapi sudah memiliki alokasi proyek. Sistem akan otomatis mengisi alokasi project pada tabel item di bawah.')
                                ->options(function () {
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
                                ->live() // 💡 Membuatnya Live agar bisa men-trigger auto-fill ke repeater
                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                    if ($state) {
                                        $po = PurchaseOrder::with('project')->find($state);
                                        if ($po && $po->project) {
                                            // 💡 AUTO-FILL: Mengisi project_id di setiap baris item repeater
                                            $details = $get('details') ?? [];
                                            foreach ($details as $key => $item) {
                                                $set("details.{$key}.project_id", $po->project->id);
                                            }
                                        }
                                    }
                                })
                                ->visible(fn (callable $get) => $get('jenis_transaksi') === 'masuk' && $get('tujuan_type') === 'gudang'),
                        ]),

                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('no_surat_jalan')
                                ->label('No. Surat Jalan / Bon (Opsional)')
                                ->maxLength(255),

                            Forms\Components\Select::make('request_oleh')
                                ->label('Diminta Oleh')
                                ->options(fn () => Karyawan::query()->pluck('nama', 'nama')->toArray())
                                ->searchable()
                                ->preload()
                                ->placeholder('Pilih Karyawan')
                                ->nullable(),

                            Forms\Components\Select::make('diterima_oleh')
                                ->label('Diterima Oleh')
                                ->options(fn () => Karyawan::query()->pluck('nama', 'nama')->toArray())
                                ->searchable()
                                ->preload()
                                ->placeholder('Pilih Karyawan')
                                ->nullable(),
                        ]),
                    ]),

                Forms\Components\Section::make('Daftar Barang')
                    ->schema([
                        TableRepeater::make('details')
                            ->relationship('details')
                            ->headers([
                                Header::make('barang_id')->label('Nama Barang')->width('35%'),
                                Header::make('qty')->label('Qty')->width('15%'),
                                Header::make('satuan_id')->label('Satuan')->width('20%'),
                                Header::make('project_id')->label('Alokasi Project')->width('30%'),
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
                                    ->relationship('project', 'nama_project')
                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->placeholder('Ikut project induk')
                                    ->default(function (Forms\Get $get) {
                                        // 💡 Menarik project dari header jika user klik 'Tambah Barang Lain'
                                        $poId = $get('../../purchase_order_id');
                                        if ($poId) {
                                            $po = PurchaseOrder::find($poId);
                                            return $po->project_id ?? null;
                                        }
                                        return null;
                                    }),
                            ])
                            ->defaultItems(1)
                            ->addActionLabel('＋ Tambah Barang Lain')
                            ->reorderable(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
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

                Tables\Columns\TextColumn::make('jenis_transaksi')
                    ->label('Alur')
                    ->badge()
                    ->state(function (TransaksiBarang $record): string {
                        return match (strtolower($record->jenis_transaksi ?? '')) {
                            'masuk' => 'Masuk',
                            'keluar' => 'Keluar',
                            'transfer' => 'Transfer',
                            default => 'Masuk',
                        };
                    })
                    ->color(function (string $state): string {
                        return match ($state) {
                            'Masuk' => 'success',
                            'Keluar' => 'danger',
                            'Transfer' => 'info',
                            default => 'gray',
                        };
                    }),

                Tables\Columns\TextColumn::make('asal')
                    ->label('Asal')
                    ->state(fn (TransaksiBarang $record): string => $record->namaAsal())
                    ->limit(30)
                    ->tooltip(fn (TransaksiBarang $record): string => $record->namaAsal())
                    ->sortable(false),

                Tables\Columns\TextColumn::make('tujuan')
                    ->label('Lokasi Penurunan')
                    ->state(fn (TransaksiBarang $record): string => $record->namaTujuan())
                    ->description(function (TransaksiBarang $record): ?string {
                        $isMasuk = strtolower($record->jenis_transaksi ?? '') === 'masuk';
                        $project = $record->namaProjectTerkait();

                        if ($isMasuk) {
                            if ($record->tujuan_type === 'gudang') {
                                $desc = '📦 Transit Gudang';
                                if ($project !== '-') {
                                    $desc .= " | 📌 Project: {$project}";
                                }
                                return $desc;
                            } else {
                                return '🚚 Langsung Drop ke Project';
                            }
                        }

                        return null;
                    })
                    ->limit(45)
                    ->tooltip(fn (TransaksiBarang $record): string => $record->namaTujuan())
                    ->sortable(false),

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
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['dari_tanggal'] ?? null) {
                            $indicators[] = 'Dari: ' . Carbon::parse($data['dari_tanggal'])->toFormattedDateString();
                        }
                        if ($data['sampai_tanggal'] ?? null) {
                            $indicators[] = 'Sampai: ' . Carbon::parse($data['sampai_tanggal'])->toFormattedDateString();
                        }
                        return $indicators;
                    }),

                Tables\Filters\SelectFilter::make('jenis_transaksi')
                    ->label('Filter Alur Transaksi')
                    ->options([
                        'masuk' => 'Barang Masuk',
                        'keluar' => 'Barang Keluar',
                        'transfer' => 'Transfer Gudang',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('View')
                    ->modalHeading('Rincian Transaksi Barang')
                    ->infolist([
                        \Filament\Infolists\Components\Section::make('Informasi Transaksi')
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('no_transaksi')
                                    ->label('No. Transaksi')
                                    ->weight('bold'),

                                \Filament\Infolists\Components\TextEntry::make('tanggal_transaksi')
                                    ->label('Tanggal & Jam')
                                    ->dateTime('d M Y, H:i'),

                                \Filament\Infolists\Components\TextEntry::make('asal_view')
                                    ->label('Asal Barang')
                                    ->state(fn ($record) => $record->namaAsal()),

                                \Filament\Infolists\Components\TextEntry::make('tujuan_view')
                                    ->label('Lokasi Penurunan Barang')
                                    ->state(fn ($record) => $record->namaTujuan())
                                    ->weight('bold'),

                                \Filament\Infolists\Components\TextEntry::make('tipe_penurunan_view')
                                    ->label('Tipe Penurunan')
                                    ->state(function ($record) {
                                        if (strtolower($record->jenis_transaksi ?? '') === 'masuk') {
                                            return $record->tujuan_type === 'gudang'
                                                ? '📦 Masuk Gudang (Transit/Stok)'
                                                : '🚚 Langsung Drop ke Project';
                                        }
                                        return '-';
                                    })
                                    ->badge()
                                    ->color(fn ($state) => str_contains($state, 'Drop') ? 'warning' : 'success')
                                    ->visible(fn ($record) => strtolower($record->jenis_transaksi ?? '') === 'masuk'),

                                \Filament\Infolists\Components\TextEntry::make('project_view')
                                    ->label('Peruntukan Project / Job Description')
                                    ->state(fn ($record) => $record->namaProjectTerkait())
                                    ->color('primary')
                                    ->weight('bold')
                                    ->columnSpanFull()
                                    ->visible(fn ($record) => $record->namaProjectTerkait() !== '-'),

                                \Filament\Infolists\Components\TextEntry::make('request_oleh')
                                    ->label('Di-request Oleh')
                                    ->placeholder('-'),

                                \Filament\Infolists\Components\TextEntry::make('diterima_oleh')
                                    ->label('Diterima Oleh')
                                    ->placeholder('-'),
                            ])->columns(2),

                        \Filament\Infolists\Components\Section::make('Rincian Barang & Alokasi Project')
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
                                            
                                        // 💡 LOGIKA VIEW ACTION MENGGUNAKAN FALLBACK NAMA PROJECT BARU
                                        \Filament\Infolists\Components\TextEntry::make('alokasi_project')
                                            ->label('Alokasi Project')
                                            ->state(function ($record) {
                                                $parent = $record->transaksiBarang ?? $record->transaksi;
                                                if ($parent && method_exists($parent, 'namaProjectItem')) {
                                                    return $parent->namaProjectItem($record);
                                                }
                                                return $record->project->nama_project ?? '-';
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
                                    ])->columns(4),
                            ]),
                    ]),

                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('tanggal_transaksi', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransaksiBarangs::route('/'),
            'create' => Pages\CreateTransaksiBarang::route('/create'),
            'edit' => Pages\EditTransaksiBarang::route('/{record}/edit'),
        ];
    }
}