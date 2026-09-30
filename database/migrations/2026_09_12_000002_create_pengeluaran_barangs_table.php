<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel Header Barang Keluar
        Schema::create('pengeluaran_barangs', function (Blueprint $table) {
            $table->id();
            $table->string('no_transaksi')->unique();
            $table->dateTime('tanggal');
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('karyawan_id')->nullable()->constrained('karyawans')->nullOnDelete();
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });

        // Tabel Detail Barang Keluar (Anak)
        Schema::create('pengeluaran_barang_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengeluaran_barang_id')->constrained('pengeluaran_barangs')->cascadeOnDelete();
            $table->foreignId('barang_id')->constrained('barangs')->cascadeOnDelete();
            $table->decimal('jumlah', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengeluaran_barang_details');
        Schema::dropIfExists('pengeluaran_barangs');
    }
};