<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseOrderResource\Pages;
use App\Models\PurchaseOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    // 💡 Icon dan posisi menu di Sidebar
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'P0-Project';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Purchase Order')
                    ->description('Daftarkan nomor Surat PO dari pusat atau buat nomor PO Internal untuk ditautkan ke Project.')
                    ->schema([
                        Forms\Components\TextInput::make('no_po')
                            ->label('Nomor PO')
                            ->placeholder('Contoh: PO12345 atau PO-INTERNAL-01')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\Select::make('project_id')
                            ->label('Tautkan ke Project')
                            ->relationship('project', 'nama_project')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Pilih project yang akan menggunakan PO ini agar muncul di form Barang Masuk.'),

                        Forms\Components\DatePicker::make('tanggal_po')
                            ->label('Tanggal PO (Opsional)')
                            ->default(now()),

//                        Forms\Components\Textarea::make('keterangan')
//                            ->label('Keterangan / Catatan (Opsional)')
//                            ->placeholder('Tambahkan catatan jika diperlukan...')
//                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('no_po')
                    ->label('Nomor PO')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('project.nama_project')
                    ->label('Peruntukan Project')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('tanggal_po')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'edit' => Pages\EditPurchaseOrder::route('/{record}/edit'),
        ];
    }
}