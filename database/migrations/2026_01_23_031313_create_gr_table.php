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
       Schema::create('gr', function (Blueprint $table) {
            // Kolom standar Laravel
            $table->id();

            // Kolom dari data SAP Anda
            $table->string('MANDT', 3)->nullable();
            $table->string('LGORT', 4)->nullable();
            $table->string('MBLNR', 10)->nullable();
            $table->string('DISPO', 3)->nullable();
            $table->string('AUFNR', 12)->nullable();
            $table->string('WERKS', 4)->nullable();
            $table->string('CHARG', 10)->nullable();
            $table->string('MATNR', 40)->nullable();
            $table->string('MAKTX', 40)->nullable();
            $table->string('MAT_KDAUF', 10)->nullable();
            $table->string('MAT_KDPOS', 6)->nullable();
            $table->string('KUNNR', 10)->nullable();
            $table->string('NAME2', 30)->nullable();
            $table->decimal('PSMNG', 13, 3)->nullable();
            $table->decimal('MENGE', 13, 3)->nullable();
            $table->decimal('MENGEX', 13, 3)->nullable();
            $table->decimal('MENGE_M', 13, 3)->nullable();
            $table->decimal('MENGE_M2', 13, 3)->nullable();
            $table->decimal('MENGE_M3', 13, 3)->nullable();
            $table->decimal('WEMNG', 13, 3)->nullable();
            $table->string('MEINS', 3)->nullable();
            $table->string('LINE', 40)->nullable();
            $table->decimal('STPRS', 11, 2)->nullable();
            $table->string('WAERS', 5)->nullable();
            $table->decimal('VALUE', 11, 2)->nullable();
            $table->date('BUDAT_MKPF')->nullable();
            $table->date('CPUDT_MKPF')->nullable();
            $table->integer('NODAY')->nullable();
            $table->string('AUFNR2', 50)->nullable();
            $table->string('CSMG', 50)->nullable();
            $table->string('TXT50', 50)->nullable();
            $table->decimal('NETPR', 11, 2)->nullable();
            $table->string('WAERK', 5)->nullable();
            $table->decimal('VALUSX', 13, 2)->nullable();
            $table->decimal('VALUS', 13, 2)->nullable();
            $table->string('PERNR', 20)->nullable();
            $table->string('ARBPL', 20)->nullable();
            $table->string('KTEXT', 20)->nullable();
            $table->string('MATNR2', 100)->nullable();
            $table->string('MAKTX2', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gr');
    }
};
