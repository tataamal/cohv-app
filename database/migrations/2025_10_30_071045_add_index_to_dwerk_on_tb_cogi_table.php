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
        Schema::table('tb_cogi', function (Blueprint $table) {
            // Baris ini akan menambahkan index ke kolom DWERK
            $table->index('DWERK');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tb_cogi', function (Blueprint $table) {
            // Baris ini akan menghapus index jika migrasi di-rollback
            $table->dropIndex(['DWERK']);
        });
    }
};