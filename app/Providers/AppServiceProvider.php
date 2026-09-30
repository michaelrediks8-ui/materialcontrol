<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\TransaksiBarangDetail;
use App\Observers\TransaksiBarangDetailObserver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Observer dipasang di DETAIL (bukan header) — supaya jalan SETELAH
        // rincian barang dari Repeater selesai tersimpan, bukan sebelumnya.
        TransaksiBarangDetail::observe(TransaksiBarangDetailObserver::class);
    }
}
