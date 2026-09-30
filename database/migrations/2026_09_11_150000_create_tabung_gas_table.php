<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tabung_gas', function (Blueprint $table) {
            $table->id();

            // Foreign Key Ke Relasi
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->foreignId('jenis_gas_id')->nullable()->constrained('jenis_gas')->nullOnDelete(); // Mengacu ke tabel jenis_gas
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();

            // Identitas Tabung & Keperluan
            $table->string('nomor_serial')->unique(); // Barcode / Serial
            $table->string('keperluan')->nullable();

            // Waktu & Pemulangan
            $table->dateTime('tanggal_masuk');
            $table->dateTime('batas_kembali')->nullable();
            $table->dateTime('tanggal_dikembalikan_aktual')->nullable();

            // Status & Catatan
            $table->string('status')->default('Dipinjam / Di Lapangan');
            $table->text('catatan')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tabung_gas');
    }
};