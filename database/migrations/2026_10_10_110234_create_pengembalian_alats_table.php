<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pengembalian_alats')) {
            Schema::create('pengembalian_alats', function (Blueprint $table) {
                $table->id();
                $table->foreignId('peminjaman_alat_id')->constrained('peminjaman_alats')->cascadeOnDelete();
                $table->dateTime('tanggal_kembali');
                $table->unsignedInteger('qty_kembali');
                $table->string('kondisi')->default('baik'); // baik | rusak | hilang
                $table->string('penerima')->nullable();
                $table->text('catatan')->nullable();
                $table->timestamps();
            });

            // Peminjaman yang sudah ditutup sebelumnya dianggap kembali penuh
            DB::table('peminjaman_alats')
                ->whereNotNull('tanggal_aktual_kembali')
                ->get()
                ->each(function ($p) {
                    DB::table('pengembalian_alats')->insert([
                        'peminjaman_alat_id' => $p->id,
                        'tanggal_kembali' => $p->tanggal_aktual_kembali,
                        'qty_kembali' => $p->qty,
                        'kondisi' => $p->kondisi_saat_kembali ?? 'baik',
                        'penerima' => $p->penerima_pengembalian,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pengembalian_alats');
    }
};