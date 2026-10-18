<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransaksiBarangResource\Pages;
use App\Models\TransaksiBarang;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class TransaksiBarangResource extends Resource
{
    protected static ?string $model = TransaksiBarang::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';
    protected static ?string $navigationGroup = 'Stok Material';
    protected static ?string $navigationLabel = 'Transaksi Harian';

    protected static ?string $modelLabel = 'Transaksi Harian';
    protected static ?string $pluralModelLabel = 'Transaksi Harian';

    // Halaman ini hanya untuk melihat; transaksi dibuat lewat Barang Masuk / Barang Keluar / Transfer Gudang
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->reorder('no_transaksi', 'desc'))
            ->columns([
                Tables\Columns\TextColumn::make('no_transaksi')
                    ->label('No. Transaksi')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('tanggal_transaksi')
                    ->label('Tanggal & Jam')
                    ->dateTime('d M Y, H:i'),

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
                    ->label('Tujuan')
                    ->state(fn (TransaksiBarang $record): string => $record->namaTujuan())
                    ->description(function (TransaksiBarang $record): ?string {
                        $jenis = strtolower($record->jenis_transaksi ?? '');
                        $project = $record->namaProjectTerkait();

                        if ($jenis === 'masuk') {
                            if ($record->tujuan_type === 'gudang') {
                                $desc = '📦 Transit Gudang';
                                if ($project !== '-') {
                                    $desc .= " | 📌 Project: {$project}";
                                }
                                return $desc;
                            }

                            return '🚚 Langsung Drop ke Project';
                        }

                        if ($jenis === 'transfer') {
                            $desc = '🔄 Transfer Gudang';
                            if ($project !== '-') {
                                $desc .= " | 📌 Project: {$project}";
                            }
                            return $desc;
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
                    ->columnSpan(2)
                    ->form([
                        // Dibungkus Grid 2 kolom supaya dua tanggal berdampingan
                        Forms\Components\Grid::make(2)->schema([
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
                            $indicators[] = 'Dari: ' . Carbon::parse($data['dari_tanggal'])->toFormattedDateString();
                        }
                        if ($data['sampai_tanggal'] ?? null) {
                            $indicators[] = 'Sampai: ' . Carbon::parse($data['sampai_tanggal'])->toFormattedDateString();
                        }
                        return $indicators;
                    }),

                Tables\Filters\SelectFilter::make('jenis_transaksi')
                    ->label('Filter Alur Transaksi')
                    ->columnSpan(1)
                    ->options([
                        'masuk' => 'Barang Masuk',
                        'keluar' => 'Barang Keluar',
                        'transfer' => 'Transfer Gudang',
                    ]),
            ])
            // Filter tanggal di kiri, search di kanan (sejajar) — dibantu CSS global di AdminPanelProvider
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
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
                                        $jenis = strtolower($record->jenis_transaksi ?? '');

                                        if ($jenis === 'masuk') {
                                            return $record->tujuan_type === 'gudang'
                                                ? '📦 Masuk Gudang (Transit/Stok)'
                                                : '🚚 Langsung Drop ke Project';
                                        }

                                        if ($jenis === 'transfer') {
                                            return '🔄 Transfer Antar Gudang';
                                        }

                                        return '-';
                                    })
                                    ->badge()
                                    ->color(fn ($state) => match (true) {
                                        str_contains($state, 'Drop') => 'warning',
                                        str_contains($state, 'Transfer') => 'info',
                                        default => 'success',
                                    })
                                    ->visible(fn ($record) => in_array(strtolower($record->jenis_transaksi ?? ''), ['masuk', 'transfer'], true)),

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
                                                // 0.5 tampil "0,5" | 5.00 tampil "5"
                                                $formatted = number_format((float) $state, 2, ',', '.');
                                                if (str_contains($formatted, ',')) {
                                                    $formatted = rtrim(rtrim($formatted, '0'), ',');
                                                }
                                                return $formatted;
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
            ])
            ->defaultSort('tanggal_transaksi', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransaksiBarangs::route('/'),
        ];
    }
}