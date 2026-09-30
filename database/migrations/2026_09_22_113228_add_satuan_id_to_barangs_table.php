<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barangs', function (Blueprint $table) {
            if (!Schema::hasColumn('barangs', 'satuan_id')) {
                $table->foreignId('satuan_id')->nullable()->after('satuan_besar_id')
                      ->constrained('satuans')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('barangs', function (Blueprint $table) {
            if (Schema::hasColumn('barangs', 'satuan_id')) {
                $table->dropConstrainedForeignId('satuan_id');
            }
        });
    }
};