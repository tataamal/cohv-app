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
        Schema::create('wc_compatibilities', function (Blueprint $table) {
            $table->id();
            $table->string('wc_asal');
            $table->string('wc_tujuan')->nullable();
            $table->string('status');
            $table->string('plant')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wc_compatibilities');
    }
};
