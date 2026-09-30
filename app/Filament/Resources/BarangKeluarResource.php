<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BarangKeluarResource\Pages;
use App\Models\Gudang;
use App\Models\Karyawan; 
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\Satuan;
use App\Models\TransaksiBarang;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Awcodes\TableRepeater\Components\TableRepeater;
use Awcodes\TableRepeater\Header;

class BarangKeluarResource extends Resource
{
    protected static ?string $model = TransaksiBarang::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';
    protected static ?string $navigationLabel = 'Barang Keluar';
    protected static ?string $navigationGroup = 'Stok Material';
    protected static ?string $slug = 'barang-keluar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Pengeluaran Barang (Outbound)')
                    ->schema([
                        // Hidden field otomatis
                        Forms\Components\Hidden::make('jenis_transaksi')->default('keluar'),
                        Forms\Components\Hidden::make('asal_type')->default('gudang'),
                        Forms\Components\Hidden::make('tujuan_type')->default('purchase_order'),
                        Forms\Components\Hidden::make('purchase_order_id'),

                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\DateTimePicker::make('tanggal_transaksi')
                                ->label('Tanggal & Jam Keluar')
                                ->displayFormat('d/m/Y H:i:s')
                                ->format('Y-m-d H:i:s')
                                ->seconds(true)
                                ->default(now())
                                ->required(),

                            /* 💡 SELEKSI DARI TABEL KARYAWAN */
                            Forms\Components\Select::make('request_oleh')
                                ->label('Diminta Oleh (Mandor/User)')
                                ->options(fn () => Karyawan::query()->pluck('nama', 'nama')->toArray())
                                ->searchable()
                                ->preload()
                                ->placeholder('Pilih Karyawan (Peminta)')
                                ->required(),

                            Forms\Components\Select::make('diterima_oleh')
                                ->label('Diambil / Diterima Oleh')
                                ->options(fn () => Karyawan::query()->pluck('nama', 'nama')->toArray())
                                ->searchable()
                                ->preload()
                                ->placeholder('Pilih Karyawan (Penerima)')
                                ->required(),
                        ]),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('asal_id')
                                ->label('Dari Gudang Asal')
                                ->options(function (): array {
                                    return Gudang::query()
                                        ->whereNotNull('nama_gudang')
                                        ->pluck('nama_gudang', 'id')
                                        ->map(fn ($nama) => (string) $nama)
                                        ->toArray();
                                })
                                ->searchable()
                                ->preload()
                                ->required(),

                            Forms\Components\Select::make('tujuan_id')
                                ->label('Ke Project / PO Tujuan')
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
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                    if ($state) {
                                        $po = PurchaseOrder::with('project')->find($state);
                                        if ($po && $po->project) {
                                            $set('area', $po->project->area);
                                            $set('job_description', $po->project->nama_project);
                                            $set('purchase_order_id', $state);
                                        }
                                    }
                                }),
                        ]),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('area')
                                ->label('Area / Site Location')
                                ->placeholder('Autofill dari Project')
                                ->dehydrated(false)
                                ->nullable(),

                            Forms\Components\Textarea::make('job_description')
                                ->label('Job Description / Peruntukan Pekerjaan')
                                ->placeholder('Autofill dari Project')
                                ->rows(2)
                                ->nullable(),
                        ]),
                    ]),

                Forms\Components\Section::make('Rincian Barang yang Dikeluarkan')
                    ->schema([
                        TableRepeater::make('details')
                            ->relationship('details')
                            ->headers([
                                Header::make('barang_id')->label('Nama Barang')->width('45%'),
                                Header::make('qty')->label('Qty')->width('25%'),
                                Header::make('satuan_id')->label('Satuan')->width('30%'),
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
                                    ->label('Jumlah')
                                    ->numeric()
                                    ->step('any')
                                    ->minValue(0.01)
                                    ->default(null)
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
                            ])
                            ->defaultItems(1)
                            ->addActionLabel('＋ Tambah Item Keluar')
                            ->reorderable(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->where('jenis_transaksi', 'keluar'))
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

                Tables\Columns\TextColumn::make('request_oleh')
                    ->label('Peminta')
                    ->searchable(),

                Tables\Columns\TextColumn::make('diterima_oleh')
                    ->label('Penerima')
                    ->searchable(),

                Tables\Columns\TextColumn::make('asal')
                    ->label('Dari Gudang')
                    ->state(fn (TransaksiBarang $record): string => $record->namaAsal() ?? '-'),

                Tables\Columns\TextColumn::make('tujuan')
                    ->label('Ke Project')
                    ->state(fn (TransaksiBarang $record): string => $record->namaTujuan() ?? '-'),

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
                    ->modalHeading('Rincian Barang Keluar')
                    ->infolist([
                        \Filament\Infolists\Components\Section::make('Informasi Transaksi')
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('no_transaksi')
                                    ->label('No. Transaksi')
                                    ->weight('bold'),

                                \Filament\Infolists\Components\TextEntry::make('tanggal_transaksi')
                                    ->label('Tanggal & Jam')
                                    ->dateTime('d M Y, H:i'),

                                \Filament\Infolists\Components\TextEntry::make('request_oleh')
                                    ->label('Diminta Oleh')
                                    ->placeholder('-'),

                                \Filament\Infolists\Components\TextEntry::make('diterima_oleh')
                                    ->label('Diambil / Diterima Oleh')
                                    ->placeholder('-'),

                                \Filament\Infolists\Components\TextEntry::make('asal_view')
                                    ->label('Dari Gudang Asal')
                                    ->state(fn ($record) => $record->namaAsal()),

                                \Filament\Infolists\Components\TextEntry::make('tujuan_view')
                                    ->label('Ke Project Tujuan')
                                    ->state(fn ($record) => $record->namaTujuan())
                                    ->weight('bold')
                                    ->color('primary'),
                            ])->columns(2),

                        \Filament\Infolists\Components\Section::make('Rincian Barang')
                            ->schema([
                                \Filament\Infolists\Components\RepeatableEntry::make('details')
                                    ->label('')
                                    ->schema([
                                        \Filament\Infolists\Components\TextEntry::make('barang.nama_barang')
                                            ->label('Nama Barang'),
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
                                    ])->columns(3),
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
            'index' => Pages\ListBarangKeluars::route('/'),
            'create' => Pages\CreateBarangKeluar::route('/create'),
            'edit' => Pages\EditBarangKeluar::route('/{record}/edit'),
        ];
    }
}