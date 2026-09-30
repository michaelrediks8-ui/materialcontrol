<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi_barangs', function (Blueprint $table) {
            // siapa dari tim gudang yang menangani transaksi ini secara langsung
            // (bisa beda dari home base-nya team_gudang itu sendiri, kalau lagi bantu gudang lain)
            $table->foreignId('team_gudang_id')->nullable()
                  ->after('diterima_oleh')
                  ->constrained('team_gudangs')->nullOnDelete();

            $table->index('team_gudang_id');
        });
    }

    public function down(): void
    {
        Schema::table('transaksi_barangs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('team_gudang_id');
        });
    }
};
