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
            
            // Foreign key untuk WC Asal
            $table->foreignId('wc_asal_id')
                  ->constrained('workcenters')
                  ->onUpdate('cascade')
                  ->onDelete('cascade');

            // Foreign key untuk WC Tujuan
            $table->foreignId('wc_tujuan_id')
                  ->constrained('workcenters')
                  ->onUpdate('cascade')
                  ->onDelete('cascade');
            
            $table->string('status', 20)->default('Aktif');
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
