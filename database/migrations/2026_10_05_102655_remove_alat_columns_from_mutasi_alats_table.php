<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mutasi_alats', function (Blueprint $table) {
            if (Schema::hasColumn('mutasi_alats', 'alat_id')) {
                $table->dropConstrainedForeignId('alat_id');
            }
            foreach (['jumlah_baik', 'jumlah_rusak', 'jumlah'] as $col) {
                if (Schema::hasColumn('mutasi_alats', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('mutasi_alats', function (Blueprint $table) {
            $table->foreignId('alat_id')->nullable()->constrained('alats')->nullOnDelete();
            $table->integer('jumlah_baik')->nullable();
            $table->integer('jumlah_rusak')->nullable();
            $table->integer('jumlah')->nullable();
        });
    }
};