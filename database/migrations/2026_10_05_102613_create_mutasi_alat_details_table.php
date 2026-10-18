<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('mutasi_alat_details')) {
            Schema::create('mutasi_alat_details', function (Blueprint $table) {
                $table->id();
                $table->foreignId('mutasi_alat_id')->constrained('mutasi_alats')->cascadeOnDelete();
                $table->foreignId('alat_id')->constrained('alats')->cascadeOnDelete();
                $table->integer('jumlah_baik')->nullable();
                $table->integer('jumlah_rusak')->nullable();
                $table->integer('jumlah')->nullable();
                $table->timestamps();
            });
        }

        // Pindahkan data lama (1 alat per header) ke tabel detail, sebelum kolomnya dihapus
        if (Schema::hasColumn('mutasi_alats', 'alat_id')) {
            $rows = DB::table('mutasi_alats')->get();
            foreach ($rows as $row) {
                DB::table('mutasi_alat_details')->insert([
                    'mutasi_alat_id' => $row->id,
                    'alat_id' => $row->alat_id,
                    'jumlah_baik' => $row->jumlah_baik,
                    'jumlah_rusak' => $row->jumlah_rusak,
                    'jumlah' => $row->jumlah,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mutasi_alat_details');
    }
};