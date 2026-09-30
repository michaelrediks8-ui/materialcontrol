<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Satuan Besar di barangs: dari teks bebas -> FK ke satuans
        Schema::table('barangs', function (Blueprint $table) {
            if (!Schema::hasColumn('barangs', 'satuan_besar_id')) {
                $table->foreignId('satuan_besar_id')->nullable()->constrained('satuans')->nullOnDelete();
            }
        });

        // Satuan per baris transaksi: dari teks bebas (satuan_catatan) -> FK ke satuans
        Schema::table('transaksi_barang_details', function (Blueprint $table) {
            if (!Schema::hasColumn('transaksi_barang_details', 'satuan_id')) {
                $table->foreignId('satuan_id')->nullable()->after('qty')
                      ->constrained('satuans')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('barangs', function (Blueprint $table) {
            if (Schema::hasColumn('barangs', 'satuan_besar_id')) {
                $table->dropConstrainedForeignId('satuan_besar_id');
            }
        });

        Schema::table('transaksi_barang_details', function (Blueprint $table) {
            if (Schema::hasColumn('transaksi_barang_details', 'satuan_id')) {
                $table->dropConstrainedForeignId('satuan_id');
            }
        });
    }
};
