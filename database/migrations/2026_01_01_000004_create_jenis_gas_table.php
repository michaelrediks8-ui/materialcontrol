<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jenis_gas', function (Blueprint $table) {
            $table->id();
            $table->string('nama_gas')->unique(); // Contoh: Acetylene, Oksigen, Argon, CO2
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jenis_gas');
    }
};