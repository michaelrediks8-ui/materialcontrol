<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProjectIdToTransaksiBarangDetailsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        if (! Schema::hasColumn('transaksi_barang_details', 'project_id')) {
            Schema::table('transaksi_barang_details', function (Blueprint $table) {
                $table->unsignedBigInteger('project_id')->nullable()->after('satuan_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        if (Schema::hasColumn('transaksi_barang_details', 'project_id')) {
            Schema::table('transaksi_barang_details', function (Blueprint $table) {
                $table->dropColumn('project_id');
            });
        }
    }
}