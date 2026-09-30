<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VoucherPembelian extends Model
{
    protected $table = 'voucher_pembelian';

    protected $fillable = [
        'no_voucher', 'vendor_id', 'tanggal',
        'status_bayar', 'tanggal_dibayar', 'catatan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'tanggal_dibayar' => 'date',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransaksiBarang::class, 'voucher_pembelian_id');
    }

    public function totalQty(): float
    {
        return $this->items()->sum('qty');
    }
}
