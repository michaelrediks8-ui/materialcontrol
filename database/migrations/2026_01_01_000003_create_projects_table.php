<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();

            $table->string('no_po')->nullable();
            $table->string('nama_project');
            $table->string('kode_project')->nullable();

            $table->string('area')->nullable();
            $table->string('pic')->nullable();

            $table->string('status')->default('aktif');

            $table->date('tanggal_mulai')->nullable();
            $table->date('target_selesai')->nullable();

            $table->boolean('aktif')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};