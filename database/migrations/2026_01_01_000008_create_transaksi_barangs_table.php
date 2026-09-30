<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksi_barangs', function (Blueprint $table) {
            $table->id();
            
            // System Identification
            $table->string('no_transaksi')->unique()->nullable(); // Auto generated (TRX-IN/OUT)
         
            // Polymorphic Origin (ASAL BARANG)
            // vendor/gudang
            $table->enum('asal_type', ['vendor', 'gudang']);
            $table->unsignedBigInteger('asal_id');

            // Polymorphic Destination (TUJUAN BARANG)
            // gudang/project
            $table->enum('tujuan_type', ['gudang', 'project']);
            $table->unsignedBigInteger('tujuan_id');

            // Logistik & Dokumen Ekspedisi (Berdasarkan SJ Fisik)
            $table->string('po_number')->nullable();
            $table->string('no_surat_jalan')->nullable();
            $table->string('jasa_pengirim')->nullable(); // Gopans, Shopee, JNE, Kurir
            $table->string('no_resi')->nullable();

            // Penanggung Jawab & Lokasi Detail
            $table->string('request_oleh')->nullable();
            $table->string('diterima_oleh')->nullable();
            $table->string('area')->nullable(); // Lokasi spesifik area
            $table->text('job_description')->nullable(); // Uraian pekerjaan

            $table->text('catatan')->nullable();

            $table->foreignId('voucher_pembelian_id')->nullable()
                  ->constrained('voucher_pembelian')->nullOnDelete();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('tanggal_transaksi')->nullable();
            $table->timestamps();
            // Indexing
            $table->index(['asal_type', 'asal_id']);
            $table->index(['tujuan_type', 'tujuan_id']);
            $table->index('tanggal_transaksi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi_barangs');
    }
};  