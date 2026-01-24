<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workcenter_mappings', function (Blueprint $table) {
            // Kalau sebelumnya kolomnya string, kita rename dulu biar gak bentrok
            $table->renameColumn('wc_induk', 'wc_induk_id');
            $table->renameColumn('workcenter', 'wc_anak_id');
        });

        Schema::table('workcenter_mappings', function (Blueprint $table) {
            // Ubah tipe dari string -> unsignedBigInteger
            // NOTE: butuh doctrine/dbal kalau pakai change()
            $table->unsignedBigInteger('wc_induk_id')->change();
            $table->unsignedBigInteger('wc_anak_id')->change();

            // Drop kolom yang tidak diperlukan
            $table->dropColumn(['nama_wc_induk', 'nama_workcenter']);

            // Index + FK
            $table->foreign('wc_induk_id')->references('id')->on('workcenters')->cascadeOnDelete();
            $table->foreign('wc_anak_id')->references('id')->on('workcenters')->cascadeOnDelete();

            // Optional: cegah duplikat mapping yang sama
            $table->unique(['wc_induk_id', 'wc_anak_id'], 'wc_parent_child_unique');
        });
    }

    public function down(): void
    {
        Schema::table('workcenter_mappings', function (Blueprint $table) {
            $table->dropUnique('wc_parent_child_unique');
            $table->dropForeign(['wc_induk_id']);
            $table->dropForeign(['wc_anak_id']);
            $table->string('nama_wc_induk', 50)->nullable();
            $table->string('nama_workcenter', 50)->nullable();
            $table->string('wc_induk_id', 10)->change();
            $table->string('wc_anak_id', 10)->change();
            $table->renameColumn('wc_induk_id', 'wc_induk');
            $table->renameColumn('wc_anak_id', 'workcenter');
        });
    }
};
