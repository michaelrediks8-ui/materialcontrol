<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi_barangs', function (Blueprint $table) {
            if (!Schema::hasColumn('transaksi_barangs', 'jenis_transaksi')) {
                $table->string('jenis_transaksi')->nullable()->after('no_transaksi');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transaksi_barangs', function (Blueprint $table) {
            if (Schema::hasColumn('transaksi_barangs', 'jenis_transaksi')) {
                $table->dropColumn('jenis_transaksi');
            }
        });
    }
};