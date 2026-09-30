<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mutasi_alats', function (Blueprint $table) {
            if (Schema::hasColumn('mutasi_alats', 'asal_id')) {
                $table->dropColumn(['asal_id', 'tujuan_id']);
            }
        });

        Schema::table('mutasi_alats', function (Blueprint $table) {
            $table->unsignedBigInteger('asal_id')->nullable()->after('asal_type');
            $table->unsignedBigInteger('tujuan_id')->nullable()->after('tujuan_type');

            if (!Schema::hasColumn('mutasi_alats', 'jenis_mutasi')) {
                $table->string('jenis_mutasi')->default('transfer')->after('alat_id');
                // registrasi | transfer | rusak | perbaikan
            }
            if (!Schema::hasColumn('mutasi_alats', 'jumlah_baik')) {
                $table->integer('jumlah_baik')->nullable()->after('tujuan_id');
            }
            if (!Schema::hasColumn('mutasi_alats', 'jumlah_rusak')) {
                $table->integer('jumlah_rusak')->nullable()->after('jumlah_baik');
            }
            if (!Schema::hasColumn('mutasi_alats', 'jumlah')) {
                // dipakai khusus utk jenis 'rusak'/'perbaikan' (asal_id = tujuan_id, cuma pindah status)
                $table->integer('jumlah')->nullable()->after('jumlah_rusak');
            }
        });

        // no_surat_jalan tidak selalu ada untuk mutasi jenis 'rusak'/'perbaikan'
       Schema::table('mutasi_alats', function (Blueprint $table) {
    $table->string('no_surat_jalan')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('mutasi_alats', function (Blueprint $table) {
            $table->dropColumn(['jenis_mutasi', 'jumlah_baik', 'jumlah_rusak', 'jumlah']);
        });
    }
};