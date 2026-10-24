<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('jabatans')) {
            Schema::create('jabatans', function (Blueprint $table) {
                $table->id();
                $table->string('nama_jabatan')->unique();
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('karyawans', 'jabatan_id')) {
            Schema::table('karyawans', function (Blueprint $table) {
                $table->foreignId('jabatan_id')->nullable()->after('departemen_id')
                    ->constrained('jabatans')->nullOnDelete();
            });
        }

        // Pindahkan jabatan lama (teks bebas) ke tabel master.
        // Penulisan yang hanya beda huruf besar/kecil atau spasi disatukan.
        if (Schema::hasColumn('karyawans', 'jabatan')) {
            $map = [];
            $daftar = DB::table('karyawans')
                ->whereNotNull('jabatan')->where('jabatan', '!=', '')
                ->distinct()->pluck('jabatan');

            foreach ($daftar as $mentah) {
                $nama = trim(preg_replace('/\s+/', ' ', $mentah));
                $kunci = mb_strtolower($nama);

                if (! isset($map[$kunci])) {
                    $map[$kunci] = DB::table('jabatans')->insertGetId([
                        'nama_jabatan' => $nama,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('karyawans')->where('jabatan', $mentah)->update([
                    'jabatan_id' => $map[$kunci],
                    'jabatan' => $nama,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('karyawans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('jabatan_id');
        });
        Schema::dropIfExists('jabatans');
    }
};