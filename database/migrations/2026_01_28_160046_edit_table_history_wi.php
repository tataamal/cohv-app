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
            $table->renameColumn('workcenter_induk', 'workcenter');
            $table->dropColumn('posted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('history_wi', function (Blueprint $table) {
            $table->renameColumn('workcenter', 'workcenter_induk');
            $table->dropColumn('posted_at');
        });
    }
};
