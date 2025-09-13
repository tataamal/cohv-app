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
        Schema::create('production_t_data2', function (Blueprint $table) {
            $table->id();
            $table->string('MANDT', 3)->nullable();
            $table->string('KDAUF', 20)->nullable();
            $table->string('KDPOS', 10)->nullable();
            $table->string('MATFG', 30)->nullable();
            $table->string('MAKFG')->nullable();
            $table->date('EDATU')->nullable();
            $table->string('WERKSX')->nullable();
            $table->string('KUNNR', 10)->nullable();
            $table->string('NAME1', 35)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_t_data2');
    }
};
