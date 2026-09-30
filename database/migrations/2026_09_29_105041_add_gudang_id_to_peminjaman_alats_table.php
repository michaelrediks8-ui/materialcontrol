<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peminjaman_alats', function (Blueprint $table) {
            if (!Schema::hasColumn('peminjaman_alats', 'gudang_id')) {
                $table->foreignId('gudang_id')->nullable()->after('alat_id')
                      ->constrained('gudangs')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('peminjaman_alats', function (Blueprint $table) {
            if (Schema::hasColumn('peminjaman_alats', 'gudang_id')) {
                $table->dropConstrainedForeignId('gudang_id');
            }
        });
    }
};