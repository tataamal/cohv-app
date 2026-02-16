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
            $table->string('CHARG',100)->nullable()->after('VORNR');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_t_data1', function (Blueprint $table) {
            $table->dropColumn('CHARG');
        });
    }
};
