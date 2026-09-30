<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peminjaman_alats', function (Blueprint $table) {
            if (!Schema::hasColumn('peminjaman_alats', 'karyawan_id')) {
                $table->foreignId('karyawan_id')->nullable()->after('alat_id')
                      ->constrained('karyawans')->nullOnDelete();
            }
            if (!Schema::hasColumn('peminjaman_alats', 'keperluan')) {
                $table->string('keperluan')->nullable()->after('tanggal_rencana_kembali');
            }
        });
    }

    public function down(): void
    {
        Schema::table('peminjaman_alats', function (Blueprint $table) {
            if (Schema::hasColumn('peminjaman_alats', 'karyawan_id')) {
                $table->dropConstrainedForeignId('karyawan_id');
            }
            if (Schema::hasColumn('peminjaman_alats', 'keperluan')) {
                $table->dropColumn('keperluan');
            }
        });
    }
};