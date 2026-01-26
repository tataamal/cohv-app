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
        Schema::create('production_t_data3', function (Blueprint $table) {
            $table->id();
            $table->string('MANDT')->nullable();
            $table->string('ARBPL')->nullable();
            $table->string('ORDERX')->nullable();
            $table->string('WERKSX')->nullable();
            $table->string('PWWRK')->nullable();
            $table->string('KTEXT')->nullable();
            $table->string('ARBID')->nullable();
            $table->string('VERID')->nullable();
            $table->string('KDAUF')->nullable();
            $table->string('KDPOS')->nullable();
            $table->string('AUFNR')->nullable();
            $table->string('NAME1')->nullable();
            $table->string('KUNNR')->nullable();
            $table->string('PLNUM')->nullable();
            $table->string('STATS')->nullable();
            $table->string('DISPO')->nullable();
            $table->string('MATNR')->nullable();
            $table->string('MTART')->nullable();
            $table->string('MAKTX')->nullable();
            $table->string('VORNR')->nullable();
            $table->string('STEUS')->nullable();
            $table->string('AUART')->nullable();
            $table->string('MEINS')->nullable();
            $table->string('MATKL')->nullable();
            $table->double('PSMNG', 15, 3)->nullable();
            $table->double('WEMNG', 15, 3)->nullable();
            $table->double('MGVRG2', 15, 3)->nullable();
            $table->double('LMNGA', 15, 3)->nullable();
            $table->double('P1', 15, 3)->nullable();
            $table->double('MENGE2', 15, 3)->nullable();
            $table->double('VGW01', 15, 3)->nullable();
            $table->string('VGE01')->nullable();
            $table->double('CPCTYX', 15, 3)->nullable();
            $table->string('DTIME')->nullable();
            $table->string('DDAY')->nullable();
            $table->date('SSSLD')->nullable();
            $table->date('SSAVD')->nullable();
            $table->date('GLTRP')->nullable();
            $table->date('GSTRP')->nullable();
            $table->string('MATFG')->nullable();
            $table->string('MAKFG')->nullable();
            $table->string('CATEGORY')->nullable();
            $table->string('STATS2')->nullable();
            $table->string('GROES')->nullable();
            $table->string('FERTH')->nullable();
            $table->string('ZEINR')->nullable();
            $table->string('BSTNK')->nullable();
            $table->string('DAUAT')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_t_data3');
    }
};
