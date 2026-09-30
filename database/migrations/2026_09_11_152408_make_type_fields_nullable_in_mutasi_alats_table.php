<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mutasi_alats', function (Blueprint $table) {
            $table->string('asal_type')->nullable()->change();
            $table->string('tujuan_type')->nullable()->change();
            $table->string('asal_id')->change(); // Mengubah tipe ke string karena menyimpan format 'gudang_3'
            $table->string('tujuan_id')->change();
        });
    }

    public function down(): void
    {
        Schema::table('mutasi_alats', function (Blueprint $table) {
            $table->string('asal_type')->nullable(false)->change();
            $table->string('tujuan_type')->nullable(false)->change();
        });
    }
};