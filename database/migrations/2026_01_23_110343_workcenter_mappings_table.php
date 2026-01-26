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
        Schema::create('workcenter_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('wc_induk', 10);
            $table->string('nama_wc_induk', 50);
            $table->string('workcenter', 10);
            $table->string('nama_workcenter', 50);
            $table->string('kode_laravel', 10)->nullable();
            $table->string('plant', 10)->nullable();
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workcenter_mappings');
    }
};
