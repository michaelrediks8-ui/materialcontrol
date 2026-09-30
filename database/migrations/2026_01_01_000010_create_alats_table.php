<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alats', function (Blueprint $table) {
            $table->id();
            $table->string('kode_alat')->unique(); // EL01, MN01, OP01, dst
            $table->string('nama_alat');
            $table->string('kategori')->nullable(); // Elektrik, Manual, Operasional, dst
            $table->string('merk_catatan')->nullable();
            $table->string('satuan')->default('Unit');
            $table->integer('jumlah_total')->default(0);
            $table->integer('jumlah_rusak')->default(0);
            $table->timestamps();

            // "sedang dipinjam" & "sisa tersedia" TIDAK disimpan di sini —
            // selalu dihitung real-time dari tabel peminjaman_alats (lihat model Alat)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alats');
    }
};
