<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voucher_pembelian', function (Blueprint $table) {
            $table->id();
            $table->string('no_voucher');
            $table->foreignId('vendor_id')->constrained('vendors');
            $table->date('tanggal');
            $table->enum('status_bayar', ['belum', 'sudah'])->default('belum');
            $table->date('tanggal_dibayar')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['vendor_id', 'status_bayar']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_pembelian');
    }
};
