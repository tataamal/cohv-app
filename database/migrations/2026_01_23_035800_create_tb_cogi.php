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
        Schema::create('tb_cogi', function (Blueprint $table) {
            $table->id();
            $table->string('MANDT')->nullable();
            $table->string('AUFNR')->nullable()->index();
            $table->string('RSNUM')->nullable()->index();
            $table->date('BUDAT')->nullable();
            $table->string('KDAUF')->nullable();
            $table->string('KDPOS')->nullable();
            $table->string('DWERK')->nullable();
            $table->string('MATNRH')->nullable();
            $table->string('MAKTXH')->nullable();
            $table->string('DISPOH')->nullable();
            $table->decimal('PSMNG', 15, 3)->nullable();
            $table->decimal('WEMNG', 15, 3)->nullable();
            $table->string('MATNR')->nullable()->index();
            $table->string('MAKTX')->nullable();
            $table->string('DISPO')->nullable();
            $table->decimal('ERFMG', 15, 3)->nullable();
            $table->string('AUFNRX')->nullable();
            $table->string('P1')->nullable();
            $table->string('PW')->nullable();
            $table->decimal('MENGE', 15, 3)->nullable();
            $table->string('MEINS')->nullable();
            $table->string('LGORTH')->nullable();
            $table->string('LGORT')->nullable();
            $table->string('DEVISI')->nullable();
            $table->string('TYPMAT')->nullable();
            $table->text('PESAN_ERROR')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_cogi');
    }
};
