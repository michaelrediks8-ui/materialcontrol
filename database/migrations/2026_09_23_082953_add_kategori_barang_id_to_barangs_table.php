<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barangs', function (Blueprint $table) {
            if (!Schema::hasColumn('barangs', 'kategori_barang_id')) {
                $table->foreignId('kategori_barang_id')->nullable()
                      ->after('kode_barang')
                      ->constrained('kategori_barangs')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('barangs', function (Blueprint $table) {
            if (Schema::hasColumn('barangs', 'kategori_barang_id')) {
                $table->dropConstrainedForeignId('kategori_barang_id');
            }
        });
    }
};