<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mutasi_alats', function (Blueprint $table) {
            $table->id();
            $table->string('no_surat_jalan')->unique(); // Contoh: SJ-ALT/2026/09/001
            $table->dateTime('tanggal_mutasi');
            
            // Relasi Alat yang dipindahkan
            $table->foreignId('alat_id')->constrained('alats')->cascadeOnDelete();
            
            // Asal Perpindahan (Bisa dari Gudang atau Project)
            $table->string('asal_type'); // 'gudang' atau 'project'
            $table->unsignedBigInteger('asal_id');
            
            // Tujuan Perpindahan (Bisa ke Gudang atau Project)
            $table->string('tujuan_type'); // 'gudang' atau 'project'
            $table->unsignedBigInteger('tujuan_id');

            // Penanggung Jawab & Pengangkut
            $table->string('disetujui_oleh')->nullable(); // PM / Supervisor
            $table->string('pengangkut_driver')->nullable(); // Driver / Logistik
            $table->string('kondisi_alat')->default('Bagus'); // Bagus, Rusak Ringan, Rusak Berat
            $table->text('catatan')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mutasi_alats');
    }
};