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

            // Kolom dari SAP
            $table->string('MANDT', 3)->nullable();         // Client
            $table->string('KDAUF', 10)->nullable();        // Sales Order
            $table->string('KDPOS', 6)->nullable();         // Sales Order Item (NUMC6)
            $table->string('MATNR', 40)->nullable();        // Material Number
            $table->string('MAKTX', 40)->nullable();        // Material Description
            $table->string('EDATU', 16)->nullable();         // Schedule Line Date (format SAP YYYYMMDD)
            $table->string('WERKSX', 4)->nullable();        // Plant
            $table->string('KUNNR', 10)->nullable();        // Customer Number
            $table->string('NAME1', 35)->nullable();        // Name 1

            $table->timestamps();

            // Index untuk mempercepat query
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
