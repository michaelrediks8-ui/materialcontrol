<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Models\Project;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationLabel = 'Project / Pekerjaan';
    protected static ?string $slug = 'projects';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Utama Project')
                    ->description('Detail pekerjaan project. Nomor PO diinput terpisah di menu Purchase Order.')
                    ->schema([
                        Forms\Components\Textarea::make('nama_project')
                            ->label('Deskripsi Pekerjaan (Project)')
                            ->placeholder('Contoh: Additional Calcedony Project...')
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('area')
                                ->label('Area / Lokasi')
                                ->placeholder('Contoh: M3, Operational, M4')
                                ->datalist([
                                    'M3',
                                    'M4',
                                    'M8',
                                    'Operational',
                                ]),

                            Forms\Components\TextInput::make('pic_unilever')
                                ->label('PIC / User')
                                ->placeholder('Contoh: Dimas Febry'),
                        ]),
                    ]),

                Forms\Components\Section::make('Jadwal & Status')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\DatePicker::make('tanggal_mulai')
                                ->label('Tanggal Mulai')
                                ->placeholder('Pilih tanggal')
                                ->native(false),

                            Forms\Components\DatePicker::make('target_selesai')
                                ->label('Target Selesai (Delivery Date)')
                                ->placeholder('Pilih tanggal')
                                ->native(false),

                            Forms\Components\Select::make('status')
                                ->label('Status Project')
                                ->options([
                                    'On Progress' => 'On Progress',
                                    'ACC' => 'ACC (Disetujui)',
                                    'Hold' => 'Hold / Tertunda',
                                    'Selesai' => 'Selesai',
                                    'Batal' => 'Batal',
                                ])
                                ->default('On Progress')
                                ->required(),
                        ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_project')
                    ->label('Deskripsi Pekerjaan')
                    ->searchable()
                    ->wrap()
                    ->sortable(),

                Tables\Columns\TextColumn::make('area')
                    ->label('Area')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->placeholder('-'),

                // 💡 Dikembalikan menggunakan 'pic_unilever' sesuai database kamu
                Tables\Columns\TextColumn::make('pic')
                    ->label('User / PIC')
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('target_selesai')
                    ->label('Target Selesai')
                    ->date('d M Y')
                    ->sortable()
                    ->placeholder('Belum ada jadwal'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'On Progress' => 'primary',
                        'ACC' => 'info',
                        'Selesai' => 'success',
                        'Hold' => 'warning',
                        'Batal' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'On Progress' => 'On Progress',
                        'ACC' => 'ACC',
                        'Selesai' => 'Selesai',
                        'Hold' => 'Hold',
                    ]),
                Tables\Filters\SelectFilter::make('area')
                    ->options([
                        'M3' => 'M3',
                        'M4' => 'M4',
                        'Operational' => 'Operational',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}