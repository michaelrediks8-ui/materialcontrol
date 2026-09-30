<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('karyawans', function (Blueprint $table) {
            $table->id();
            $table->string('nik')->unique()->nullable();
            $table->string('nama');
            
            // Relasi ke Master Departemen
            $table->foreignId('departemen_id')->nullable()->constrained('departemens')->nullOnDelete();
            
            $table->string('jabatan')->default('Mandor');
            $table->string('no_hp')->nullable();
            $table->text('alamat')->nullable();
            $table->timestamps();

            
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('karyawans');
    }
};