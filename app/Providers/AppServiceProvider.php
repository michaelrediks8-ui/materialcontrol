<?php

namespace App\Providers;

use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Models\TransaksiBarangDetail;
use App\Observers\TransaksiBarangDetailObserver;
use App\Models\MutasiAlatDetail;
use App\Observers\MutasiAlatDetailObserver;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {    
        // Paksa skema HTTPS jika diakses lewat ngrok / SSL
        if (str_contains(request()->header('host'), 'ngrok') || request()->server('HTTP_X_FORWARDED_PROTO') === 'https') {
            URL::forceScheme('https');
        }

        // Daftarkan Morph Map untuk mengenali tipe relasi asal dan tujuan
        Relation::morphMap([
            'gudang'         => \App\Models\Gudang::class,
            'purchase_order' => \App\Models\PurchaseOrder::class,
            'project'        => \App\Models\Project::class,
            'vendor'         => \App\Models\Vendor::class,
        ]);

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