<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JabatanResource\Pages;
use App\Models\Jabatan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class JabatanResource extends Resource
{
    protected static ?string $model = Jabatan::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationLabel = 'Jabatan';
    protected static ?string $slug = 'jabatans';
    protected static ?string $modelLabel = 'Jabatan';
    protected static ?string $pluralModelLabel = 'Jabatan';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\TextInput::make('nama_jabatan')
                    ->label('Nama Jabatan')
                    ->placeholder('Contoh: Foreman')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_jabatan')
                    ->label('Jabatan')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('karyawans_count')
                    ->label('Jumlah Karyawan')
                    ->counts('karyawans')
                    ->badge()
                    ->color('info')
                    ->sortable(),
            ])
            ->defaultSort('nama_jabatan')
            ->actions([
                Tables\Actions\EditAction::make(),

                // Satukan jabatan yang penulisannya berbeda (mis. "Civil Worker" -> "Civil/Tukang")
                Tables\Actions\Action::make('gabung')
                    ->label('Gabungkan')
                    ->icon('heroicon-o-arrows-pointing-in')
                    ->color('warning')
                    ->modalHeading(fn (Jabatan $record) => "Gabungkan \"{$record->nama_jabatan}\" ke jabatan lain")
                    ->modalDescription('Semua karyawan jabatan ini dipindah ke jabatan tujuan, lalu jabatan ini dihapus.')
                    ->form(fn (Jabatan $record) => [
                        Forms\Components\Select::make('tujuan_id')
                            ->label('Gabungkan ke')
                            ->options(fn () => Jabatan::where('id', '!=', $record->id)->orderBy('nama_jabatan')->pluck('nama_jabatan', 'id'))
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (Jabatan $record, array $data) {
                        $tujuan = Jabatan::findOrFail($data['tujuan_id']);

                        \App\Models\Karyawan::where('jabatan_id', $record->id)->update([
                            'jabatan_id' => $tujuan->id,
                            'jabatan' => $tujuan->nama_jabatan,
                        ]);
                        $record->delete();

                        Notification::make()->title("Digabungkan ke {$tujuan->nama_jabatan}")->success()->send();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->before(function (Jabatan $record, Tables\Actions\DeleteAction $action) {
                        if ($record->karyawans()->exists()) {
                            Notification::make()
                                ->title('Tidak bisa dihapus')
                                ->body('Masih ada karyawan dengan jabatan ini. Pindahkan dulu atau pakai tombol Gabungkan.')
                                ->danger()
                                ->send();
                            $action->halt();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJabatans::route('/'),
            'create' => Pages\CreateJabatan::route('/create'),
            'edit' => Pages\EditJabatan::route('/{record}/edit'),
        ];
    }
}