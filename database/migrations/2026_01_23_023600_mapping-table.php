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
        Schema::create('mapping_table', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_sap_id')->constrained('user_sap')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('kode_laravel_id')->constrained('kode_laravel')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('mrp_id')->constrained('mrp')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('workcenter_id')->constrained('workcenters')->cascadeOnUpdate()->restrictOnDelete();
            $table->unique(['user_sap_id', 'kode_laravel_id', 'mrp_id', 'workcenter_id'], 'mapping_unique_combo');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mapping_table');
    }
};
