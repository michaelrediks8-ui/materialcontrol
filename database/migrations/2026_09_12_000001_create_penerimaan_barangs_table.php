<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel Header Barang Masuk
        Schema::create('penerimaan_barangs', function (Blueprint $table) {
            $table->id();
            $table->string('no_transaksi')->unique();
            $table->dateTime('tanggal');
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });

        // Tabel Detail Barang Masuk (Anak)
        Schema::create('penerimaan_barang_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penerimaan_barang_id')->constrained('penerimaan_barangs')->cascadeOnDelete();
            $table->foreignId('barang_id')->constrained('barangs')->cascadeOnDelete();
            $table->decimal('jumlah', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penerimaan_barang_details');
        Schema::dropIfExists('penerimaan_barangs');
    }
};