<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('kategori_barangs')->count() === 0) {
            DB::table('kategori_barangs')->insert([
                ['kode_kategori' => 'ELEC', 'nama_kategori' => 'Electrical', 'keterangan' => 'Material pekerjaan kelistrikan', 'created_at' => now(), 'updated_at' => now()],
                ['kode_kategori' => 'MECH', 'nama_kategori' => 'Mechanical', 'keterangan' => 'Material pekerjaan mekanikal', 'created_at' => now(), 'updated_at' => now()],
                ['kode_kategori' => 'INST', 'nama_kategori' => 'Instrumentation', 'keterangan' => 'Material pekerjaan instrumentasi', 'created_at' => now(), 'updated_at' => now()],
                ['kode_kategori' => 'CABLE', 'nama_kategori' => 'Cable & Wire', 'keterangan' => 'Kabel dan penghantar', 'created_at' => now(), 'updated_at' => now()],
                ['kode_kategori' => 'CABLE_ACC', 'nama_kategori' => 'Cable Accessories', 'keterangan' => 'Cable gland, cable lug, ferrule, marker dan aksesorinya', 'created_at' => now(), 'updated_at' => now()],
                ['kode_kategori' => 'PANEL', 'nama_kategori' => 'Panel & Components', 'keterangan' => 'Panel, terminal block, MCB, MCCB, contactor dan komponen panel', 'created_at' => now(), 'updated_at' => now()],
                ['kode_kategori' => 'TRAY', 'nama_kategori' => 'Cable Tray & Conduit', 'keterangan' => 'Cable tray, conduit dan aksesorinya', 'created_at' => now(), 'updated_at' => now()],
                ['kode_kategori' => 'GROUND', 'nama_kategori' => 'Grounding', 'keterangan' => 'Material grounding dan earthing', 'created_at' => now(), 'updated_at' => now()],
                ['kode_kategori' => 'PIPE', 'nama_kategori' => 'Pipe & Fitting', 'keterangan' => 'Pipe, elbow, tee, reducer, flange dan fitting', 'created_at' => now(), 'updated_at' => now()],
                ['kode_kategori' => 'FASTENER', 'nama_kategori' => 'Fastener', 'keterangan' => 'Baut, mur, washer, anchor dan pengikat', 'created_at' => now(), 'updated_at' => now()],
                ['kode_kategori' => 'SUPPORT', 'nama_kategori' => 'Support & Bracket', 'keterangan' => 'Support, bracket, hanger dan clamp', 'created_at' => now(), 'updated_at' => now()],
                ['kode_kategori' => 'CONSUMABLE', 'nama_kategori' => 'Consumable', 'keterangan' => 'Material yang habis digunakan dalam pekerjaan', 'created_at' => now(), 'updated_at' => now()],
                ['kode_kategori' => 'OTHER', 'nama_kategori' => 'Lain-lain', 'keterangan' => 'Material yang belum masuk kategori', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        if (DB::table('kategori_alats')->count() === 0) {
            DB::table('kategori_alats')->insert([
                ['kode_kategori' => 'HAND', 'nama_kategori' => 'Hand Tools', 'keterangan' => 'Peralatan tangan seperti tang, obeng, kunci dan cutter', 'created_at' => now(), 'updated_at' => now()],
                ['kode_kategori' => 'POWER', 'nama_kategori' => 'Power Tools', 'keterangan' => 'Peralatan bertenaga listrik atau baterai seperti gerinda, bor dan impact', 'created_at' => now(), 'updated_at' => now()],
                ['kode_kategori' => 'MEASURE', 'nama_kategori' => 'Measuring Tools', 'keterangan' => 'Alat ukur seperti meteran, multimeter dan clamp meter', 'created_at' => now(), 'updated_at' => now()],
                ['kode_kategori' => 'ELECTRICAL', 'nama_kategori' => 'Electrical Tools', 'keterangan' => 'Alat khusus pekerjaan dan pengujian kelistrikan', 'created_at' => now(), 'updated_at' => now()],
                ['kode_kategori' => 'CRIMP', 'nama_kategori' => 'Crimping Tools', 'keterangan' => 'Alat crimping kabel dan terminal', 'created_at' => now(), 'updated_at' => now()],
                ['kode_kategori' => 'CUTTING', 'nama_kategori' => 'Cutting Tools', 'keterangan' => 'Alat pemotong kabel, pipa dan material lainnya', 'created_at' => now(), 'updated_at' => now()],
                ['kode_kategori' => 'TERMINATION', 'nama_kategori' => 'Termination Tools', 'keterangan' => 'Peralatan untuk pekerjaan terminasi kabel', 'created_at' => now(), 'updated_at' => now()],
                ['kode_kategori' => 'WELDING', 'nama_kategori' => 'Welding Equipment', 'keterangan' => 'Mesin las dan perlengkapan pengelasan', 'created_at' => now(), 'updated_at' => now()],
                ['kode_kategori' => 'LIFTING', 'nama_kategori' => 'Lifting Equipment', 'keterangan' => 'Peralatan pengangkatan seperti chain block, sling dan lever block', 'created_at' => now(), 'updated_at' => now()],
                ['kode_kategori' => 'SCAFFOLDING', 'nama_kategori' => 'Scaffolding Equipment', 'keterangan' => 'Peralatan scaffolding dan aksesorinya', 'created_at' => now(), 'updated_at' => now()],
                ['kode_kategori' => 'MECHANICAL', 'nama_kategori' => 'Mechanical Tools', 'keterangan' => 'Peralatan khusus pekerjaan mekanikal', 'created_at' => now(), 'updated_at' => now()],
                ['kode_kategori' => 'INSTRUMENT', 'nama_kategori' => 'Instrument Tools', 'keterangan' => 'Peralatan instrumentasi dan testing', 'created_at' => now(), 'updated_at' => now()],
                ['kode_kategori' => 'OTHER', 'nama_kategori' => 'Lain-lain', 'keterangan' => 'Alat yang belum masuk kategori', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
    }

    public function down(): void
    {
        DB::table('kategori_barangs')->truncate();
        DB::table('kategori_alats')->truncate();
    }
};