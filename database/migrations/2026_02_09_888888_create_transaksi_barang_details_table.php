<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksi_barang_details', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('transaksi_barang_id')
                  ->constrained('transaksi_barangs')
                  ->cascadeOnDelete();

            $table->foreignId('barang_id')->constrained('barangs');
            $table->decimal('qty', 12, 2);
            $table->string('satuan_catatan')->nullable();
            $table->text('catatan_item')->nullable();

            $table->timestamps();

            $table->index(['transaksi_barang_id', 'barang_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi_barang_details');
    }
};