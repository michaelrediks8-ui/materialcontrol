<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi_barangs', function (Blueprint $table) {
            if (!Schema::hasColumn('transaksi_barangs', 'purchase_order_id')) {
                $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('transaksi_barangs', function (Blueprint $table) {
            if (Schema::hasColumn('transaksi_barangs', 'purchase_order_id')) {
                $table->dropConstrainedForeignId('purchase_order_id');
            }
        });
    }
};