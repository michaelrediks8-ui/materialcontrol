<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransferGudangResource\Pages;
use App\Models\Gudang;
use App\Models\Karyawan;
use App\Models\PurchaseOrder;
use App\Models\Satuan;
use App\Models\TransaksiBarang;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TransferGudangResource extends Resource
{
    protected static ?string $model = TransaksiBarang::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationLabel = 'Transfer Gudang';
    protected static ?string $navigationGroup = 'Stok Material';
    protected static ?string $slug = 'transfer-gudang';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Transfer / Pengiriman Barang')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\DateTimePicker::make('tanggal_transaksi')
                                ->label('Tanggal & Jam Transfer')
                                ->displayFormat('d/m/Y H:i:s')
                                ->format('Y-m-d H:i:s')
                                ->seconds(true)
                                ->default(now())
                                ->required(),

                            Forms\Components\Select::make('request_oleh')
                                ->label('Di-request Oleh')
                                ->options(fn () => Karyawan::query()->pluck('nama', 'nama')->toArray())
                                ->searchable()
                                ->preload()
                                ->placeholder('Pilih Karyawan Pengaju')
                                ->nullable(),

                            Forms\Components\Select::make('diterima_oleh')
                                ->label('Diterima Oleh (Penerima Fisik)')
                                ->options(fn () => Karyawan::query()->pluck('nama', 'nama')->toArray())
                                ->searchable()
                                ->preload()
                                ->placeholder('Pilih Karyawan Penerima')
                                ->required(),
                        ]),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Hidden::make('asal_type')->default('gudang'),
                            Forms\Components\Hidden::make('jenis_transaksi')->default('transfer'),

                            Forms\Components\Select::make('asal_id')
                                ->label('Dari Gudang Asal (Pengirim)')
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

                            Forms\Components\Select::make('tujuan_type')
                                ->label('Tujuan Penurunan Barang (Fisik)')
                                ->options([
                                    'gudang' => '🏢 Antar Gudang Internal',
                                    'project' => '🚚 Langsung Drop ke Site/Project',
                                ])
                                ->default('gudang')
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (callable $set) {
                                    $set('tujuan_id', null);
                                }),
                        ]),

                        Forms\Components\Grid::make(1)->schema([
                            Forms\Components\Select::make('tujuan_id')
                                ->label(fn (callable $get) => $get('tujuan_type') === 'gudang' ? 'Pilih Gudang Penerima' : 'Pilih Site / Project Tujuan')
                                ->options(function (callable $get): array {
                                    $type = $get('tujuan_type') ?? 'gudang';
                                    if ($type === 'gudang') {
                                        return Gudang::query()
                                            ->whereNotNull('nama_gudang')
                                            ->pluck('nama_gudang', 'id')
                                            ->map(fn ($nama) => (string) $nama)
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

                Forms\Components\Section::make('Rincian Barang yang Ditransfer')
                    ->schema([
                        Forms\Components\Repeater::make('details')
                            ->relationship('details')
                            ->schema([
                                Forms\Components\Select::make('barang_id')
                                    ->label('Nama Barang')
                                    ->relationship('barang', 'nama_barang')
                                    ->getOptionLabelFromRecordUsing(fn ($record) => "[{$record->kode_barang}] {$record->nama_barang}")
                                    ->searchable(['kode_barang', 'nama_barang'])
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn (callable $set) => $set('satuan_id', null))
                                    ->columnSpan(['md' => 4]),

                                Forms\Components\TextInput::make('qty')
                                    ->label('Qty')
                                    ->numeric()
                                    ->step(1)
                                    ->minValue(1)
                                    ->required()
                                    ->columnSpan(['md' => 2]),

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
                                    ->required()
                                    ->columnSpan(['md' => 3]),

                                Forms\Components\Select::make('project_id')
                                    ->label('Alokasi Project')
                                    ->relationship('project', 'nama_project')
                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->placeholder('Opsional')
                                    ->columnSpan(['md' => 3]),
                            ])
                            ->columns(12)
                            ->defaultItems(1)
                            ->addActionLabel('＋ Tambah Item Transfer')
                            ->reorderable(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->where('jenis_transaksi', 'transfer'))
            ->columns([
                Tables\Columns\TextColumn::make('no_transaksi')
                    ->label('No. Transaksi')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('tanggal_transaksi')
                    ->label('Tanggal & Jam')
                    ->dateTime('d M Y, H:i:s')
                    ->sortable(),

                Tables\Columns\TextColumn::make('asal')
                    ->label('Gudang Asal')
                    ->state(fn (TransaksiBarang $record): string => $record->namaAsal())
                    ->sortable(false),

                Tables\Columns\TextColumn::make('tujuan')
                    ->label('Tujuan Penerima')
                    ->state(fn (TransaksiBarang $record): string => $record->namaTujuan())
                    ->description(fn (TransaksiBarang $record): string => $record->tujuan_type === 'project' ? '🚚 Langsung ke Project' : '🏢 Antar Gudang')
                    ->sortable(false),

                Tables\Columns\TextColumn::make('diterima_oleh')
                    ->label('Penerima / PJ')
                    ->searchable(),

                Tables\Columns\TextColumn::make('details_count')
                    ->label('Total Item')
                    ->counts('details')
                    ->badge()
                    ->color('info'),
            ])
            ->filters([])
            ->actions([
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
            'index' => Pages\ListTransferGudangs::route('/'),
            'create' => Pages\CreateTransferGudang::route('/create'),
            'edit' => Pages\EditTransferGudang::route('/{record}/edit'),
        ];
    }
}