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
        Schema::table('history_wi_item', function (Blueprint $table) {
            $table->string('matfg')->nullable()->after('material_number');
            $table->string('makfg')->nullable()->after('material_desc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('history_wi_item', function (Blueprint $table) {
            $table->dropColumn('matfg');
            $table->dropColumn('makfg');
        });
    }
};
