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
        Schema::create('production_t_data4', function (Blueprint $table) {
            $table->id();
            $table->string('MANDT')->nullable();
            $table->string('RSNUM')->nullable();
            $table->string('RSPOS')->nullable();
            $table->string('VORNR')->nullable();
            $table->string('KDAUF')->nullable();
            $table->string('KDPOS')->nullable();
            $table->string('AUFNR')->nullable();
            $table->string('PLNUM')->nullable();
            $table->string('STATS')->nullable();
            $table->string('DISPO')->nullable();
            $table->string('MATNR')->nullable();
            $table->string('MAKTX')->nullable();
            $table->string('MEINS')->nullable();
            $table->string('BAUGR')->nullable();
            $table->string('WERKSX')->nullable();
            $table->double('BDMNG', 15, 3)->nullable();
            $table->double('KALAB', 15, 3)->nullable();
            $table->double('VMENG', 15, 3)->nullable();
            $table->string('SOBSL')->nullable();
            $table->string('BESKZ')->nullable();
            $table->string('LTEXT')->nullable();
            $table->string('LGORT')->nullable();
            $table->string('OUTSREQ')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_t_data4');
    }
};
