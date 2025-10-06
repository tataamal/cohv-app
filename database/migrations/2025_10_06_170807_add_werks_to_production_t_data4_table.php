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
        Schema::table('production_t_data4', function (Blueprint $table) {
            // Tambahkan kolom 'WERKS' (kode plant, varchar/string 4 karakter)
            // Letakkan setelah kolom VORNR agar rapi
            $table->string('WERKS', 4)->nullable()->after('VORNR');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_t_data4', function (Blueprint $table) {
            $table->dropColumn('WERKS');
        });
    }
};