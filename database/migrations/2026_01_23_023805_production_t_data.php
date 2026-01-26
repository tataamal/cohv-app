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
        Schema::create('production_t_data', function (Blueprint $table) {
            $table->id();
            $table->string('MANDT', 3)->nullable();
            $table->string('KDAUF', 10)->nullable();
            $table->string('KDPOS', 6)->nullable();
            $table->string('MATNR', 40)->nullable();
            $table->string('MAKTX', 40)->nullable();
            $table->string('EDATU', 16)->nullable();
            $table->string('WERKSX', 4)->nullable();
            $table->string('KUNNR', 10)->nullable();
            $table->string('NAME1', 35)->nullable();
            $table->string('BSTNK', 35)->nullable();
            $table->timestamps();
            $table->index(['WERKSX', 'KDAUF', 'KDPOS']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_t_data');
    }
};
