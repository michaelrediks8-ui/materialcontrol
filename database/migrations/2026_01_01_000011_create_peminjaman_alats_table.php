<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peminjaman_alats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alat_id')->constrained('alats');
            $table->integer('qty')->default(1);
            $table->date('tanggal_pinjam');
            $table->string('peminjam'); // ID Card / nama penanggung jawab
            $table->string('pemberi_barang')->nullable();
            $table->foreignId('project_id')->nullable()->constrained('projects');
            $table->date('tanggal_rencana_kembali')->nullable();

            // NULL = masih dipinjam. Diisi = sudah kembali.
            $table->date('tanggal_aktual_kembali')->nullable();
            $table->string('penerima_pengembalian')->nullable();
            $table->enum('kondisi_saat_kembali', ['baik', 'rusak', 'hilang'])->nullable();
            $table->string('checked_by')->nullable();

            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['alat_id', 'tanggal_aktual_kembali']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peminjaman_alats');
    }
};
