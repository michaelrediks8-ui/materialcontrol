<?php

namespace App\Observers;

use App\Models\TransaksiBarangDetail;
use App\Support\StokRebuilder;

class TransaksiBarangDetailObserver
{
    public function created(TransaksiBarangDetail $detail): void
    {
        StokRebuilder::rebuild();
    }

    public function updated(TransaksiBarangDetail $detail): void
    {
        StokRebuilder::rebuild();
    }

    public function deleted(TransaksiBarangDetail $detail): void
    {
        StokRebuilder::rebuild();
    }
}