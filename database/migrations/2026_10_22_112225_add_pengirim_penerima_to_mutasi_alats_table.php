<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mutasi_alats', function (Blueprint $table) {
            // Menambahkan kolom untuk menyimpan nama yang menyerahkan dan menerima
            $table->string('diserahkan_oleh')->nullable()->after('disetujui_oleh');
            $table->string('diterima_oleh')->nullable()->after('diserahkan_oleh');
        });
    }

    public function down(): void
    {
        Schema::table('mutasi_alats', function (Blueprint $table) {
            $table->dropColumn(['diserahkan_oleh', 'diterima_oleh']);
        });
    }
};
