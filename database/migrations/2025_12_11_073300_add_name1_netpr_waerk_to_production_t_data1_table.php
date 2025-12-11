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
        Schema::table('production_t_data1', function (Blueprint $table) {
            $table->string('NAME1')->nullable()->after('STATS2'); // Adjust placement if needed
            $table->double('NETPR', 15, 3)->nullable()->after('NAME1');
            $table->string('WAERK')->nullable()->after('NETPR');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_t_data1', function (Blueprint $table) {
            $table->dropColumn(['NAME1', 'NETPR', 'WAERK']);
        });
    }
};
