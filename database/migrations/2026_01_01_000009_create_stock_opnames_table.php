<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gudang_id')->constrained('gudangs');
            $table->foreignId('barang_id')->constrained('barangs');
            $table->date('tanggal_opname');
            $table->decimal('stok_sistem', 12, 2); // snapshot hasil hitung sistem saat opname dilakukan
            $table->decimal('stok_fisik', 12, 2);
            // selisih tidak disimpan permanen — dihitung on the fly (stok_fisik - stok_sistem)
            // supaya tidak ada resiko data usang; kalau perlu kolom generated, bisa ditambah belakangan
            $table->string('petugas')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['gudang_id', 'tanggal_opname']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_opnames');
    }
};
