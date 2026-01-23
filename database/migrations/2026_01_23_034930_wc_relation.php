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
        Schema::create('wc_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wc_asal_id')
                  ->constrained('workcenters')
                  ->onUpdate('cascade')
                  ->onDelete('cascade');
            $table->foreignId('wc_tujuan_id')
                  ->constrained('workcenters')
                  ->onUpdate('cascade')
                  ->onDelete('cascade');
            $table->string('status', 255)->default('compatible');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wc_relations');
    }
};
