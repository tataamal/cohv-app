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
        Schema::create('serial_number', function (Blueprint $table) {
            $table->id();
            $table->string('so',100);
            $table->string('item',10);
            $table->string('serial_number',100);
            $table->datetime('gi_painting_date')->nullable();
            $table->datetime('gr_painting_date')->nullable();
            $table->datetime('gi_packing_date')->nullable();
            $table->datetime('gr_packing_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('serial_number');
    }
};
