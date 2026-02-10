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
            $table->string('stats')->nullable()->after('vornr');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('history_wi_item', function (Blueprint $table) {
            $table->dropColumn('stats');
        });
    }
};
