<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_gudangs', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('posisi')->nullable(); // Kepala Gudang, Admin Gudang, Helper, dst — bebas diisi
            // gudang_id = home base / penempatan utama saat ini (boleh kosong kalau posisinya floating/admin pusat)
            $table->foreignId('gudang_id')->nullable()->constrained('gudangs')->nullOnDelete();
            $table->string('kontak')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->index('gudang_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_gudangs');
    }
};
