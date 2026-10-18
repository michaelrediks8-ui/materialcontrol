<?php

namespace App\Providers;

use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Support\ServiceProvider;
use App\Models\TransaksiBarangDetail;
use App\Observers\TransaksiBarangDetailObserver;
use App\Models\MutasiAlatDetail;
use App\Observers\MutasiAlatDetailObserver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        TransaksiBarangDetail::observe(TransaksiBarangDetailObserver::class);
        MutasiAlatDetail::observe(MutasiAlatDetailObserver::class);

        // Filter tampil di atas tabel, responsif: 1 kolom di HP, 2 di tablet, 4 di layar lebar
        Table::configureUsing(function (Table $table): void {
            $table
                ->filtersLayout(FiltersLayout::AboveContent)
                ->filtersFormColumns([
                    'default' => 1,
                    'md' => 2,
                    'xl' => 4,
                ]);
        });
    }
}