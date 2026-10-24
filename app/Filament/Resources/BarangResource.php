<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BarangResource\Pages;
use App\Models\Barang;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BarangResource extends Resource
{
    protected static ?string $model = Barang::class;
    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationLabel = 'Barang';
    protected static ?string $recordTitleAttribute = 'nama_barang';
    protected static ?string $modelLabel = 'Barang';
    protected static ?string $pluralModelLabel = 'Barang';

    public static function form(Form $form): Form
    {
        return $form->schema([

           
            Forms\Components\TextInput::make('kode_barang')
                ->label('Kode Barang')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(50)
                ->default(
                    fn (string $operation) =>
                        $operation === 'create'
                            ? Barang::generateNextCode()
                            : null
                )
                ->disabled(
                    fn (string $operation) =>
                        $operation === 'create'
                )
                ->dehydrated(),

      
            Forms\Components\TextInput::make('nama_barang')
                ->label('Nama Barang')
                ->required()
                ->maxLength(255)
                ->helperText(
                    'Nama umum barang, misal "Cable Ties". Variasi ukuran/warna diisi di Spesifikasi.'
                ),
                Forms\Components\Select::make('kategori_barang_id')
               ->label('Kategori Barang')
               ->relationship('kategoriBarang', 'nama_kategori')
               ->searchable()
               ->preload(),

           
            Forms\Components\TextInput::make('spesifikasi')
                ->label('Spesifikasi')
                ->maxLength(255)
                ->helperText(
                    'Contoh: 150mm, 3x2.5mm, Merah, dll.'
                ),

          
            Forms\Components\Select::make('satuan_id')
                ->label('Satuan Kecil')
                ->relationship('satuan', 'nama_satuan')
                ->searchable()
                ->preload()
                ->required(),

           
            Forms\Components\Select::make('satuan_besar_id')
                ->label('Satuan Besar')
                ->relationship('satuanBesar', 'nama_satuan')
                ->searchable()
                ->preload()
                ->helperText('Kosongkan kalau barang ini tidak punya satuan besar. Contoh: Box, Roll.'),

            Forms\Components\TextInput::make('isi_per_satuan_besar')
                ->label('Isi per Satuan Besar')
                ->numeric()
                ->step('any')
                ->placeholder('Contoh: 12 (untuk Lusin)')
                // Hilangkan desimal berlebih saat data di-load ke form edit (1,00 jadi 1)
                ->formatStateUsing(function ($state) {
                    if ($state === null) return null;
                    $val = (float) $state;
                    return $val == floor($val) ? (int) $val : $val;
                }),

            Forms\Components\Select::make('jenis_barang')
                ->label('Jenis Barang')
                ->options([
                    'Consumable' => 'Consumable',
                    'NonConsumable' => 'NonConsumable',
                ])
                ->searchable(),

            /*
             * CATATAN
             */
            Forms\Components\Textarea::make('catatan')
                ->label('Catatan')
                ->columnSpanFull(),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            // Muat relasi sekali (bukan satu per baris) agar daftar tetap ringan
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['kategoriBarang', 'satuan', 'satuanBesar']))
            // Pencarian cepat: jeda singkat setelah mengetik, tiap kata dicari di kode / nama / spesifikasi
            ->searchDebounce('150ms')
            ->searchPlaceholder('Cari kode, nama, atau spesifikasi...')
            ->defaultSort('nama_barang')
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->columns([

                Tables\Columns\TextColumn::make('kode_barang')
                    ->label('Kode')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('nama_barang')
                    ->label('Nama Barang')
                    ->searchable()
                    ->sortable(),


                Tables\Columns\TextColumn::make('kategoriBarang.nama_kategori')
    ->label('Kategori')
    ->badge()
    ->placeholder('-'),

                Tables\Columns\TextColumn::make('spesifikasi')
                    ->label('Spesifikasi')
                    ->searchable(),

                // FIX: sebelumnya baca 'satuan_kecil' (kolom teks lama yang
                // sudah tidak dipakai) — sekarang baca relasi satuan_id yang benar.
                Tables\Columns\TextColumn::make('satuan.nama_satuan')
                    ->label('Satuan'),

                // FIX: sebelumnya baca 'satuan_besar' (teks) — sekarang relasi satuan_besar_id.
                Tables\Columns\TextColumn::make('satuanBesar.nama_satuan')
                    ->label('Satuan Besar')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('isi_per_satuan_besar')
                    ->label('Isi / Satuan Besar')
                    ->formatStateUsing(function ($state) {
                        if ($state === null) return '-';
                        $val = (float) $state;
                        return $val == floor($val)
                            ? number_format($val, 0, ',', '.')
                            : rtrim(rtrim(number_format($val, 2, ',', '.'), '0'), ',');
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('jenis_barang')
                    ->label('Jenis')
                    ->badge(),

            ])

            ->filters([

                Tables\Filters\SelectFilter::make('jenis_barang')
                    ->label('Jenis Barang')
                    ->options([
                        'Consumable' => 'Consumable',
                        'NonConsumable' => 'NonConsumable',
                    ]),

            ])

            ->actions([
                Tables\Actions\EditAction::make(),
            ])

            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBarangs::route('/'),
            'create' => Pages\CreateBarang::route('/create'),
            'edit' => Pages\EditBarang::route('/{record}/edit'),
        ];
    }
}