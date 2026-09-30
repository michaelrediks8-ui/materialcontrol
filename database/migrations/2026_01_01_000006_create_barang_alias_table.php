<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barang_alias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barang_id')->constrained('barangs')->cascadeOnDelete();
            $table->string('nama_alias'); // varian nama yang pernah dipakai orang, misal "srg tangan katun"
            $table->timestamps();

            $table->unique(['barang_id', 'nama_alias']);
            $table->index('nama_alias');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barang_alias');
    }
};
