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
        Schema::create('workcenters', function (Blueprint $table) {
            $table->id();
            $table->string('plant');
            $table->string('kode_wc');
            $table->string('description');
            $table->string('start_time')->nullable();
            $table->string('end_time')->nullable();
            $table->string('operating_time')->nullable();
            $table->string('capacity')->nullable();
            $table->unique(['plant', 'kode_wc']);
            $table->softDeletes(); 
            $table->timestamps();   
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workcenters');
    }
};
