<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Management User';
    protected static ?string $navigationGroup = 'Pengaturan Sistem';
    protected static ?string $slug = 'users';
    protected static ?int $navigationSort = 99;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Akun User')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('Alamat Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('no_hp')
                            ->label('No. WhatsApp / HP')
                            ->tel()
                            ->placeholder('081234567890'),

                        Forms\Components\Select::make('role')
                            ->label('Peran / Hak Akses (Role)')
                            ->options([
                                'admin' => '👑 Super Admin / Owner',
                                'gudang' => '📦 Petugas Gudang',
                                'project' => '🏗️ Tim Project / Site Manager',
                                'staff' => '👤 Staf Biasa / Viewer',
                            ])
                            ->default('staff')
                            ->required()
                            ->live(),

                        // Model sudah memakai cast 'hashed', jadi tidak perlu Hash::make lagi di sini
                        Forms\Components\TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->placeholder(fn (string $context) => $context === 'edit' ? 'Kosongkan jika tidak ubah password' : ''),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Status Akun Aktif')
                            ->helperText('Jika dinonaktifkan, user ini tidak bisa login ke sistem.')
                            ->default(true)
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Hak Akses Data')
                    ->description('Tentukan apa yang boleh dilakukan user ini dan di gudang mana.')
                    ->schema([
                        Forms\Components\Placeholder::make('info_admin')
                            ->hiddenLabel()
                            ->content('👑 Super Admin otomatis punya akses penuh ke semua gudang dan semua izin.')
                            ->visible(fn (callable $get) => $get('role') === 'admin')
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('can_input')
                            ->label('Boleh Input Data')
                            ->helperText('Menambah data baru (registrasi, barang masuk, dll).')
                            ->default(true)
                            ->visible(fn (callable $get) => $get('role') !== 'admin'),

                        Forms\Components\Toggle::make('can_keluar')
                            ->label('Boleh Mengeluarkan Barang / Alat')
                            ->helperText('Transfer, pemakaian, dan peminjaman.')
                            ->default(true)
                            ->visible(fn (callable $get) => $get('role') !== 'admin'),

                        Forms\Components\Toggle::make('can_delete')
                            ->label('Boleh Hapus Data')
                            ->helperText('Menghapus data transaksi.')
                            ->default(false)
                            ->visible(fn (callable $get) => $get('role') !== 'admin'),

                        Forms\Components\Select::make('gudangs')
                            ->label('Gudang / Area yang Boleh Diakses')
                            ->relationship('gudangs', 'nama_gudang')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->helperText('User hanya bisa input dan mengeluarkan di gudang yang dipilih.')
                            ->visible(fn (callable $get) => $get('role') !== 'admin')
                            ->columnSpanFull(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('no_hp')
                    ->label('No. HP')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'admin' => 'SUPER ADMIN',
                        'gudang' => 'PETUGAS GUDANG',
                        'project' => 'TIM PROJECT',
                        default => 'STAFF / VIEWER',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'gudang' => 'warning',
                        'project' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('gudangs.nama_gudang')
                    ->label('Gudang')
                    ->badge()
                    ->color('gray')
                    ->placeholder(fn (User $record) => $record->isAdmin() ? 'Semua Gudang' : 'Belum diatur'),

                Tables\Columns\IconColumn::make('can_input')
                    ->label('Input')
                    ->boolean()
                    ->state(fn (User $record) => $record->bolehInput()),

                Tables\Columns\IconColumn::make('can_keluar')
                    ->label('Keluar')
                    ->boolean()
                    ->state(fn (User $record) => $record->bolehKeluar()),

                Tables\Columns\IconColumn::make('can_delete')
                    ->label('Hapus')
                    ->boolean()
                    ->state(fn (User $record) => $record->bolehHapus()),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->dateTime('d M Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'gudang' => 'Gudang',
                        'project' => 'Project',
                        'staff' => 'Staff',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn (User $record) => $record->id === auth()->id()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function canViewAny(): bool
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        return $user && $user->isAdmin();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}