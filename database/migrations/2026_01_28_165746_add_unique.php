<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('history_wi', function (Blueprint $table) {
            $table->time('document_time')->nullable()->change();
        });

        Schema::table('history_wi_item', function (Blueprint $table) {
            $table->unique(['history_wi_id', 'aufnr', 'vornr', 'nik'], 'uq_wiitem_doc_aufnr_vornr_nik');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('history_wi', function (Blueprint $table) {
            $table->time('document_time')->change();
        });

        Schema::table('history_wi_item', function (Blueprint $table) {
            $table->dropUnique('uq_wiitem_doc_aufnr_vornr_nik');
        });
    }
};
