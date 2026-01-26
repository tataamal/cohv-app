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
        Schema::table('workcenter_mappings', function (Blueprint $table) {
            // Adding Foreign Key to Kode Laravel
            $table->unsignedBigInteger('kode_laravel_id')->nullable()->after('wc_anak_id');
            
            // Drop legacy columns
            $table->dropColumn(['plant', 'kode_laravel']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workcenter_mappings', function (Blueprint $table) {
            $table->dropColumn('kode_laravel_id');
        });
    }
};
