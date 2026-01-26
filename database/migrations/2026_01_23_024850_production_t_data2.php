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
            $table->string('MANDT', 100)->nullable();
            $table->string('KDAUF', 100)->nullable();
            $table->string('KDPOS', 100)->nullable();
            $table->string('MATFG', 100)->nullable();
            $table->string('MAKFG', 100)->nullable();
            $table->date('EDATU')->nullable();
            $table->string('WERKSX', 4)->nullable();
            $table->string('KUNNR', 100)->nullable();
            $table->string('NAME1', 100)->nullable();
            $table->string('BSTNK', 100)->nullable();
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
