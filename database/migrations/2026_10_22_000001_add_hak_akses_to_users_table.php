<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('can_input')->default(true)->after('is_active');
            $table->boolean('can_keluar')->default(true)->after('can_input');
            $table->boolean('can_delete')->default(false)->after('can_keluar');
        });

        Schema::create('gudang_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gudang_id')->constrained('gudangs')->cascadeOnDelete();
            $table->primary(['user_id', 'gudang_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gudang_user');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['can_input', 'can_keluar', 'can_delete']);
        });
    }
};
