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
        Schema::table('history_pro', function (Blueprint $table) {
            $table->index(['history_wi_item_id', 'status'], 'idx_pro_item_status');
        });

        Schema::table('history_wi_item', function (Blueprint $table) {
            $table->index(['history_wi_id', 'aufnr', 'vornr', 'nik'], 'idx_item_doc_aufnr_vornr_nik');
        });
    }

    public function down(): void
    {
        Schema::table('history_pro', function (Blueprint $table) {
            $table->dropIndex('idx_pro_item_status');
        });

        Schema::table('history_wi_item', function (Blueprint $table) {
            $table->dropIndex('idx_item_doc_aufnr_vornr_nik');
        });
    }
};
