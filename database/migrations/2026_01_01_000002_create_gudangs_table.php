<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gudangs', function (Blueprint $table) {
            $table->id();
            $table->string('kode_gudang')->unique()->nullable();
            $table->string('nama_gudang')->unique(); // Gudang M4, Gudang M3, Gudang Landbaw
            $table->text('alamat_lokasi')->nullable();
            $table->string('pic_gudang')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gudangs');
    }
};
