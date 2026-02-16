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
        Schema::table('serial_number', function (Blueprint $table) {
            $table->unique(['so', 'item', 'serial_number'], 'uniq_so_item_serial');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('serial_number', function (Blueprint $table) {
            $table->dropUnique('uniq_so_item_serial');
        });
    }
};
