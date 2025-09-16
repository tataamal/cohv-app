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
            $table->id(); // INT, auto-increment, primary key
            $table->string('kode_wc', 50)->unique();
            $table->string('WERKS', 10);
            $table->string('WERKSX', 100);
            $table->string('description', 255)->nullable()->charset('utf8mb4')->collation('utf8mb4_unicode_ci');
            $table->timestamps(); // Menambahkan created_at dan updated_at
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
