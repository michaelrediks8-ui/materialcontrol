<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('stok_alats')) {
            Schema::create('stok_alats', function (Blueprint $table) {
                $table->id();
                $table->foreignId('alat_id')->constrained('alats')->cascadeOnDelete();
                $table->foreignId('gudang_id')->constrained('gudangs')->cascadeOnDelete();
                $table->integer('jumlah_baik')->default(0);
                $table->integer('jumlah_rusak')->default(0);
                $table->timestamps();
                $table->unique(['alat_id', 'gudang_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stok_alats');
    }
};