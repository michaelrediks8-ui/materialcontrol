<?php

namespace App\Observers;

use App\Models\MutasiAlatDetail;
use App\Support\AlatStokRebuilder;

class MutasiAlatDetailObserver
{
    public function created(MutasiAlatDetail $detail): void
    {
        AlatStokRebuilder::rebuild();
    }

    public function updated(MutasiAlatDetail $detail): void
    {
        AlatStokRebuilder::rebuild();
    }

    public function deleted(MutasiAlatDetail $detail): void
    {
        AlatStokRebuilder::rebuild();
    }
}