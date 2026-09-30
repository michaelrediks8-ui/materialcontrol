<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alats', function (Blueprint $table) {
            // lokasi penyimpanan default saat ini (bukan status pinjam — itu dari peminjaman_alats)
            $table->string('lokasi_saat_ini')->nullable()->after('kategori');
        });
    }

    public function down(): void
    {
        Schema::table('alats', function (Blueprint $table) {
            $table->dropColumn('lokasi_saat_ini');
        });
    }
};
