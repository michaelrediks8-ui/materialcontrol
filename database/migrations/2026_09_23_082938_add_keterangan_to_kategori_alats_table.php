<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kategori_alats', function (Blueprint $table) {
            if (!Schema::hasColumn('kategori_alats', 'keterangan')) {
                $table->string('keterangan')->nullable()->after('nama_kategori');
            }
        });
    }

    public function down(): void
    {
        Schema::table('kategori_alats', function (Blueprint $table) {
            if (Schema::hasColumn('kategori_alats', 'keterangan')) {
                $table->dropColumn('keterangan');
            }
        });
    }
};