<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barangs', function (Blueprint $table) {
            $table->id();
            $table->string('kode_barang')->unique(); 
            $table->string('nama_barang');
            $table->string('spesifikasi')->nullable();
            $table->string('satuan_besar')->nullable();
            $table->decimal('isi_per_satuan_besar', 12, 2)->nullable();
            $table->string('satuan_kecil');
            $table->string('jenis_barang')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index('nama_barang');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barangs');
    }
};
