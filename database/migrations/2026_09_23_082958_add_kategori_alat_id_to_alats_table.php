<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alats', function (Blueprint $table) {
            if (!Schema::hasColumn('alats', 'kategori_alat_id')) {
                $table->foreignId('kategori_alat_id')->nullable()
                      ->after('kategori')
                      ->constrained('kategori_alats')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('alats', function (Blueprint $table) {
            if (Schema::hasColumn('alats', 'kategori_alat_id')) {
                $table->dropConstrainedForeignId('kategori_alat_id');
            }
        });
    }
};