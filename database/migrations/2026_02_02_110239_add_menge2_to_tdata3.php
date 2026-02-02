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
        Schema::table('production_t_data3', function (Blueprint $table) {
            // Menambah kolom baru
            $table->decimal('MENG2', 15, 3)->nullable()->after('P1');
            
            // Memindahkan kolom yang sudah ada (MENGE2) ke setelah CATEGORY
            $table->string('MENGE2')->change()->after('CATEGORY'); 
        });
    }

    public function down(): void
    {
        Schema::table('production_t_data3', function (Blueprint $table) {
            $table->dropColumn('MENG2');
            // Catatan: Untuk 'down', kembalikan MENGE2 ke posisi asalnya jika diperlukan
            $table->string('MENGE2')->change()->after('P1'); 
        });
    }
};
